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

        // Render the body content if variables are provided
        $renderedBody = $template->body ?? "Template: {$template->template_name}";
        if (!empty($variables)) {
            foreach ($variables as $index => $var) {
                // index is 0-based, placeholders are 1-based (e.g. {{1}})
                $placeholder = '{{' . ($index + 1) . '}}';
                $renderedBody = str_replace($placeholder, $var, $renderedBody);
            }
        }

        // 1. Find or create conversation
        $conversation = \App\Models\WhatsappConversation::firstOrCreate(
            ['phone_number' => $phoneFormatted],
            ['unread_count' => 0]
        );

        // 2. Create message record
        $message = \App\Models\WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'template',
            'content' => $renderedBody,
            'status' => 'pending'
        ]);

        // 3. Dispatch the Job
        \App\Jobs\SendWhatsAppMessageJob::dispatch($message->id, $payload)->onQueue('whatsapp');

        return true;
    }

    /**
     * Send a normal text message (only allowed within 24hr window)
     *
     * @param string $phone
     * @param string $text
     * @return bool
     */
    public function sendTextMessage($phone, $text)
    {
        $url = env('WHATSAPP_CLOUD_URL');
        $phoneId = env('WHATSAPP_CLOUD_PHONE_ID');
        $token = env('WHATSAPP_CLOUD_TOKEN');

        if (!$url || !$phoneId || !$token) {
            Log::warning('WhatsApp Cloud credentials are not set.');
            return false;
        }

        $phoneFormatted = ltrim($phone, '+');

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phoneFormatted,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text
            ]
        ];

        // 1. Find or create conversation
        $conversation = \App\Models\WhatsappConversation::firstOrCreate(
            ['phone_number' => $phoneFormatted],
            ['unread_count' => 0]
        );

        // 2. Create message record
        $message = \App\Models\WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'content' => $text,
            'status' => 'pending'
        ]);

        // 3. Dispatch the Job
        \App\Jobs\SendWhatsAppMessageJob::dispatch($message->id, $payload)->onQueue('whatsapp');

        return true;
    }
}
