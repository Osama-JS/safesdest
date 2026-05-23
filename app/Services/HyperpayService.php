<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class HyperpayService
{
    protected string $apiUrl;
    protected string $apiToken;
    protected string $entityId;
    protected string $entityIdMada;
    protected string $currency;
    protected string $scriptUrl;

    public function __construct()
    {
        $this->apiUrl       = config('hyperpay.base_url', 'https://eu-test.oppwa.com');
        $this->apiToken     = config('hyperpay.access_token', '');
        $this->entityId     = config('hyperpay.entityId', '');
        $this->entityIdMada = config('hyperpay.entityIdMada') ?? config('hyperpay.entityId') ?? '';
        $this->currency     = config('hyperpay.currency', 'SAR');
        $this->scriptUrl    = config('hyperpay.script_url', 'https://eu-test.oppwa.com/v1/paymentWidgets.js');
    }

    /**
     * Get HyperPay widget script URL.
     */
    public function getScriptUrl(): string
    {
        return $this->scriptUrl;
    }

    /**
     * Get the correct entity ID based on payment brand.
     */
    public function getEntityId(string $brand = 'VISA MASTER'): string
    {
        return strtoupper($brand) === 'MADA' ? $this->entityIdMada : $this->entityId;
    }

    /**
     * Create a HyperPay checkout session.
     *
     * @param float  $amount
     * @param string $brand  VISA MASTER | MADA
     * @param string $shopperResultUrl Callback URL after payment
     * @return array|null
     */
    public function createCheckout(float $amount, string $brand = 'VISA MASTER', string $shopperResultUrl = '', array $options = []): ?array
    {
        $entityId = $this->getEntityId($brand);

        // Test server only accepts whole amounts (e.g. xx.00) — no fractions allowed
        $isSandbox = config('hyperpay.sandboxMode', true) || str_contains($this->apiUrl, 'test');
        $formattedAmount = $isSandbox
            ? number_format(ceil($amount), 2, '.', '')
            : number_format($amount, 2, '.', '');

        $paymentData = [
            'entityId'          => $entityId,
            'amount'            => $formattedAmount,
            'currency'          => $this->currency,
            'paymentType'       => 'DB',
            'integrity'         => 'true',
            'merchantTransactionId' => $options['merchantTransactionId'] ?? uniqid('PAY-'),
            'customer.email'    => $options['customer.email'] ?? 'test@example.com',
            'customer.givenName'=> substr($options['customer.givenName'] ?? 'Customer', 0, 50),
            'customer.surname'  => substr($options['customer.surname'] ?? 'User', 0, 50),
            'billing.street1'   => $options['billing.street1'] ?? '123 Riyadh St',
            'billing.city'      => $options['billing.city'] ?? 'Riyadh',
            'billing.state'     => $options['billing.state'] ?? 'SA-01',
            'billing.country'   => $options['billing.country'] ?? 'SA',
            'billing.postcode'  => $options['billing.postcode'] ?? '12211',
        ];

        // Only include shopperResultUrl when explicitly provided (creation only)
        if (!empty($shopperResultUrl)) {
            $paymentData['shopperResultUrl'] = $shopperResultUrl;
        }

        // Apply mandatory 3DS2 integration params and Test Mode flag for sandbox environments
        if (config('hyperpay.sandboxMode', true) || str_contains($this->apiUrl, 'test')) {
            // $paymentData['testMode'] = 'EXTERNAL'; // Removed as per HyperPay support request
            $paymentData['customParameters[3DS2_enrolled]'] = 'true';
            $paymentData['customParameters[3DS2_flow]'] = 'challenge';
        }

        $params = http_build_query($paymentData);

        Log::info('HyperPay CreateCheckout Request', [
            'amount'    => $amount,
            'brand'     => $brand,
            'entityId'  => $entityId,
        ]);

        $response = $this->curlPost($this->apiUrl . '/v1/checkouts', $params);

        if ($response === null) {
            return null;
        }

        Log::info('HyperPay CreateCheckout Response', ['response' => $response]);

        return $response;
    }

    /**
     * Query the status of a payment by checkout ID.
     */
    public function getPaymentStatus(string $checkoutId, string $brand = 'VISA MASTER'): ?array
    {
        $entityId = urlencode($this->getEntityId($brand));
        $url      = "{$this->apiUrl}/v1/checkouts/{$checkoutId}/payment?entityId={$entityId}";
        $url      = $this->sanitizeUrl($url);

        $response = $this->curlGet($url);

        Log::info('HyperPay Status Query', [
            'checkoutId' => $checkoutId,
            'url'        => $url,
            'response'   => $response,
        ]);

        return $response;
    }

    /**
     * Query payment status using HyperPay resourcePath from callback redirect.
     */
    public function getPaymentStatusByResourcePath(string $resourcePath, string $brand = 'VISA MASTER'): ?array
    {
        $entityId = urlencode($this->getEntityId($brand));
        // urlencode the resourcePath and entityId to avoid malformed queries
        $url      = "{$this->apiUrl}/v1/payments?entityId={$entityId}&resourcePath=" . urlencode($resourcePath);
        $url      = $this->sanitizeUrl($url);

        $response = $this->curlGet($url);

        Log::info('HyperPay ResourcePath Status Query', [
            'resourcePath' => $resourcePath,
            'url'          => $url,
            'response'     => $response,
        ]);

        return $response;
    }

    /**
     * Determine if a HyperPay result code means success.
     * Codes: 000.000.* or 000.100.1xx → immediate success
     */
    public static function isSuccessCode(string $code): bool
    {
        return str_starts_with($code, '000.000') || str_starts_with($code, '000.100.1');
    }

    /**
     * Determine if a code means "needs review" (soft success).
     */
    public static function isReviewCode(string $code): bool
    {
        return str_starts_with($code, '000.400');
    }

    /**
     * Determine if a code means "pending" (async 3DS etc).
     */
    public static function isPendingCode(string $code): bool
    {
        return str_starts_with($code, '000.200');
    }

    /**
     * Translate a HyperPay code into local status string.
     */
    public static function codeToStatus(string $code): string
    {
        if (self::isSuccessCode($code))  return 'paid';
        if (self::isReviewCode($code))   return 'review';
        if (self::isPendingCode($code))  return 'pending';
        return 'failed';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function curlPost(string $url, string $postFields): ?array
    {
        $url = $this->sanitizeUrl($url);

        Log::info('HyperPay Request', [
            'method' => 'POST',
            'url'    => $url,
            'body'   => $postFields,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiToken],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_SSL_VERIFYPEER => !config('hyperpay.sandboxMode', true),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $resp  = curl_exec($ch);
        $errno = curl_errno($ch);
        if ($errno) {
            Log::error('HyperPay cURL POST Error', ['errno' => $errno, 'error' => curl_error($ch), 'url' => $url]);
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        return json_decode($resp, true);
    }

    private function curlGet(string $url): ?array
    {
        $url = $this->sanitizeUrl($url);

        Log::info('HyperPay Request', [
            'method' => 'GET',
            'url'    => $url,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiToken],
            CURLOPT_SSL_VERIFYPEER => !config('hyperpay.sandboxMode', true),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $resp  = curl_exec($ch);
        $errno = curl_errno($ch);
        if ($errno) {
            Log::error('HyperPay cURL GET Error', ['errno' => $errno, 'error' => curl_error($ch), 'url' => $url]);
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        return json_decode($resp, true);
    }

    private function sanitizeUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (!isset($parsed['query'])) {
            return $url;
        }

        parse_str($parsed['query'], $query);
        if (isset($query['shopperResultUrl'])) {
            unset($query['shopperResultUrl']);
            $parsed['query'] = http_build_query($query);

            $scheme   = $parsed['scheme'] ?? 'https';
            $host     = $parsed['host'] ?? '';
            $port     = isset($parsed['port']) ? ":{$parsed['port']}" : '';
            $path     = $parsed['path'] ?? '';
            $queryStr = $parsed['query'] ? "?{$parsed['query']}" : '';
            $fragment = isset($parsed['fragment']) ? "#{$parsed['fragment']}" : '';

            $sanitized = "{$scheme}://{$host}{$port}{$path}{$queryStr}{$fragment}";
            Log::warning('HyperPay URL sanitized to remove shopperResultUrl', [
                'original_url'  => $url,
                'sanitized_url' => $sanitized,
            ]);

            return $sanitized;
        }

        return $url;
    }
}
