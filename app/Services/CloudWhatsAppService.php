<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappTemplate;
use App\Services\Interfaces\WhatsAppServiceInterface;

class CloudWhatsAppService implements WhatsAppServiceInterface
{
    /**
     * Send an OTP via WhatsApp Cloud API
     *
     * @param string $phone
     * @param string $code
     * @param string $lang
     * @return bool
     */
    public function sendOTP($phone, $code, $lang = 'ar')
    {
        // For testing/simulation
        if (env('WHATSAPP_SIMULATION', true)) {
            Log::info("SIMULATED CLOUD WHATSAPP OTP sent to {$phone}: {$code}");
            return true;
        }

        return $this->sendTemplateMessage($phone, 'otp', [$code], $lang);
    }

    /**
     * Send a template message using WhatsApp templates stored in database.
     *
     * @param string $phone
     * @param string $purpose
     * @param array $variables
     * @param string $lang
     * @return bool
     */
    public function sendTemplateMessage($phone, $purpose, array $variables = [], $lang = 'ar')
    {
        $url = env('WHATSAPP_CLOUD_URL');
        $phoneId = env('WHATSAPP_CLOUD_PHONE_ID');
        $token = env('WHATSAPP_CLOUD_TOKEN');

        if (!$url || !$phoneId || !$token) {
            Log::warning('WhatsApp Cloud credentials are not set.');
            return false;
        }

        // Format phone to international format without +
        $phoneFormatted = ltrim($phone, '+');

        // Retrieve template from database
        $langCode = explode('_', $lang)[0];
        $template = WhatsappTemplate::where('purpose', $purpose)
            ->where('status', 1)
            // Fallback language if specific one is not found or we just use the default
            ->first();

        if (!$template) {
            Log::warning("WhatsApp Cloud: No active template found for purpose: {$purpose}");
            return false;
        }

        // Prepare components for the template (assuming simple body parameters)
        $components = [];
        if (!empty($variables)) {
            $parameters = [];
            foreach ($variables as $var) {
                $parameters[] = [
                    'type' => 'text',
                    'text' => (string)$var
                ];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $parameters
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phoneFormatted,
            'type' => 'template',
            'template' => [
                'name' => $template->template_name,
                'language' => [
                    'code' => $template->language ?? 'ar'
                ]
            ]
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        $endpoint = "{$url}{$phoneId}/messages";

        try {
            $response = Http::withToken($token)->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp Cloud message sent successfully to {$phoneFormatted} (Template: {$template->template_name})");
                return true;
            } else {
                Log::error("WhatsApp Cloud Error: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp Cloud message: ' . $e->getMessage());
            return false;
        }
    }
}
