<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class HyperPayPayoutService
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $merchantId;
    protected $sourceId;

    public function __construct()
    {
        $this->baseUrl = config('services.hyperpay.payout_url', 'https://gateway.sandbox.hyperpay.com/payouts');
        $this->username = config('services.hyperpay.username');
        $this->password = config('services.hyperpay.password');
        $this->merchantId = config('services.hyperpay.merchant_id');
        $this->sourceId = config('services.hyperpay.source_id');
    }

    /**
     * Map Response Codes to Arabic Messages
     */
    protected function getResponseMessage($code, $default = 'خطأ غير معروف')
    {
        $codes = [
            '00000' => 'تمت المعالجة بنجاح',
            '00001' => 'تم استلام الطلب بنجاح',
            '33000' => 'تم جدولة عملية الدفع',
            '33333' => 'جاري معالجة العملية حالياً',
            '13000' => 'خطأ في التحقق من البيانات (تأكد من الحقول)',
            '13001' => 'رقم حساب المصدر (Source ID) غير صحيح',
            '13004' => 'البيانات المطلوبة غير موجودة',
            '13005' => 'القناة المستخدمة غير صحيحة',
            '13006' => 'المرجع (Reference) مستخدم مسبقاً',
            '63000' => 'تم رفض العملية من قبل النظام',
            '90000' => 'العملية بانتظار تأكيد البنك',
            '88888' => 'خطأ تقني في نظام HyperPay',
            '77000' => 'تم إلغاء العملية بنجاح',
        ];

        return $codes[$code] ?? $default;
    }

    /**
     * Send a payout request
     */
    public function sendPayout(array $data)
    {
        try {
            // Sanitize description: Max 35 chars, Alphanumeric only
            $description = preg_replace('/[^A-Za-z0-9 ]/', '', $data['description'] ?? 'Driver Payout');
            $description = substr($description, 0, 35);

            // Sanitize Addresses: Alphanumeric only
            $address1 = preg_replace('/[^A-Za-z0-9 ]/', '', $data['address1'] ?? 'Riyadh');
            if (empty(trim($address1))) $address1 = 'Riyadh';
            
            $address2 = preg_replace('/[^A-Za-z0-9 ]/', '', $data['address2'] ?? '.');
            if (empty(trim($address2))) $address2 = 'Street';

            $payload = [
                'merchantReference' => 'REF-' . time(),
                'sourceId'          => trim($this->sourceId),
                'payouts'           => [
                    [
                        'payoutReference' => $data['externalId'],
                        'amount'          => number_format($data['amount'], 2, '.', ''),
                        'currency'        => $data['currency'] ?? 'SAR',
                        'transferMode'    => 'INSTANT',
                        'purpose'         => $data['purpose'] ?? 'BA',
                        'description'     => $description,
                        'beneficiary'     => [
                            'name'     => $data['beneficiary_name'],
                            'address1' => $address1,
                            'address2' => $address2,
                            'country'  => $data['country'] ?? 'SA',
                            'city'     => $data['city'] ?? 'Riyadh',
                            'iban'     => str_replace(' ', '', $data['iban']),
                            'bicCode'  => $data['bic'],
                        ],
                    ]
                ]
            ];

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'X-Merchant-Id' => $this->merchantId,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->post($this->baseUrl, $payload);

            $result = $response->json();

            // Log full response for debugging
            Log::info('HyperPay Payout Response:', ['result' => $result]);

            $responseCode = $result['responseCode'] ?? 'ERROR';
            
            // Note: HyperSplits 2.0 returns payout details in an array
            $payoutDetails = $result['payouts'][0] ?? $result;
            $finalResponseCode = $payoutDetails['responseCode'] ?? $responseCode;
            $finalMessage = $payoutDetails['responseMessage'] ?? ($result['responseMessage'] ?? 'Unknown Error');

            // Specific success codes for HyperSplits 2.0
            $isSuccessCode = in_array($finalResponseCode, ['00000', '00001', '33000', '33333']);

            return [
                'status'  => $isSuccessCode,
                'code'    => $finalResponseCode,
                'message' => $this->getResponseMessage($finalResponseCode, $finalMessage),
                'data'    => $payoutDetails
            ];

        } catch (Exception $e) {
            Log::error('HyperPay Payout Exception:', ['message' => $e->getMessage()]);
            return [
                'status'  => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check payout status by payoutReference
     */
    public function checkPayoutStatus($referenceId)
    {
        try {
            $url = config('services.hyperpay.payout_url', 'https://gateway.sandbox.hyperpay.com/payouts');
            // The doc says endpoint is `/payout` for GET. If `payout_url` ends with `/payouts`, we need to change to `/payout`
            $url = str_replace('/payouts', '/payout', $url);
            
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'X-Merchant-Id' => $this->merchantId,
                    'Accept'        => 'application/json',
                ])
                ->get($url, [
                    'merchantId'       => $this->merchantId,
                    'entityId'         => $this->merchantId,
                    'payout-reference' => $referenceId
                ]);

            $result = $response->json();
            
            Log::info('HyperPay Check Status Response:', ['result' => $result]);
            
            $responseCode = $result['responseCode'] ?? 'ERROR';
            $message = $result['responseMessage'] ?? 'Unknown Error';
            
            // Note: HyperSplits 2.0 GET response
            $isSuccessCode = in_array($responseCode, ['00000', '00001', '33000', '33333']);

            return [
                'status'  => $isSuccessCode,
                'code'    => $responseCode,
                'message' => $this->getResponseMessage($responseCode, $message),
                'data'    => $result
            ];

        } catch (Exception $e) {
            Log::error('HyperPay Check Status Exception:', ['message' => $e->getMessage()]);
            return [
                'status'  => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
}
