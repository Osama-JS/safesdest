<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SignitService
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $scope;

    public function __construct()
    {
        $this->baseUrl = config('services.signit.base_url');
        $this->clientId = config('services.signit.client_id');
        $this->clientSecret = config('services.signit.client_secret');
        $this->scope = config('services.signit.scope');
    }

    /**
     * جلب Access Token مع تخزينه في الكاش حتى انتهاء صلاحيته
     */
    public function getAccessToken()
    {
        return Cache::remember('signit_access_token', $this->getTokenTtl(), function () {
            $clientId = $this->clientId;
            $clientSecret = $this->clientSecret;
            $scope = 'signature-requests:read signature-requests:write';

            $credentials = base64_encode("{$clientId}:{$clientSecret}");

            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => 'Basic ' . $credentials,
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . '/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'scope' => $scope,
                ]);

            if ($response->failed()) {
                throw new \Exception('فشل الحصول على Access Token: ' . $response->body());
            }

            $data = $response->json();
            $accessToken = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? 3600; // افتراضي: ساعة

            // تخزين وقت انتهاء الصلاحية (ناقص دقيقة كهامش أمان)
            Cache::put(
                'signit_access_token_expires_at',
                Carbon::now()->addSeconds($expiresIn - 60),
                $expiresIn
            );

            return $accessToken;
        });
    }

    /**
     * حساب وقت انتهاء الكاش بناءً على ما هو مخزن
     */
    protected function getTokenTtl()
    {
        $expiresAt = Cache::get('signit_access_token_expires_at');

        if ($expiresAt && $expiresAt instanceof Carbon) {
            return $expiresAt;
        }

        // إذا لا يوجد وقت انتهاء محفوظ، نخزن دقيقة واحدة مؤقتاً
        return Carbon::now()->addMinute();
    }

    /**
     * رفع مستند PDF وإنشاء طلب توقيع
     */
    public function createSignatureRequest($filePath, $signerEmail, $signerName)
    {
        $token = $this->getAccessToken();

        // 1️⃣ رفع المستند بطريقة binary مباشرة
        $fileStream = fopen($filePath, 'r');

        $uploadResponse = Http::withToken($token)
            ->attach('document', $fileStream, basename($filePath), [
                'Content-Type' => 'application/pdf'
            ])
            ->post($this->baseUrl . '/documents', [
                'metadata_document' => json_encode([
                    'tags' => ['tag1', 'tag2', 'tag3'],
                    'document_type' => 'Offer Letter'
                    // لم نعد نستخدم custom_fields
                ])
            ]);

        if ($uploadResponse->failed()) {
            throw new \Exception('فشل رفع المستند: ' . $uploadResponse->body());
        }

        $documentName = $uploadResponse->json()['document_name'] ?? null;
        if (!$documentName) {
            throw new \Exception('لم يتم إرجاع اسم المستند من API.');
        }

        // 2️⃣ إنشاء طلب التوقيع بدون أي custom_fields
        $payload = [
            'document_name' => $documentName,
            'signature_request' => [
                'title' => 'Company NDA',
                'metadata_document' => [
                    'tags' => ['tag1', 'tag2', 'tag3'],
                    'document_type' => 'Offer Letter'
                ],
                'signatories' => [
                    [
                        'full_name' => $signerName,
                        'order' => 0,
                        'verification_method' => [
                            'email' => $signerEmail
                        ],
                        'notification_method' => [
                            'email' => $signerEmail
                        ],
                        'fields' => [
                            [
                                'position' => [
                                    'page' => 1,
                                    'x' => 200,
                                    'y' => 200,
                                    'height' => 50,
                                    'width' => 100
                                ],
                                'properties' => [
                                    'required' => true
                                ],
                                'placeholder' => 'sign here',
                                'kind' => 'signature'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // 3️⃣ إرسال طلب التوقيع
        $signatureResponse = Http::withToken($token)
            ->post($this->baseUrl . '/signature-requests', $payload);

        if ($signatureResponse->failed()) {
            throw new \Exception('فشل إنشاء طلب التوقيع: ' . $signatureResponse->body());
        }

        return $signatureResponse->json();
    }










    /**
     * التحقق من حالة طلب التوقيع
     */
    public function getSignatureStatus($requestId)
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get($this->baseUrl . '/signature-requests/' . $requestId);

        if ($response->failed()) {
            throw new \Exception('فشل جلب حالة التوقيع: ' . $response->body());
        }

        return $response->json();
    }
}
