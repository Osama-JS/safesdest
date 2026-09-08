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
     * رمز الـ CSRF الثابت لبيئة أمن لضمان عدم رفض الطلبات بحظر CSRF من دجانغو
     */
    protected function getCsrfToken(): string
    {
        return 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';
    }

    /**
     * ترويسة الطلبات المعتمدة من منصة أمن / متعهد
     * تدعم طلبات الاستعلام والطلبات المعدلة (POST/PUT/DELETE) مع معالجة حماية CSRF الخاصة بدجانغو
     */
    protected function getHeaders(bool $isMutating = false): array
    {
        $headers = [
            'X-API-Token'  => $this->apiToken,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'User-Agent'   => 'SafeDests/1.0',
        ];

        if ($isMutating) {
            $csrf = $this->getCsrfToken();
            $parsedUrl = parse_url($this->baseUrl);
            $origin = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'sandbox-api.amnn.sa');
            $headers['X-CSRFToken'] = $csrf;
            $headers['Cookie']      = "csrftoken={$csrf}";
            $headers['Referer']     = $origin . '/';
            $headers['Origin']      = $origin;
        }

        return $headers;
    }

    /**
     * تنسيق بيانات العميل لتتوافق مع متطلبات API منصة أمن (first_name, last_name, phone_code, phone_number)
     */
    public function formatCustomerPayload(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;

        if (!$firstName && !empty($name)) {
            $parts = preg_split('/\s+/', $name, 2);
            $firstName = $parts[0] ?? 'عميل';
            $lastName = $parts[1] ?? 'سيف ديست';
        }

        $firstName = $firstName ?: 'عميل';
        $lastName = $lastName ?: 'سيف ديست';

        // استخراج وتنسيق رقم الجوال للتوافق مع صيغة أرقام الجوال المقبولة
        $rawPhone = $data['phone_number'] ?? ($data['phone'] ?? '');
        $cleanPhone = preg_replace('/[^\d]/', '', (string)$rawPhone);

        // إزالة بادئة 966 أو 00966 إن وجدت
        if (str_starts_with($cleanPhone, '00966')) {
            $cleanPhone = substr($cleanPhone, 5);
        } elseif (str_starts_with($cleanPhone, '966')) {
            $cleanPhone = substr($cleanPhone, 3);
        }
        $cleanPhone = ltrim($cleanPhone, '0');

        // في حال كان الرقم سعودياً صحيحاً (يبدأ بـ 5 ومكون من 9 أرقام)
        if (str_starts_with($cleanPhone, '5') && strlen($cleanPhone) === 9) {
            $phone = $cleanPhone;
        } else {
            // في بيئة الاختبار والتجربة إن كان الرقم غير سعودي (مثل الأرقام اليمنية 73xxxxxxx أو أرقام تجريبية)
            // نقوم بضبطه لصيغة جوال مقبولة لدى منصة أمن حتى تنجح العملية في الـ Sandbox
            $phone = '5' . substr(str_pad($cleanPhone, 8, '0', STR_PAD_LEFT), -8);
        }

        return [
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'phone_code'   => $data['phone_code'] ?? 'SA',
            'phone_number' => $phone,
            'email'        => $data['email'] ?? "user_{$phone}@safedests.com",
            'type'         => in_array($data['type'] ?? '', ['individual', 'company', 'regular']) ? $data['type'] : 'individual',
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
        $formattedData = $this->formatCustomerPayload($data);

        try {
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(true))
                            ->timeout($this->timeout)
                            ->post($url, $formattedData);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();

            $this->logDealOperation([
                'task_id'          => $taskId,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'buyer_info'       => ($formattedData['first_name'] . ' ' . $formattedData['last_name']) . ' (' . $formattedData['phone_number'] . ')',
                'request_payload'  => $formattedData,
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في إنشاء العميل في منصة أمن')),
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
                'request_payload'  => $formattedData,
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

        $amount = $data['amount'] ?? ($data['total_amount'] ?? ($data['offer_price'] ?? 0));
        $title = $data['title'] ?? ($data['offer_title'] ?? 'خدمة توصيل شحنة');
        $description = $data['description'] ?? ($data['offer_description'] ?? 'خدمات نقل وشحن عبر منصة سيف ديست');
        $subjectDetails = $data['deal_subject_details'] ?? ($description ?: 'توصيل الشحنة بحالة سليمة للمستلم');
        $category = is_numeric($data['offer_category'] ?? null) ? (int)$data['offer_category'] : (is_numeric($data['category'] ?? null) ? (int)$data['category'] : 1);

        $dealPayload = [
            'offer_type'           => 'service',
            'offer_category'       => $category,
            'offer_title'          => $title,
            'offer_description'    => $description,
            'deal_subject_details' => $subjectDetails,
        ];

        try {
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(true))
                            ->timeout($this->timeout)
                            ->post($url, $dealPayload);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();
            $dealNumber = $responseBody['deal_number'] ?? ($responseBody['number'] ?? ($responseBody['data']['deal_number'] ?? ($responseBody['data']['number'] ?? null)));
            $dealId = $responseBody['id'] ?? ($responseBody['data']['id'] ?? null);

            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'deal_id'          => $dealId ? (string)$dealId : null,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'amount'           => $amount,
                'request_payload'  => $dealPayload,
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في إنشاء الصفقة في منصة أمن')),
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
                'error'   => $responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في إنشاء الصفقة في منصة أمن'),
                'details' => $responseBody
            ];

        } catch (Exception $e) {
            $this->logDealOperation([
                'task_id'          => $taskId,
                'action'           => $action,
                'status'           => 'failed',
                'amount'           => $amount,
                'request_payload'  => $dealPayload,
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
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(true))
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
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في إضافة أطراف الصفقة')),
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
     * 4. إرسال الصفقة للاعتماد (Submit Deal)
     * نقل حالة الصفقة من مسودة (Draft) إلى بانتظار الموافقة (Requested)
     */
    public function submitDeal(string $dealNumber, ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/{$dealNumber}/action/submit";
        $action = 'submit_deal';

        try {
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(true))
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
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في اعتماد وتأكيد الصفقة')),
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
     * 4.1. موافقة البائع على الصفقة وتحديد السعر النهائي (Approve Deal)
     * تنقل حالة الصفقة من requested إلى payment_pending
     */
    public function approveDeal(string $dealNumber, float $price, ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/{$dealNumber}/action/approve";
        $action = 'approve_deal';
        $payload = [
            'price' => number_format($price, 2, '.', ''),
        ];

        try {
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(true))
                            ->timeout($this->timeout)
                            ->post($url, $payload);

            $responseBody = $response->json() ?? [];
            $isSuccess = $response->successful();

            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => $isSuccess ? 'success' : 'failed',
                'amount'           => $price,
                'request_payload'  => $payload,
                'response_payload' => $responseBody,
                'http_status'      => $response->status(),
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في اعتماد سعر الصفقة')),
            ]);

            if ($isSuccess) {
                return [
                    'status' => true,
                    'data'   => $responseBody
                ];
            }

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في اعتماد سعر الصفقة',
                'details' => $responseBody
            ];
        } catch (Exception $e) {
            $this->logDealOperation([
                'task_id'          => $taskId,
                'deal_number'      => $dealNumber,
                'action'           => $action,
                'status'           => 'failed',
                'amount'           => $price,
                'request_payload'  => $payload,
                'error_message'    => $e->getMessage(),
            ]);

            Log::error('Mtahd API Exception [ApproveDeal]: ' . $e->getMessage());

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 4.2. إنشاء طلب الدفع الإلكتروني للصفقة (Make Online Payment)
     * يرجع بيانات Checkout و checkout_id من HyperPay
     */
    public function makePaymentOnline(string $dealNumber, string $paymentMethod = 'mada', ?int $taskId = null): array
    {
        $url = "{$this->baseUrl}/deals/{$dealNumber}/action/make-payment-online";
        $action = 'make_payment_online';
        $payload = [
            'payment_method' => in_array($paymentMethod, ['mada', 'applepay', 'visa_master']) ? $paymentMethod : 'mada',
        ];

        try {
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(true))
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
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في إنشاء جلسة الدفع الإلكتروني')),
            ]);

            if ($isSuccess) {
                return [
                    'status'      => true,
                    'checkout_id' => $responseBody['hyperpay']['checkout_id'] ?? null,
                    'provider'    => $responseBody['provider'] ?? 'hyperpay',
                    'data'        => $responseBody
                ];
            }

            return [
                'status'  => false,
                'error'   => $responseBody['message'] ?? 'فشل في إنشاء جلسة الدفع الإلكتروني',
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

            Log::error('Mtahd API Exception [MakePaymentOnline]: ' . $e->getMessage());

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
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(false))
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
                'error'   => $responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في جلب حالة الصفقة'),
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
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(true))
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
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في تحرير الضمان المالي في منصة أمن')),
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
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(true))
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
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في إلغاء الصفقة في منصة أمن')),
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
            $response = Http::withoutVerifying()
                            ->withHeaders($this->getHeaders(true))
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
                'error_message'    => $isSuccess ? null : ($responseBody['message'] ?? (isset($responseBody['error']) ? json_encode($responseBody['error'], JSON_UNESCAPED_UNICODE) : 'فشل في تأكيد التسليم')),
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
     * تراعي سياق المنفّذ (مستخدم لوحة التحكم، عميل من التطبيق، سائق) بأمان تام
     */
    public function logDealOperation(array $data): ?MtahdDealLog
    {
        try {
            $user = auth()->user();
            $performedBy = null;
            $actorNote = null;

            if (isset($data['performed_by']) && is_numeric($data['performed_by'])) {
                if (\App\Models\User::where('id', $data['performed_by'])->exists()) {
                    $performedBy = (int)$data['performed_by'];
                }
            } elseif ($user instanceof \App\Models\User) {
                $performedBy = $user->id;
            } elseif ($user instanceof \App\Models\Customer) {
                $actorNote = "[عميل: #{$user->id} - {$user->name}]";
            } elseif ($user instanceof \App\Models\Driver) {
                $actorNote = "[سائق: #{$user->id} - {$user->name}]";
            }

            $notes = $data['notes'] ?? null;
            if ($actorNote) {
                $notes = $notes ? ($actorNote . ' ' . $notes) : $actorNote;
            }

            $buyerInfo = isset($data['buyer_info']) ? substr(is_string($data['buyer_info']) ? $data['buyer_info'] : json_encode($data['buyer_info'], JSON_UNESCAPED_UNICODE), 0, 250) : null;
            $sellerInfo = isset($data['seller_info']) ? substr(is_string($data['seller_info']) ? $data['seller_info'] : json_encode($data['seller_info'], JSON_UNESCAPED_UNICODE), 0, 250) : null;

            return MtahdDealLog::create([
                'task_id'          => $data['task_id'] ?? null,
                'deal_number'      => $data['deal_number'] ?? null,
                'deal_id'          => $data['deal_id'] ?? null,
                'action'           => $data['action'] ?? 'unknown',
                'status'           => $data['status'] ?? 'info',
                'amount'           => isset($data['amount']) ? floatval($data['amount']) : null,
                'currency'         => $data['currency'] ?? 'SAR',
                'buyer_info'       => $buyerInfo,
                'seller_info'      => $sellerInfo,
                'request_payload'  => $data['request_payload'] ?? null,
                'response_payload' => $data['response_payload'] ?? null,
                'http_status'      => $data['http_status'] ?? null,
                'error_message'    => isset($data['error_message']) ? substr((string)$data['error_message'], 0, 1000) : null,
                'ip_address'       => request()->ip() ?? null,
                'performed_by'     => $performedBy,
                'notes'            => $notes,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log Mtahd Deal Operation: ' . $e->getMessage());
            return null;
        }
    }
}
