<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * خدمة الربط مع منصة أمن (Amnn / Mtahd)
 * لخدمات الوساطة المالية وحسابات الضمان (Escrow)
 */
class MtahdService
{
    protected $baseUrl;
    protected $apiToken;

    public function __construct()
    {
        // استخدام رابط الـ Sandbox كافتراضي
        $this->baseUrl = env('MTAHD_BASE_URL', 'https://sandbox-api.amnn.sa/api/v1');
        
        // مفتاح الربط (API Code) الخاص بالمنصة
        $this->apiToken = env('MTAHD_API_TOKEN', 'c2199c8e0d00d9fca3f86c14c95050798174ddeb94f8b760cd1b66dcd5bb4922');
    }

    /**
     * الهيدر الأساسي للطلبات حسب توثيق أمن
     */
    protected function getHeaders()
    {
        return [
            'X-API-Token' => $this->apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * 1. إنشاء عميل جديد (Customer)
     * يجب إنشاء العميل (سواء كان المشتري أو البائع) في منصة أمن قبل إنشاء العقد
     */
    public function createCustomer(array $data)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->post("{$this->baseUrl}/customers/", $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Amnn API Create Customer Error', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return ['status' => false, 'error' => 'فشل في إنشاء العميل في منصة أمن', 'details' => $response->json()];

        } catch (Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 2. إنشاء صفقة / عقد ضمان (Deal)
     */
    public function createDeal(array $data)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->post("{$this->baseUrl}/deals/", $data);

            if ($response->successful()) {
                return $response->json(); 
            }

            Log::error('Amnn API Create Deal Error', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return ['status' => false, 'error' => 'فشل في إنشاء الصفقة في منصة أمن', 'details' => $response->json()];

        } catch (Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 3. إضافة أطراف الصفقة (البائع والمشتري)
     */
    public function addDealParties($dealNumber, array $buyers, array $sellers)
    {
        try {
            $payload = [
                'buyers' => $buyers,   // مصفوفة بأرقام العملاء (Customers numbers)
                'sellers' => $sellers, // مصفوفة بأرقام العملاء
            ];

            $response = Http::withHeaders($this->getHeaders())
                            ->post("{$this->baseUrl}/deals/{$dealNumber}/parties/", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Amnn API Add Deal Parties Error', [
                'deal' => $dealNumber,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return ['status' => false, 'error' => 'فشل في إضافة أطراف الصفقة'];

        } catch (Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 4. اعتماد وتأكيد الصفقة (Submit Deal)
     * لتغيير حالة الصفقة من Draft إلى Payment_Pending
     */
    public function submitDeal($dealNumber)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->post("{$this->baseUrl}/deals/{$dealNumber}/action/submit");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Amnn API Submit Deal Error', [
                'deal' => $dealNumber,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return ['status' => false, 'error' => 'فشل في تأكيد الصفقة'];

        } catch (Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 5. الاستعلام عن تفاصيل الصفقة (Get Deal details)
     */
    public function getDealDetails($dealNumber, $checkPayment = false)
    {
        try {
            $url = "{$this->baseUrl}/deals/{$dealNumber}";
            if ($checkPayment) {
                $url .= "?check_payment=true";
            }

            $response = Http::withHeaders($this->getHeaders())
                            ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return ['status' => false, 'error' => 'فشل في جلب حالة الصفقة'];

        } catch (Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }
}
