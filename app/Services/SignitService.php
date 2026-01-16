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
     * رفع مستند PDF وإنشاء طلب توقيع لعدة أطراف
     */
    public function createSignatureRequest($filePath, $signers, $title = 'Task Policy Signature')
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
                    'tags' => ['task', 'policy'],
                    'document_type' => 'Offer Letter'
                ])
            ]);

        if ($uploadResponse->failed()) {
            throw new \Exception('فشل رفع المستند: ' . $uploadResponse->body());
        }

        $documentName = $uploadResponse->json()['document_name'] ?? null;
        if (!$documentName) {
            throw new \Exception('لم يتم إرجاع اسم المستند من API.');
        }

        // 0️⃣ حساب عدد صفحات الملف ديناميكياً
        $totalPages = $this->getPdfPageCount($filePath);

        // 2️⃣ تجهيز قائمة الموقعين
        $signatories = [];
        foreach ($signers as $index => $signer) {
            // توزيع أماكن التوقيع في الصفحة المخصصة للتوقيع
            // السائق (أول عنصر) في المربع الأيمن، العميل (ثاني عنصر) في المربع الأيسر
            // الإحداثيات محسوبة لصفحة A4 (595x842) حيث y=0 في الأعلى

            if ($index == 0) {
                // السائق - يمين
                $posX = 320;
                $posY = 580;
            } else {
                // العميل - يسار
                $posX = 50;
                $posY = 580;
            }

            $signatories[] = [
                'full_name' => $signer['name'],
                'order' => $index,
                'verification_method' => [
                    'email' => $signer['email']
                ],
                'notification_method' => [
                    'email' => $signer['email']
                ],
                'fields' => [
                    [
                        'position' => [
                            'page' => $totalPages, // استهداف الصفحة الأخيرة ديناميكياً
                            'x' => $posX,
                            'y' => $posY,
                            'height' => 80,
                            'width' => 180
                        ],
                        'properties' => [
                            'required' => true
                        ],
                        'placeholder' => $signer['label'] ?? 'sign here',
                        'kind' => 'signature'
                    ]
                ]
            ];
        }

        // 3️⃣ إنشاء طلب التوقيع
        $payload = [
            'document_name' => $documentName,
            'signature_request' => [
                'title' => $title,
                'metadata_document' => [
                    'tags' => ['task', 'policy'],
                    'document_type' => 'Offer Letter'
                ],
                'signatories' => $signatories
            ]
        ];

        // 4️⃣ إرسال طلب التوقيع
        $signatureResponse = Http::withToken($token)
            ->post($this->baseUrl . '/signature-requests', $payload);

        if ($signatureResponse->failed()) {
            throw new \Exception('فشل إنشاء طلب التوقيع: ' . $signatureResponse->body());
        }

        return $signatureResponse->json();
    }


    /**
     * جلب حالة طلب التوقيع
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

    /**
     * تحميل المستند الموقع
     */
    public function downloadSignedDocument($requestId)
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get($this->baseUrl . "/signature-requests/{$requestId}/signed-document");

        if ($response->failed()) {
            throw new \Exception('فشل تحميل المستند الموقع: ' . $response->body());
        }

        return $response->body(); // محتوى الملف binary
    }

    /**
     * جلب عدد صفحات ملف PDF ديناميكياً
     */
    private function getPdfPageCount($filePath)
    {
        try {
            if (!file_exists($filePath)) return 2;

            $content = file_get_contents($filePath);

            // الطريقة الأولى لإيجاد الرقم خلف /Count (أكثر دقة في بعض الهياكل)
            if (preg_match_all("/\/Count\s+(\d+)/", $content, $matches)) {
                $count = (int) max($matches[1]);
                if ($count > 0) return $count;
            }

            // الطريقة الثانية: حساب كائنات /Page
            $count = preg_match_all("/\/Page\W/", $content, $dummy);
            return $count > 0 ? $count : 2;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Signit Page Count Error: " . $e->getMessage());
            return 2; // احتياطياً
        }
    }
}
