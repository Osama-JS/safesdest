<?php

namespace App\Services;

use App\Models\Settings;
use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * خدمة الربط المتكاملة مع منصة Go-Tech للتمويل اللوجستي والاستثمار
 * Go-Tech Logistics Investment & Funding Service
 */
class GoTechService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $webhookSecret;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (Settings::getValue('gotech_base_url')
            ?: config('services.gotech.base_url', env('GOTECH_BASE_URL', 'http://127.0.0.1:8000/api/v1'))), '/');

        $this->apiKey = (string) (Settings::getValue('gotech_api_key')
            ?: config('services.gotech.api_key', env('GOTECH_API_KEY', '')));

        $this->webhookSecret = (string) (Settings::getValue('gotech_webhook_secret')
            ?: config('services.gotech.webhook_secret', env('GOTECH_WEBHOOK_SECRET', '')));

        $this->timeout = 25; // seconds
    }

    /**
     * التحقق مما إذا كانت خدمة Go-Tech مفعلة في الإعدادات
     */
    public static function isServiceEnabled(): bool
    {
        $val = Settings::getValue('gotech_enabled', '1');
        return $val !== '0' && $val !== 0 && $val !== false && $val !== 'false';
    }

    /**
     * إرجاع ترويسات الطلب المعتمدة لـ Go-Tech API
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Api-Key'     => $this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'SafeDests-Logistics/1.0',
        ];
    }

    /**
     * 1. فحص الاتصال بالبوابة البرمجية (Gateway Ping & Health Check & Auth Verification)
     */
    public function ping(): array
    {
        try {
            $url = "{$this->baseUrl}/ping";
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            if ($response->successful()) {
                // التحقق أيضاً من صلاحية مفتاح الـ API إذا كان مدخلاً
                if (!empty($this->apiKey)) {
                    $authCheck = Http::timeout($this->timeout)
                        ->withHeaders($this->getHeaders())
                        ->get("{$this->baseUrl}/tasks?per_page=1");

                    if ($authCheck->failed()) {
                        $authErr = $authCheck->json('error') ?? $authCheck->json('message') ?? 'مفتاح الـ API غير صالح أو غير معتمد';
                        return [
                            'success' => false,
                            'status'  => 0,
                            'code'    => $authCheck->status(),
                            'message' => "تم الوصول لبوابة Go-Tech ولكن فشل التحقق من مفتاح الـ API: {$authErr}",
                            'details' => $authCheck->json(),
                        ];
                    }
                }

                return [
                    'success' => true,
                    'status'  => 1,
                    'message' => 'تم الاتصال ببوابة Go-Tech API بنجاح ومفتاح الـ API معتمد وجاهز للعمل.',
                    'data'    => $response->json(),
                ];
            }

            $statusCode = $response->status();
            $errMessage = $response->json('error') ?? $response->json('message');

            if (!$errMessage) {
                if ($statusCode === 404) {
                    $errMessage = "كود 404 (الصفحة أو المسار غير موجود). تأكد من صحة الرابط Base URL (في بيئة XAMPP تأكد من وجود مجلد public/api/v1 والمسار الصحيح للمشروع).";
                } elseif ($statusCode === 401) {
                    $errMessage = "كود 401: غير مصرح. مفتاح الـ API غير صالح أو مفقود.";
                } elseif ($statusCode === 403) {
                    $errMessage = "كود 403: الحساب معطل أو غير مصرح له بالوصول.";
                } elseif ($statusCode >= 500) {
                    $errMessage = "كود {$statusCode}: خطأ داخلي في خادم Go-Tech.";
                } else {
                    $errMessage = "فشل الاتصال بـ Go-Tech: استجابة غير متوقعة (رمز الاستجابة: {$statusCode})";
                }
            }

            return [
                'success' => false,
                'status'  => 0,
                'code'    => $statusCode,
                'message' => $errMessage,
                'details' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('GoTechService::ping Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'status'  => 0,
                'message' => 'تعذر الاتصال بخادم Go-Tech: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 2. إرسال مهمة جديدة إلى Go-Tech لطرحها للتمويل
     */
    public function submitTask(Task $task, array $customData = []): array
    {
        if (!self::isServiceEnabled()) {
            return ['status' => 0, 'success' => false, 'message' => 'خدمة Go-Tech معطلة حالياً في إعدادات المنصة.'];
        }

        if (empty($this->apiKey)) {
            return ['status' => 0, 'success' => false, 'message' => 'مفتاح الـ API الخاص بـ Go-Tech غير مضبوط في الإعدادات.'];
        }

        try {
            // Load required relationships if not loaded
            $task->loadMissing(['pickup', 'delivery', 'vehicle_size.type.vehicle', 'customer']);

            $pickup = $task->pickup;
            $delivery = $task->delivery;

            $pickupCity = (!empty($pickup?->city) && $pickup?->city !== 'غير محدد') ? $pickup->city : ($task->customer?->city ?: 'الرياض');
            $pickupAddress = $pickup?->address ?: ($pickup?->contact_name ?: "موقع الاستلام في {$pickupCity}");
            $dropoffCity = (!empty($delivery?->city) && $delivery?->city !== 'غير محدد') ? $delivery->city : 'جدة';
            $dropoffAddress = $delivery?->address ?: ($delivery?->contact_name ?: "موقع التسليم في {$dropoffCity}");

            $vehicleSizeName = $task->vehicle_size?->name ?? '';
            $vehicleTypeName = $task->vehicle_size?->type?->name ?? '';
            $truckName = $task->vehicle_size?->type?->vehicle?->name ?? '';
            $vehicleLabel = trim("{$truckName} {$vehicleTypeName} {$vehicleSizeName}");
            if (empty($vehicleLabel)) {
                $vehicleLabel = 'مركبة نقل لوجستي';
            }

            $externalTaskId = 'TASK-' . $task->id;
            $fundingAmount = (float) ($customData['funding_amount'] ?? $task->total_price ?? 100);
            $expiresMinutes = (int) ($customData['expires_in_minutes'] ?? Settings::getValue('gotech_default_duration_minutes', 120));

            $payload = [
                'external_task_id'        => $externalTaskId,
                'title'                   => $customData['title'] ?? ("مهمة نقل: " . ($task->customer_task_number ?: '#' . $task->id) . " - " . $vehicleLabel),
                'description'             => $customData['description'] ?? "مهمة نقل لوجستي من {$pickupCity} إلى {$dropoffCity} لصالح عميل SafeDests",
                'pickup_city'             => $pickupCity,
                'pickup_address'          => $pickupAddress,
                'pickup_lat'              => $pickup?->lat ? (float) $pickup->lat : null,
                'pickup_lng'              => $pickup?->lng ? (float) $pickup->lng : null,
                'dropoff_city'            => $dropoffCity,
                'dropoff_address'         => $dropoffAddress,
                'dropoff_lat'             => $delivery?->lat ? (float) $delivery->lat : null,
                'dropoff_lng'             => $delivery?->lng ? (float) $delivery->lng : null,
                'funding_amount'          => $fundingAmount,
                'expires_in_minutes'      => $expiresMinutes,
                'estimated_delivery_time' => $customData['estimated_delivery_time'] ?? '24-48 ساعة',
                'metadata'                => [
                    'safedests_task_id'     => $task->id,
                    'customer_task_number'  => $task->customer_task_number,
                    'customer_name'         => $task->customer?->name ?? 'عميل سيف ديست',
                    'vehicle_label'         => $vehicleLabel,
                    'submitted_at'          => now()->toIso8601String(),
                ],
            ];

            $url = "{$this->baseUrl}/tasks";
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($url, $payload);

            $resJson = $response->json();

            if ($response->successful() && ($resJson['success'] ?? false)) {
                $data = $resJson['data'] ?? [];
                
                // Update local task record
                $task->update([
                    'gotech_task_id'        => $data['id'] ?? null,
                    'gotech_task_number'    => $data['task_number'] ?? null,
                    'gotech_status'         => $data['status'] ?? 'pending_funding',
                    'gotech_funding_amount' => $fundingAmount,
                    'gotech_metadata'       => array_merge($task->gotech_metadata ?? [], [
                        'submitted_payload' => $payload,
                        'submission_res'    => $data,
                        'submitted_at'      => now()->toIso8601String(),
                    ]),
                ]);

                Log::info("GoTechService: Task #{$task->id} submitted successfully. GoTech Task Number: " . ($data['task_number'] ?? ''));

                return [
                    'success'     => true,
                    'status'      => 1,
                    'message'     => 'تم إرسال المهمة بنجاح إلى منصة Go-Tech وطرحها في سوق التمويل!',
                    'gotech_task' => $data,
                ];
            }

            // Conflict / Already exists
            if ($response->status() === 409) {
                $task->update([
                    'gotech_status'   => $task->gotech_status ?: 'available',
                    'gotech_metadata' => array_merge($task->gotech_metadata ?? [], [
                        'synced_on_duplicate_at' => now()->toIso8601String(),
                        'conflict_details'       => $resJson,
                    ]),
                ]);

                return [
                    'success' => false,
                    'status'  => 2,
                    'message' => 'هذه المهمة مسجلة مسبقاً في منصة Go-Tech (تمت مزامنة حالتها تلقائياً لمنع تكرار الإرسال).',
                    'details' => $resJson,
                ];
            }

            $errMsg = $resJson['message'] ?? 'فشل إرسال المهمة إلى Go-Tech';
            if (!empty($resJson['errors']) && is_array($resJson['errors'])) {
                $errStrings = [];
                foreach ($resJson['errors'] as $errKey => $errVal) {
                    $errStrings[] = is_array($errVal) ? implode(' ', $errVal) : $errVal;
                }
                if (!empty($errStrings)) {
                    $errMsg .= ' (' . implode(' | ', $errStrings) . ')';
                }
            }

            return [
                'success' => false,
                'status'  => 0,
                'code'    => $response->status(),
                'message' => $errMsg,
                'errors'  => $resJson['errors'] ?? null,
                'details' => $resJson,
            ];

        } catch (Exception $e) {
            Log::error("GoTechService::submitTask Exception for Task #{$task->id}: " . $e->getMessage());
            return [
                'success' => false,
                'status'  => 0,
                'message' => 'حدث خطأ أثناء الاتصال بمنصة Go-Tech: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 3. الاستعلام عن حالة المهمة والتمويل من Go-Tech
     */
    public function checkTaskStatus(string|int $identifier): array
    {
        try {
            $url = "{$this->baseUrl}/tasks/{$identifier}/status";
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            $resJson = $response->json();
            if ($response->successful() && ($resJson['success'] ?? false)) {
                return [
                    'success' => true,
                    'status'  => 1,
                    'data'    => $resJson['data'],
                ];
            }

            return [
                'success' => false,
                'status'  => 0,
                'message' => $resJson['message'] ?? 'تعذر العثور على حالة المهمة في Go-Tech',
            ];
        } catch (Exception $e) {
            Log::error("GoTechService::checkTaskStatus Exception: " . $e->getMessage());
            return ['success' => false, 'status' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * 4. إلغاء المهمة في Go-Tech وسحبها من سوق المستثمرين
     */
    public function cancelTask(string|int $identifier): array
    {
        try {
            $url = "{$this->baseUrl}/tasks/{$identifier}/cancel";
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($url);

            $resJson = $response->json();
            if ($response->successful() && ($resJson['success'] ?? false)) {
                return [
                    'success' => true,
                    'status'  => 1,
                    'message' => 'تم إلغاء المهمة في Go-Tech بنجاح وسحبها من سوق المستثمرين.',
                    'data'    => $resJson['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'status'  => 0,
                'message' => $resJson['message'] ?? 'فشل إلغاء المهمة في Go-Tech',
            ];
        } catch (Exception $e) {
            Log::error("GoTechService::cancelTask Exception: " . $e->getMessage());
            return ['success' => false, 'status' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * 5. إشعار Go-Tech باكتمل وتسليم المهمة
     */
    public function completeTask(string|int $identifier): array
    {
        try {
            $url = "{$this->baseUrl}/tasks/{$identifier}/complete";
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($url);

            $resJson = $response->json();
            if ($response->successful() && ($resJson['success'] ?? false)) {
                return [
                    'success' => true,
                    'status'  => 1,
                    'message' => 'تم تأكيد اكتمال المهمة في Go-Tech بنجاح.',
                    'data'    => $resJson['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'status'  => 0,
                'message' => $resJson['message'] ?? 'فشل إشعار إكمال المهمة في Go-Tech',
            ];
        } catch (Exception $e) {
            Log::error("GoTechService::completeTask Exception: " . $e->getMessage());
            return ['success' => false, 'status' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * 6. تحديث بيانات التتبع والإحداثيات اللحظية للمهمة في Go-Tech
     */
    public function updateTracking(string|int $identifier, array $trackingData): array
    {
        try {
            $url = "{$this->baseUrl}/tasks/{$identifier}/update-status";
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($url, $trackingData);

            $resJson = $response->json();
            return [
                'success' => $response->successful() && ($resJson['success'] ?? false),
                'message' => $resJson['message'] ?? 'تم تحديث التتبع في Go-Tech',
                'data'    => $resJson['data'] ?? [],
            ];
        } catch (Exception $e) {
            Log::error("GoTechService::updateTracking Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 7. استعلام رصيد محفظة التمويل المتاح للمطالبة والسحب في Go-Tech
     */
    public function getFundingWallet(): array
    {
        try {
            $url = "{$this->baseUrl}/wallets/funding";
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            $resJson = $response->json();
            if ($response->successful() && ($resJson['success'] ?? false)) {
                return [
                    'success' => true,
                    'status'  => 1,
                    'data'    => $resJson['data'],
                ];
            }

            return [
                'success' => false,
                'status'  => 0,
                'message' => $resJson['message'] ?? 'تعذر جلب بيانات محفظة التمويل',
            ];
        } catch (Exception $e) {
            return ['success' => false, 'status' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * 8. استعلام الديون والالتزامات المستحقة على شركتنا في Go-Tech
     */
    public function getDebts(): array
    {
        try {
            $url = "{$this->baseUrl}/wallets/debts";
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get($url);

            $resJson = $response->json();
            if ($response->successful() && ($resJson['success'] ?? false)) {
                return [
                    'success' => true,
                    'status'  => 1,
                    'data'    => $resJson['data'],
                ];
            }

            return [
                'success' => false,
                'status'  => 0,
                'message' => $resJson['message'] ?? 'تعذر جلب بيانات الديون والالتزامات',
            ];
        } catch (Exception $e) {
            return ['success' => false, 'status' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * 9. التحقق الرقمي المشفر من صحة توقيع الـ Webhook الوارد من Go-Tech
     */
    public function verifyWebhookSignature(string $rawPayload, ?string $signature, ?string $timestamp): bool
    {
        if (empty($signature) || empty($timestamp)) {
            Log::warning('GoTech Webhook: Missing signature or timestamp header.');
            return false;
        }

        $secret = $this->webhookSecret;
        if (empty($secret)) {
            Log::warning('GoTech Webhook: Webhook secret not configured in SafeDests settings.');
            return false;
        }

        // Expected signature: hash_hmac('sha256', timestamp . '.' . payloadJson, secret)
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $rawPayload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}

