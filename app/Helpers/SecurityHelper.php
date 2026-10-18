<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class SecurityHelper
{
    /**
     * Default list of sensitive keys to redact from logs and reports.
     */
    protected static array $defaultSensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'old_password',
        'confirm-password',
        'confirm_password',
        'pass',
        'pwd',
        'token',
        'access_token',
        'refresh_token',
        'bearer_token',
        'remember_token',
        'auth_token',
        'device_token',
        'fcm_token',
        'webhook_token',
        'card',
        'card_number',
        'credit_card',
        'cvv',
        'cvc',
        'ccv',
        'pan',
        'account_number',
        'iban',
        'secret',
        'client_secret',
        'app_secret',
        'api_secret',
        'private_key',
        'api_key',
        'key',
        'pin',
        'otp',
        'code',
        'verification_code',
        'authorization',
        'x-authorization',
        'x-api-key',
        'national_id',
        'id_number',
        'ssn',
    ];

    /**
     * Recursively masks sensitive fields in an array, collection, or object.
     *
     * @param mixed $data
     * @param array $additionalKeys
     * @return mixed
     */
    public static function maskSensitiveData(mixed $data, array $additionalKeys = []): mixed
    {
        if ($data === null || is_scalar($data)) {
            return $data;
        }

        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                $data = $data->toArray();
            } else {
                $data = (array) $data;
            }
        }

        if (!is_array($data)) {
            return $data;
        }

        $allSensitiveKeys = array_map('strtolower', array_merge(self::$defaultSensitiveKeys, $additionalKeys));

        $sanitized = [];
        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            $isSensitive = false;
            foreach ($allSensitiveKeys as $sensitiveKey) {
                if ($normalizedKey === $sensitiveKey || str_contains($normalizedKey, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value) || is_object($value)) {
                $sanitized[$key] = self::maskSensitiveData($value, $additionalKeys);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Helper to log messages safely by ensuring context is always redacted.
     *
     * @param string $level (info, warning, error, alert, debug, notice)
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $safeContext = self::maskSensitiveData($context);
        Log::log($level, $message, $safeContext);
    }
}
