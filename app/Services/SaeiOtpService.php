<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Interfaces\SaeiOtpServiceInterface;

/**
 * خدمة التحقق عبر OTP — منصة ساعي (Automize)
 * =============================================
 * الوثائق: https://saei.automize.sa
 * الرابط الأساسي: https://api.saei.automize.sa/api
 *
 * الإعدادات المطلوبة في ملف .env :
 *   SAEI_API_KEY=sk_live_xxxxxxxxxxxxxxx
 *   SAEI_FROM_PHONE_ID=1276243858896899     ← Phone Number ID وليس رقم الهاتف
 *   SAEI_TEMPLATE_ID=77                     ← رقم معرّف القالب (template_id)
 *   SAEI_CALLBACK_SECRET=                   ← مفتاح توقيع Callback (اختياري)
 *   SAEI_SIMULATION=false                   ← true لتجاهل الإرسال الفعلي في بيئة التطوير
 *
 * دورة حياة التحقق:
 *   1. sendOtp($phone)         → ترسل الرقم لساعي ← تعيد verification_id
 *   2. (المستخدم يقرأ الكود من واتساب ويدخله)
 *   3. verifyOtp($id, $code)   → تتحقق من الكود ← تعيد approved | denied | expired
 */
class SaeiOtpService implements SaeiOtpServiceInterface
{
    /**
     * الرابط الأساسي لـ API ساعي
     */
    protected string $baseUrl;

    /**
     * مفتاح الـ API السري (Bearer Token)
     */
    protected string $apiKey;

    /**
     * معرّف رقم الهاتف المُرسِل في نظام ساعي
     */
    protected string $fromPhoneId;

    /**
     * معرّف القالب (template_id) المعتمد من Meta لإرسال OTP
     */
    protected int $templateId;

    /**
     * المفتاح السري لتوقيع Callback (اختياري)
     */
    protected ?string $callbackSecret;

    /**
     * وضع المحاكاة: عند تفعيله لا يُرسل طلب حقيقي
     */
    protected bool $simulation;

    public function __construct()
    {
        $this->baseUrl       = rtrim(env('SAEI_BASE_URL', 'https://api.saei.automize.sa/api'), '/');
        $this->apiKey        = env('SAEI_API_KEY', '');
        $this->fromPhoneId   = env('SAEI_FROM_PHONE_ID', '');
        $this->templateId    = (int) env('SAEI_TEMPLATE_ID', 0);
        $this->callbackSecret = env('SAEI_CALLBACK_SECRET');
        $this->simulation    = (bool) env('SAEI_SIMULATION', false);
    }

    // ─────────────────────────────────────────────────────────────
    // الهيدرز المشتركة
    // ─────────────────────────────────────────────────────────────

    protected function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 1. إرسال رمز OTP
    //    POST /v1/verify/start
    //    Body: { "to": "+9665XXXXXXXX", "from": "phone_id", "template_id": 77 }
    //    Response: { "verification_id": 123, ... }
    // ─────────────────────────────────────────────────────────────

    /**
     * إرسال رمز OTP إلى رقم هاتف المستخدم عبر واتساب
     *
     * @param  string  $phone  رقم الهاتف بصيغة E.164 مثل: +966XXXXXXXXX
     * @return array{success: bool, verification_id: int|null, message: string}
     */
    public function sendOtp(string $phone): array
    {
        // تنظيف الرقم وإضافة + إن لم يكن موجوداً
        $phone = $this->formatPhone($phone);

        // وضع المحاكاة (Development)
        if ($this->simulation) {
            Log::info("[SaeiOTP][SIMULATION] OTP requested for {$phone}");
            return [
                'success'         => true,
                'verification_id' => 999999, // معرّف وهمي للاختبار
                'message'         => 'simulation_mode',
            ];
        }

        if (empty($this->apiKey) || empty($this->fromPhoneId) || $this->templateId === 0) {
            Log::error('[SaeiOTP] Missing credentials: SAEI_API_KEY, SAEI_FROM_PHONE_ID, or SAEI_TEMPLATE_ID');
            return [
                'success'         => false,
                'verification_id' => null,
                'message'         => 'missing_credentials',
            ];
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$this->baseUrl}/v1/verify/start", [
                    'to'          => $phone,
                    'from'        => $this->fromPhoneId,
                    'template_id' => $this->templateId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('[SaeiOTP] OTP sent successfully', [
                    'phone'           => $phone,
                    'verification_id' => $data['verification_id'] ?? null,
                ]);

                return [
                    'success'         => true,
                    'verification_id' => (int) ($data['verification_id'] ?? 0),
                    'message'         => 'otp_sent',
                ];
            }

            // خطأ من ساعي
            $error = $response->json();
            Log::warning('[SaeiOTP] sendOtp failed', [
                'phone'    => $phone,
                'status'   => $response->status(),
                'response' => $error,
            ]);

            return [
                'success'         => false,
                'verification_id' => null,
                'message'         => $error['message'] ?? 'send_failed',
            ];

        } catch (\Exception $e) {
            Log::error('[SaeiOTP] sendOtp exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success'         => false,
                'verification_id' => null,
                'message'         => 'network_error',
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 2. التحقق من رمز OTP
    //    POST /v1/verify/check
    //    Body: { "verification_id": 123, "code": "123456" }
    //    Response: { "status": "approved" | "denied" | "expired" }
    // ─────────────────────────────────────────────────────────────

    /**
     * التحقق من الرمز الذي أدخله المستخدم
     *
     * @param  int     $verificationId  المعرّف الذي أعادته دالة sendOtp
     * @param  string  $code            الرمز المكوّن من 6 أرقام
     * @return array{success: bool, status: string, message: string}
     */
    public function verifyOtp(int $verificationId, string $code): array
    {
        // وضع المحاكاة (Development)
        if ($this->simulation) {
            // في الـ Simulation، اقبل الكود 123456 دائماً
            $approved = ($code === '123456');
            Log::info("[SaeiOTP][SIMULATION] Verify code={$code} for verification_id={$verificationId}, result=" . ($approved ? 'approved' : 'denied'));
            return [
                'success' => true,
                'status'  => $approved ? 'approved' : 'denied',
                'message' => $approved ? 'otp_approved' : 'otp_denied',
            ];
        }

        if (empty($this->apiKey)) {
            Log::error('[SaeiOTP] Missing SAEI_API_KEY for verifyOtp');
            return [
                'success' => false,
                'status'  => 'error',
                'message' => 'missing_credentials',
            ];
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$this->baseUrl}/v1/verify/check", [
                    'verification_id' => $verificationId,
                    'code'            => $code,
                ]);

            $data = $response->json();

            if ($response->successful()) {
                $status = $data['status'] ?? 'denied'; // approved | denied | expired

                Log::info('[SaeiOTP] Verify result', [
                    'verification_id' => $verificationId,
                    'status'          => $status,
                ]);

                return [
                    'success' => true,
                    'status'  => $status,
                    'message' => "otp_{$status}",
                ];
            }

            // خطأ من ساعي
            Log::warning('[SaeiOTP] verifyOtp failed', [
                'verification_id' => $verificationId,
                'status'          => $response->status(),
                'response'        => $data,
            ]);

            return [
                'success' => false,
                'status'  => 'error',
                'message' => $data['message'] ?? 'verify_failed',
            ];

        } catch (\Exception $e) {
            Log::error('[SaeiOTP] verifyOtp exception', [
                'verification_id' => $verificationId,
                'error'           => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status'  => 'error',
                'message' => 'network_error',
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 3. التحقق من توقيع Callback الوارد من ساعي
    //    X-OTP-Signature: sha256=HMAC(secret, body)
    //    X-OTP-Timestamp: unix_timestamp
    // ─────────────────────────────────────────────────────────────

    /**
     * التحقق من أن Callback الوارد صادر فعلاً من ساعي
     * يجب استدعاء هذه الدالة قبل معالجة أي Callback
     *
     * @param  string  $payload    جسم الطلب الخام (raw body)
     * @param  string  $signature  قيمة هيدر X-OTP-Signature (مثال: sha256=abcdef...)
     * @param  string  $timestamp  قيمة هيدر X-OTP-Timestamp
     * @return bool
     */
    public function verifyCallbackSignature(string $payload, string $signature, string $timestamp): bool
    {
        if (empty($this->callbackSecret)) {
            // لم يتم إعداد المفتاح السري — تخطّ التحقق (غير موصى به في الإنتاج)
            Log::warning('[SaeiOTP] SAEI_CALLBACK_SECRET is not set. Skipping signature verification.');
            return true;
        }

        // التوقيع المتوقع: sha256=HMAC(secret, body)
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $this->callbackSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('[SaeiOTP] Invalid callback signature', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
            return false;
        }

        // اختياري: تحقق من أن الطلب ليس قديماً (أكثر من 5 دقائق)
        $requestTime = (int) $timestamp;
        $now         = time();
        if (abs($now - $requestTime) > 300) {
            Log::warning('[SaeiOTP] Callback timestamp is too old or in the future', [
                'timestamp' => $requestTime,
                'now'       => $now,
            ]);
            return false;
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────
    // مساعد تنسيق الرقم (E.164)
    // ─────────────────────────────────────────────────────────────

    /**
     * تحويل رقم الهاتف إلى صيغة E.164 (مثال: +966XXXXXXXXX)
     *
     * @param  string  $phone
     * @return string
     */
    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);

        // أضف + إذا لم يكن موجوداً
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . ltrim($phone, '0');
        }

        return $phone;
    }
}
