<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\MtahdDealLog;
use Exception;

/**
 * خدمة الربط المتكاملة مع منصة مُتعهد (Amnn / Mtahd)
 * لحسابات الضمان المالي والوساطة في الصفقات (Escrow Services)
 */
class MtahdService
{
    protected string $baseUrl;
    protected string $apiToken;
    protected string $webhookSecret;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = \App\Models\Settings::getValue('mtahd_base_url') 
                      ?: config('services.mtahd.base_url', env('MTAHD_BASE_URL', 'https://sandbox-api.amnn.sa/api/v1'));
        $this->apiToken = \App\Models\Settings::getValue('mtahd_api_token') 
                       ?: config('services.mtahd.api_token', env('MTAHD_API_TOKEN', 'c2199c8e0d00d9fca3f86c14c95050798174ddeb94f8b760cd1b66dcd5bb4922'));
        $this->webhookSecret = \App\Models\Settings::getValue('mtahd_webhook_secret') 
                            ?: config('services.mtahd.webhook_secret', env('MTAHD_WEBHOOK_SECRET', ''));
        $this->timeout = 30; // seconds
    }

    /**
     * التحقق مما إذا كانت خدمة متعهد مفعلة من إعدادات النظام
     */
    public static function isServiceEnabled(): bool
    {
        $val = \App\Models\Settings::getValue('mtahd_enabled', '1');
        return $val !== '0' && $val !== 0 && $val !== false;
    }

    /**
     * ترويسة الطلبات المعتمدة من منصة أمن / متعهد
     */
    protected function getHeaders(): array
    {
        return [
            'X-API-Token'  => $this->apiToken,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    /**
     * 1. إنشاء عميل جديد (Create Customer)
     * يتم إنشاء المشتري والبائع في منصة أمن قبل ربطهما بالصفقة
     */
    public function createCustomer(array $data, ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/customers/";
        $action = 'create_customer';

        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout($this->timeout)
                            ->post($url, $data);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();

            $this->logDealOperation([
                'task_id'          => $taskId,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'buyer_info'       => $data['phone_number'] ?? ($data['name'] ?? null),
                'request_payload'  => $data,
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? 'فشل في إنشاء العميل في منصة أمن'),
            ]);

            if ($isSuccess) {
                return [
                    'status' => true,
                    'data'   => $responseBody
                ];
            }

            Log::error('Mtahd API Create Customer Error', [
                'status'   => $response->status(),
                'response' => $responseBody
            ]);

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في إنشاء العميل في منصة أمن',
                'details' => $responseBody
            ];

        } catch (Exception $e) {
            $this->logDealOperation([
                'task_id'          => $taskId,
                'action'           => $action,
                'status'           => 'failed',
                'request_payload'  => $data,
                'error_message'    => $e->getMessage(),
            ]);

            Log::error('Mtahd API Exception [CreateCustomer]: ' . $e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 2. إنشاء مسودة صفقة جديدة (Create Deal)
     */
    public function createDeal(array $data, ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/";
        $action = 'create_deal';

        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout($this->timeout)
                            ->post($url, $data);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();
            $dealNumber = $responseBody['deal_number'] ?? ($responseBody['data']['deal_number'] ?? null);
            $dealId = $responseBody['id'] ?? ($responseBody['data']['id'] ?? null);
            $amount = $data['amount'] ?? ($data['total_amount'] ?? null);

            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'deal_id'          => $dealId ? (string)$dealId : null,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'amount'           => $amount,
                'request_payload'  => $data,
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? 'فشل في إنشاء الصفقة في منصة أمن'),
            ]);

            if ($isSuccess) {
                return [
                    'status'      => true,
                    'deal_number' => $dealNumber,
                    'deal_id'     => $dealId,
                    'data'        => $responseBody
                ];
            }

            Log::error('Mtahd API Create Deal Error', [
                'status'   => $response->status(),
                'response' => $responseBody
            ]);

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في إنشاء الصفقة في منصة أمن',
                'details' => $responseBody
            ];

        } catch (Exception $e) {
            $this->logDealOperation([
                'task_id'          => $taskId,
                'action'           => $action,
                'status'           => 'failed',
                'amount'           => $data['amount'] ?? null,
                'request_payload'  => $data,
                'error_message'    => $e->getMessage(),
            ]);

            Log::error('Mtahd API Exception [CreateDeal]: ' . $e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 3. إضافة أطراف الصفقة (Add Deal Parties)
     */
    public function addDealParties(string $dealNumber, array $buyers, array $sellers, ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/{$dealNumber}/parties/";
        $action = 'add_parties';
        $payload = [
            'buyers'  => $buyers,
            'sellers' => $sellers,
        ];

        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout($this->timeout)
                            ->post($url, $payload);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();

            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'request_payload'  => $payload,
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? 'فشل في إضافة أطراف الصفقة'),
            ]);

            if ($isSuccess) {
                return [
                    'status' => true,
                    'data'   => $responseBody
                ];
            }

            Log::error('Mtahd API Add Deal Parties Error', [
                'deal'     => $dealNumber,
                'status'   => $response->status(),
                'response' => $responseBody
            ]);

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في إضافة أطراف الصفقة',
                'details' => $responseBody
            ];

        } catch (Exception $e) {
            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => 'failed',
                'request_payload'  => $payload,
                'error_message'    => $e->getMessage(),
            ]);

            Log::error('Mtahd API Exception [AddDealParties]: ' . $e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 4. اعتماد الصفقة وطلب السداد (Submit Deal)
     * نقل حالة الصفقة من مسودة (Draft) إلى بانتظار الدفع (Payment Pending) وتوليد رابط السداد
     */
    public function submitDeal(string $dealNumber, ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/{$dealNumber}/action/submit";
        $action = 'submit_deal';

        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout($this->timeout)
                            ->post($url);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();

            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? 'فشل في اعتماد وتأكيد الصفقة'),
            ]);

            if ($isSuccess) {
                return [
                    'status' => true,
                    'data'   => $responseBody
                ];
            }

            Log::error('Mtahd API Submit Deal Error', [
                'deal'     => $dealNumber,
                'status'   => $response->status(),
                'response' => $responseBody
            ]);

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في اعتماد وتأكيد الصفقة',
                'details' => $responseBody
            ];

        } catch (Exception $e) {
            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => 'failed',
                'error_message'    => $e->getMessage(),
            ]);

            Log::error('Mtahd API Exception [SubmitDeal]: ' . $e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 5. الاستعلام عن تفاصيل الصفقة والتحقق من الدفع (Get Deal Details)
     */
    public function getDealDetails(string $dealNumber, bool $checkPayment = false, ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/{$dealNumber}";
        if ($checkPayment) {
            $url .= "?check_payment=true";
        }
        $action = 'get_deal';

        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout($this->timeout)
                            ->get($url);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();

            if ($isSuccess) {
                return [
                    'status' => true,
                    'data'   => $responseBody
                ];
            }

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في جلب حالة الصفقة',
                'details' => $responseBody
            ];

        } catch (Exception $e) {
            Log::error('Mtahd API Exception [GetDealDetails]: ' . $e->getMessage());
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 6. تحرير الضمان المالي وصرف المبلغ (Release Escrow Funds)
     * يتم استدعاؤها عند إتمام المهمة وتسليم الشحنة/الخدمة لتحرير المبلغ المحجوز للبائع/السائق
     */
    public function releaseFunds(string $dealNumber, array $data = [], ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/{$dealNumber}/action/release";
        $action = 'release_funds';

        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout($this->timeout)
                            ->post($url, $data);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();

            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'amount'           => $data['amount'] ?? null,
                'request_payload'  => $data,
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? 'فشل في تحرير الضمان المالي في منصة أمن'),
                'notes'            => 'تم تنفيذ طلب تحرير وصرف الضمان المالي',
            ]);

            if ($isSuccess) {
                return [
                    'status'  => true,
                    'message' => 'تم تحرير الضمان المالي بنجاح',
                    'data'    => $responseBody
                ];
            }

            Log::error('Mtahd API Release Funds Error', [
                'deal'     => $dealNumber,
                'status'   => $response->status(),
                'response' => $responseBody
            ]);

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في تحرير الضمان المالي في منصة أمن',
                'details' => $responseBody
            ];

        } catch (Exception $e) {
            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => 'failed',
                'request_payload'  => $data,
                'error_message'    => $e->getMessage(),
            ]);

            Log::error('Mtahd API Exception [ReleaseFunds]: ' . $e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 7. إلغاء الصفقة واسترداد الضمان المالي (Cancel Deal & Refund)
     * يتم استدعاؤها في حال إلغاء المهمة أو النزاع لإرجاع الأموال للمشتري
     */
    public function cancelDeal(string $dealNumber, ?string $reason = null, ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/{$dealNumber}/action/cancel";
        $action = 'cancel_deal';
        $payload = array_filter([
            'reason' => $reason ?? 'إلغاء المهمة من قبل النظام',
        ]);

        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout($this->timeout)
                            ->post($url, $payload);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();

            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'request_payload'  => $payload,
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? 'فشل في إلغاء الصفقة في منصة أمن'),
                'notes'            => 'طلب إلغاء الصفقة: ' . ($reason ?? 'لا يوجد سبب محدد'),
            ]);

            if ($isSuccess) {
                return [
                    'status'  => true,
                    'message' => 'تم إلغاء الصفقة واسترداد الضمان بنجاح',
                    'data'    => $responseBody
                ];
            }

            Log::error('Mtahd API Cancel Deal Error', [
                'deal'     => $dealNumber,
                'status'   => $response->status(),
                'response' => $responseBody
            ]);

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في إلغاء الصفقة في منصة أمن',
                'details' => $responseBody
            ];

        } catch (Exception $e) {
            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => 'failed',
                'request_payload'  => $payload,
                'error_message'    => $e->getMessage(),
            ]);

            Log::error('Mtahd API Exception [CancelDeal]: ' . $e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 8. تأكيد تسليم الخدمة/الشحنة (Deliver Deal)
     */
    public function deliverDeal(string $dealNumber, array $data = [], ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/{$dealNumber}/action/deliver";
        $action = 'deliver_deal';

        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout($this->timeout)
                            ->post($url, $data);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();

            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'request_payload'  => $data,
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? 'فشل في تأكيد التسليم'),
            ]);

            if ($isSuccess) {
                return [
                    'status' => true,
                    'data'   => $responseBody
                ];
            }

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في تأكيد التسليم',
                'details' => $responseBody
            ];

        } catch (Exception $e) {
            Log::error('Mtahd API Exception [DeliverDeal]: ' . $e->getMessage());
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 9. التحقق من صحة توقيع الـ Webhook (Verify Webhook Signature)
     * للتحقق من أن الإشعار وارد فعلاً من منصة أمن / متعهد ولم يتم التلاعب به
     */
    public function verifyWebhookSignature(string $payload, ?string $signature = null, ?string $secret = null): bool
    {
        $webhookSecret = $secret ?: $this->webhookSecret;

        // إذا لم يتم ضبط الـ secret وكان التوقيع فارغاً في بيئة الاختبار (Sandbox)
        if (empty($webhookSecret)) {
            Log::warning('Mtahd Webhook: MTAHD_WEBHOOK_SECRET is empty. Verification skipped in sandbox mode.');
            return true;
        }

        if (empty($signature)) {
            return false;
        }

        // 1. حساب الـ HMAC SHA256
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // 2. التحقق الآمن من التطابق (Time-attack safe)
        if (hash_equals($expectedSignature, $signature)) {
            return true;
        }

        // 3. فحص إضافي في حال كان التوقيع مسبوقاً بـ sha256=
        if (str_starts_with($signature, 'sha256=')) {
            $cleanSignature = substr($signature, 7);
            if (hash_equals($expectedSignature, $cleanSignature)) {
                return true;
            }
        }

        // 4. فحص مطابقة التوكن المباشر في هيدر X-Webhook-Token
        if (hash_equals($webhookSecret, $signature)) {
            return true;
        }

        Log::warning('Mtahd Webhook Signature Mismatch', [
            'received' => $signature,
            'expected' => $expectedSignature
        ]);

        return false;
    }

    /**
     * دالة مساعدة لتسجيل وتوثيق جميع عمليات متعهد في جدول mtahd_deal_logs
     */
    public function logDealOperation(array $data): ?MtahdDealLog
    {
        try {
            return MtahdDealLog::create([
                'task_id'          => $data['task_id'] ?? null,
                'deal_number'      => $data['deal_number'] ?? null,
                'deal_id'          => $data['deal_id'] ?? null,
                'action'           => $data['action'] ?? 'unknown',
                'status'           => $data['status'] ?? 'info',
                'amount'           => $data['amount'] ?? null,
                'currency'         => $data['currency'] ?? 'SAR',
                'buyer_info'       => $data['buyer_info'] ?? null,
                'seller_info'      => $data['seller_info'] ?? null,
                'request_payload'  => $data['request_payload'] ?? null,
                'response_payload' => $data['response_payload'] ?? null,
                'http_status'      => $data['http_status'] ?? null,
                'error_message'    => $data['error_message'] ?? null,
                'ip_address'       => request()->ip() ?? null,
                'performed_by'     => auth()->id() ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log Mtahd Deal Operation: ' . $e->getMessage());
            return null;
        }
    }
}
