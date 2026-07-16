<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Interfaces\WhatsAppServiceInterface;

class GreenApiWhatsAppService implements WhatsAppServiceInterface
{
    /**
     * Send an OTP via WhatsApp (Green API)
     *
     * @param string $phone
     * @param string $code
     * @return bool
     */
    public function sendOTP($phone, $code, $lang = 'ar')
    {
        // For testing/simulation, we can use a hardcoded OTP (e.g. 1234)
        // If we want to simulate entirely without hitting the API:
        if (env('WHATSAPP_SIMULATION', true)) {
            Log::info("SIMULATED WHATSAPP OTP sent to {$phone}: {$code}");
            return true;
        }

        $idInstance = env('GREEN_API_ID_INSTANCE');
        $apiTokenInstance = env('GREEN_API_TOKEN_INSTANCE');

        if (!$idInstance || !$apiTokenInstance) {
            Log::warning('Green API credentials are not set.');
            return false;
        }

        $messages = [
            'ar' => "مرحباً بك في وجهات آمنة تطبيق السائقين.\nكود التحقق الخاص بك هو: *{$code}*\nالرجاء عدم مشاركة هذا الكود مع أي شخص اخر.",
            'en' => "Welcome to SafeDest Driver App.\nYour verification code is: *{$code}*\nPlease do not share this code with anyone else.",
            'zh' => "欢迎使用 SafeDest 司机应用。\n您的验证码是：*{$code}*\n请勿将此验证码与任何人分享。",
            'ur' => "SafeDest ڈرائیور ایپ میں خوش آمدید۔\nآپ کا تصدیقی کوڈ ہے: *{$code}*\nبراہ کرم یہ کوڈ کسی اور کے ساتھ شیئر نہ کریں۔",
        ];

        // Format phone to international format without +
        // Example: if it has +, remove it
        $phoneFormatted = ltrim($phone, '+');
        $chatId = $phoneFormatted . '@c.us';

        $url = "https://api.green-api.com/waInstance{$idInstance}/sendMessage/{$apiTokenInstance}";

        // Normalize language code
        $langCode = explode('_', $lang)[0];
        $message = $messages[$langCode] ?? $messages['ar'];

        try {
            $response = Http::post($url, [
                'chatId' => $chatId,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp OTP sent successfully to {$phone}");
                return true;
            } else {
                Log::error("Failed to send WhatsApp OTP to {$phone}", [
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp OTP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a template message. Green API doesn't use Meta templates directly,
     * so we just log a warning or send a normal message if needed.
     */
    public function sendTemplateMessage($phone, $purpose, array $variables = [], $lang = 'ar')
    {
        Log::info("Green API: Cannot send Meta templates natively. Purpose: {$purpose}");
        return false;
    }
}
