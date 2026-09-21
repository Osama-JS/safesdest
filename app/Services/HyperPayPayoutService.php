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
    protected $defaultPurpose;

    public function __construct()
    {
        $rawUrl = config('services.hyperpay.payout_url', 'https://gateway.sandbox.hyperpay.com/payouts');
        $rawUrl = rtrim($rawUrl, '/');
        if (!str_ends_with($rawUrl, '/payouts') && !str_ends_with($rawUrl, '/payout')) {
            $rawUrl .= '/payouts';
        }
        $this->baseUrl = $rawUrl;
        $this->username = config('services.hyperpay.username');
        $this->password = config('services.hyperpay.password');
        $this->merchantId = config('services.hyperpay.merchant_id');
        $this->sourceId = config('services.hyperpay.source_id');
        $this->defaultPurpose = config('services.hyperpay.purpose', 'BA');
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
            '13008' => 'رمز الغرض من التحويل (Purpose Code) غير صالح',
            '63000' => 'تم رفض العملية من قبل النظام',
            '90000' => 'العملية بانتظار تأكيد البنك',
            '88888' => 'خطأ تقني في نظام HyperPay',
            '77000' => 'تم إلغاء العملية بنجاح',
        ];

        return $codes[$code] ?? $default;
    }

    /**
     * Convert/sanitize beneficiary name to valid Latin alphanumeric format for banking APIs
     */
    public static function formatBeneficiaryName($name)
    {
        $name = trim($name ?? '');
        if (empty($name)) {
            return 'Beneficiary';
        }

        // If name contains Arabic characters, transliterate to Latin
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $name)) {
            if (function_exists('transliterator_transliterate')) {
                $latin = transliterator_transliterate('Any-Latin; Latin-ASCII;', $name);
            } else {
                $customMap = [
                    'عبد الله' => 'Abdullah', 'عبدالله' => 'Abdullah',
                    'عبد الرحمن' => 'Abdulrahman', 'عبدالرحمن' => 'Abdulrahman',
                    'عبد العزيز' => 'Abdulaziz', 'عبدالعزيز' => 'Abdulaziz',
                    'أ' => 'A', 'إ' => 'E', 'آ' => 'A', 'ا' => 'A',
                    'ب' => 'B', 'ت' => 'T', 'ث' => 'Th', 'ج' => 'J',
                    'ح' => 'H', 'خ' => 'Kh', 'د' => 'D', 'ذ' => 'Dh',
                    'ر' => 'R', 'ز' => 'Z', 'س' => 'S', 'ش' => 'Sh',
                    'ص' => 'S', 'ض' => 'Dh', 'ط' => 'T', 'ظ' => 'Dh',
                    'ع' => 'A', 'غ' => 'Gh', 'ف' => 'F', 'ق' => 'Q',
                    'ك' => 'K', 'ل' => 'L', 'م' => 'M', 'ن' => 'N',
                    'ه' => 'H', 'ة' => 'H', 'و' => 'W', 'ي' => 'Y',
                    'ى' => 'A', 'ئ' => 'Y', 'ء' => '', 'ؤ' => 'O'
                ];
                $latin = str_replace(array_keys($customMap), array_values($customMap), $name);
            }

            $cleaned = preg_replace('/[^a-zA-Z0-9\s\.\,\'-]/', '', $latin);
            $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));

            if (!empty($cleaned)) {
                $name = ucwords(strtolower($cleaned));
            }
        }

        // Strictly keep valid characters: Letters, numbers, space, dot, hyphen
        $name = preg_replace('/[^a-zA-Z0-9\s\.\'-]/', '', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        if (empty($name) || strlen($name) < 2) {
            $name = 'Beneficiary Account';
        }

        return substr($name, 0, 70);
    }

    /**
     * Send a payout request
     */
    public function sendPayout(array $data)
    {
        try {
            $beneficiaryName = self::formatBeneficiaryName($data['beneficiary_name'] ?? '');

            // Sanitize description: Max 35 chars, Alphanumeric and spaces only (NO dashes or special characters)
            $cleanDesc = trim(preg_replace('/[^A-Za-z0-9 ]/', ' ', $data['description'] ?? ''));
            $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);
            if (empty($cleanDesc) || strlen($cleanDesc) < 4 || in_array(strtolower($cleanDesc), ['manual payout for', 'payout for', 'wallet payment for'])) {
                $cleanExtId = preg_replace('/[^A-Za-z0-9]/', '', $data['externalId'] ?? '');
                $cleanDesc = 'Payout ' . ($beneficiaryName ?: $cleanExtId);
            }
            // Strict sanitization: ensure ONLY A-Za-z0-9 and space, max 35 chars
            $cleanDesc = trim(preg_replace('/[^A-Za-z0-9 ]/', '', $cleanDesc));
            $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);
            if (empty($cleanDesc)) {
                $cleanDesc = 'Payout Driver';
            }
            $description = substr($cleanDesc, 0, 35);

            // Sanitize Addresses: Alphanumeric and spaces only
            $address1 = trim(preg_replace('/[^A-Za-z0-9 ]/', ' ', $data['address1'] ?? 'Riyadh'));
            $address1 = preg_replace('/\s+/', ' ', $address1);
            if (empty($address1)) $address1 = 'Riyadh';
            
            $address2 = trim(preg_replace('/[^A-Za-z0-9 ]/', ' ', $data['address2'] ?? 'Street'));
            $address2 = preg_replace('/\s+/', ' ', $address2);
            if (empty($address2)) $address2 = 'Street';

            // Purpose code for bank transfer (Default to BA / SALA)
            $purpose = !empty($data['purpose']) ? trim($data['purpose']) : $this->defaultPurpose;
            if (empty($purpose)) {
                $purpose = 'BA';
            }

            $payload = [
                'merchantReference' => 'REF-' . time(),
                'sourceId'          => trim($this->sourceId),
                'payouts'           => [
                    [
                        'payoutReference' => $data['externalId'],
                        'amount'          => number_format($data['amount'], 2, '.', ''),
                        'currency'        => $data['currency'] ?? 'SAR',
                        'transferMode'    => 'INSTANT',
                        'channel'         => $data['channel'] ?? 'IPS',
                        'purpose'         => (string) $purpose,
                        'description'     => $description,
                        'beneficiary'     => [
                            'name'     => $beneficiaryName,
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

            // Log outgoing payload for clear audit & debugging
            Log::info('HyperPay Payout Sending Request:', [
                'url'         => $this->baseUrl,
                'merchant_id' => $this->merchantId,
                'source_id'   => $this->sourceId,
                'payload'     => $payload
            ]);

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

            $rootResult = $result['result'] ?? $result;
            $responseCode = $rootResult['responseCode'] ?? ($result['responseCode'] ?? 'ERROR');
            
            // Note: HyperSplits 2.0 returns payout details in an array
            $payoutDetails = $rootResult['payouts'][0] ?? ($result['payouts'][0] ?? $rootResult);
            $finalResponseCode = $payoutDetails['responseCode'] ?? $responseCode;
            $finalMessage = $payoutDetails['responseMessage'] ?? ($rootResult['responseMessage'] ?? ($result['responseMessage'] ?? 'Unknown Error'));

            $errors = $rootResult['errors'] ?? ($result['errors'] ?? null);
            if (!empty($errors) && is_array($errors)) {
                $errorText = collect($errors)->flatten()->implode(' | ');
                $finalMessage .= ': ' . $errorText;
            }

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
    public function checkPayoutStatus($referenceId, $payoutId = null)
    {
        try {
            $url = str_replace('/payouts', '/payout', $this->baseUrl);
            
            $payload = [
                'merchantId'       => $this->merchantId,
                'entityId'         => $this->merchantId,
                'payout-reference' => $referenceId
            ];
            
            if ($payoutId) {
                $payload['payout-id'] = $payoutId;
            }

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'X-Merchant-Id' => $this->merchantId,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->send('GET', $url, [
                    'query' => $payload,
                    'json'  => $payload
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
