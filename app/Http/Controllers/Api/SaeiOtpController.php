<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\SaeiOtpService;

/**
 * Controller للتعامل مع خدمة OTP من منصة ساعي
 * ================================================
 * المسارات المقترحة (routes/api.php):
 *
 *   POST /api/saei/otp/send     → إرسال رمز OTP
 *   POST /api/saei/otp/verify   → التحقق من رمز OTP
 *   POST /api/saei/otp/callback → استقبال نتائج التحقق من ساعي (Webhook)
 */
class SaeiOtpController extends Controller
{
    protected SaeiOtpService $saei;

    public function __construct(SaeiOtpService $saei)
    {
        $this->saei = $saei;
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/saei/otp/send
    // Body: { "phone": "+966XXXXXXXXX" }
    // ─────────────────────────────────────────────────────────

    /**
     * إرسال رمز OTP إلى رقم الهاتف عبر واتساب
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:9'],
        ]);

        $result = $this->saei->sendOtp($validated['phone']);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => __('saei.' . $result['message'], [], 'ar') ?? $result['message'],
            ], 422);
        }

        return response()->json([
            'success'         => true,
            'message'         => 'تم إرسال رمز التحقق إلى واتساب الخاص بك',
            'verification_id' => $result['verification_id'],
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/saei/otp/verify
    // Body: { "verification_id": 123, "code": "123456" }
    // ─────────────────────────────────────────────────────────

    /**
     * التحقق من الرمز الذي أدخله المستخدم
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_id' => ['required', 'integer'],
            'code'            => ['required', 'string', 'min:4', 'max:8'],
        ]);

        $result = $this->saei->verifyOtp(
            (int) $validated['verification_id'],
            (string) $validated['code']
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'status'  => $result['status'],
                'message' => $result['message'],
            ], 422);
        }

        $isApproved = ($result['status'] === 'approved');

        return response()->json([
            'success'  => true,
            'approved' => $isApproved,
            'status'   => $result['status'],  // approved | denied | expired
            'message'  => $isApproved
                ? 'تم التحقق من رقمك بنجاح'
                : 'الرمز غير صحيح أو انتهت صلاحيته',
        ], $isApproved ? 200 : 400);
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/saei/otp/callback
    // هذا Webhook يُرسله ساعي عند كل عملية تحقق
    // الهيدرز: X-OTP-Signature, X-OTP-Timestamp
    // ─────────────────────────────────────────────────────────

    /**
     * استقبال نتيجة التحقق من ساعي (Webhook / Callback)
     * يُستخدم للتسجيل والمراقبة فقط — منطق التحقق الفعلي عبر verifyOtp()
     */
    public function callback(Request $request): JsonResponse
    {
        // 1. التحقق من التوقيع
        $rawBody  = $request->getContent();
        $signature = $request->header('X-OTP-Signature', '');
        $timestamp = $request->header('X-OTP-Timestamp', '');

        if (!$this->saei->verifyCallbackSignature($rawBody, $signature, $timestamp)) {
            Log::warning('[SaeiCallback] Invalid signature — request rejected', [
                'ip'        => $request->ip(),
                'signature' => $signature,
            ]);
            return response()->json(['error' => 'unauthorized'], 401);
        }

        // 2. تسجيل الحدث
        $payload = $request->all();

        Log::info('[SaeiCallback] OTP callback received', [
            'verification_id' => $payload['verification_id'] ?? null,
            'status'          => $payload['status'] ?? null,  // approved | denied | expired
            'to'              => $payload['to'] ?? null,
        ]);

        // 3. يمكنك هنا إضافة منطق إضافي مثل:
        //    - تحديث جلسة المستخدم
        //    - إرسال إشعار
        //    - تسجيل في قاعدة البيانات

        return response()->json(['received' => true]);
    }
}
