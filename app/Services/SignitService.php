<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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
     * الحصول على Access Token من Signit API
     */
    // public function getAccessToken()
    // {

    //     $response = Http::asForm()
    // ->acceptJson()->post($this->baseUrl . '/oauth/token', [
    //         'grant_type'    => 'client_credentials',
    //         'client_id'     => 'b484f18717ac44fbb9a984b13f45db77',
    //         'client_secret' => '0E1hSi3dLUljBQ9rxuq7Pv5s_GTnh3iyJ_T1hEZCrJs',

    //     ]);


    //     if ($response->failed()) {
    //         throw new \Exception('فشل الحصول على Access Token: ' . $response->body());
    //     }

    //     return $response->json()['access_token'];
    //}

    public function getAccessToken()
    {
        $clientId =  $this->clientId;         // غيرها للقيمة الحقيقية إذا تختلف
        $clientSecret =  $this->clientSecret;     // غيرها للقيمة الحقيقية إذا تختلف
        $scope = 'signature-requests:read signature-requests:write';

        // ترميز client_id و client_secret بالـ base64
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

        return $response->json()['access_token'];
    }


    /**
     * إنشاء طلب توقيع
     */
    public function createSignatureRequest($filePath, $signerEmail, $signerName)
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post($this->baseUrl . '/signature_requests', [
                'title' => 'عقد اتفاق',
                'signers' => [
                    [
                        'email' => $signerEmail,
                        'name'  => $signerName,
                        'order' => 1
                    ]
                ]
            ]);

        if ($response->failed()) {
            throw new \Exception('فشل إنشاء طلب التوقيع: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * التحقق من حالة طلب التوقيع
     */
    public function getSignatureStatus($requestId)
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get($this->baseUrl . '/signature_requests/' . $requestId);

        if ($response->failed()) {
            throw new \Exception('فشل جلب حالة التوقيع: ' . $response->body());
        }

        return $response->json();
    }
}
