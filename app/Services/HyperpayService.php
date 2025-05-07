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
    $this->apiUrl = env('HYPERPAY_API_URL');
    $this->apiToken = env('HYPERPAY_API_TOKEN');
    $this->entityId = env('HYPERPAY_ENTITY_ID');
    $this->currency = env('HYPERPAY_CURRENCY');
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
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $this->apiToken
      ]);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));  // تحويل البيانات إلى تنسيق مناسب
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // استخدم true في بيئة الإنتاج
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $responseData = curl_exec($ch);

      if (curl_errno($ch)) {
        Log::error('Error with cURL: ' . curl_error($ch));
        curl_close($ch);
        return null;
      }

      curl_close($ch);

      // تحليل الاستجابة وتحويلها إلى مصفوفة
      return json_decode($responseData, true);
    } catch (\Exception $e) {
      Log::error('Error creating HyperPay checkout: ' . $e->getMessage());
      return null;
    }
  }

  public function getPaymentStatus($checkoutId)
  {
    $entityId = config('hyperpay.entity_id'); // تأكد أن هذا معرف في .env
    $url = "https://eu-test.oppwa.com/v1/checkouts/{$checkoutId}/payment?entityId={$entityId}";

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
