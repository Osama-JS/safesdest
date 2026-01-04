<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class HyperpayService
{
  protected $client;
  protected $apiUrl;
  protected $apiToken;
  protected $entityId;
  protected $currency;

  public function __construct()
  {
    $this->client = new Client();
    $this->apiUrl = config('hyperpay.base_url');
    $this->apiToken = config('hyperpay.access_token');
    $this->entityId = config('hyperpay.entityId'); // Corrected key to match config file if needed, checking config...
    $this->currency = config('hyperpay.currency');
  }

  public function getScriptUrl()
  {
      return config('hyperpay.script_url');
  }

  public function createCheckout($amount)
  {
    // البيانات التي سيتم إرسالها في POST
    $data = [
      'entityId' => $this->entityId,
      'amount' => $amount,
      'currency' => $this->currency,
      'paymentType' => 'DB',  // DB تعني عملية دفع لمرة واحدة
      'integrity' => 'true'   // يجب تغيير هذا بناءً على تفعيل التحقق الأمني
    ];

    // إنشاء طلب cURL
    try {
      Log::info('HyperPay Checkout Request', [
        'amount' => $amount,
        'currency' => $this->currency,
        'entityId' => $this->entityId,
        'apiUrl' => $this->apiUrl,
      ]);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '/v1/checkouts');
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $this->apiToken
      ]);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));  // تحويل البيانات إلى تنسيق مناسب
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // استخدم true في بيئة الإنتاج
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $responseData = curl_exec($ch);

      if (curl_errno($ch)) {
        Log::error('HyperPay cURL Error', [
          'error' => curl_error($ch),
          'errno' => curl_errno($ch),
        ]);
        curl_close($ch);
        return null;
      }

      curl_close($ch);

      // تحليل الاستجابة وتحويلها إلى مصفوفة
      $response = json_decode($responseData, true);

      Log::info('HyperPay Checkout Response', [
        'response' => $response,
        'raw_response' => $responseData,
      ]);

      return $response;
    } catch (\Exception $e) {
      Log::error('HyperPay Exception', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return null;
    }
  }

  public function getPaymentStatus($checkoutId)
  {
    $entityId = config('hyperpay.entityId'); // تأكد أن هذا معرف في .env
    $baseUrl = config('hyperpay.base_url');
    $url = "{$baseUrl}/v1/checkouts/{$checkoutId}/payment?entityId={$entityId}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization:Bearer ' . config('hyperpay.access_token'),
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // فقط في بيئة الاختبار

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
      Log::error('Hyperpay cURL Error: ' . curl_error($ch));
      return null;
    }

    curl_close($ch);
    return json_decode($response, true);
  }
}
