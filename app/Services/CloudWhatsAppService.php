<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\Interfaces\WhatsAppServiceInterface;

class CloudWhatsAppService implements WhatsAppServiceInterface
{
    protected ?string $url;
    protected ?string $phoneId;
    protected ?string $token;
    protected ?string $wabaId;
    protected bool $simulation;

    public function __construct()
    {
        $this->url        = rtrim(env('WHATSAPP_CLOUD_URL', 'https://graph.facebook.com/v21.0/'), '/') . '/';
        $this->phoneId    = env('WHATSAPP_CLOUD_PHONE_ID') ?: env('SAEI_FROM_PHONE_ID');
        $this->token      = env('WHATSAPP_CLOUD_TOKEN');
        $this->wabaId     = env('WHATSAPP_CLOUD_WABA_ID');
        $this->simulation = (bool) env('WHATSAPP_SIMULATION', false);
    }

    /**
     * Send an OTP via WhatsApp. If Saei is enabled, uses Saei OTP service.
     */
    public function sendOTP($phone, $code, $lang = 'ar')
    {
        if (env('SAEI_OTP_ENABLED', false)) {
            $saei = app(SaeiOtpService::class);
            $res = $saei->sendOtp($phone);
            return $res['success'] ?? false;
        }

        if ($this->simulation) {
            Log::info("SIMULATED CLOUD WHATSAPP OTP sent to {$phone}: {$code}");
            return true;
        }

        $res = $this->sendTemplateMessage($phone, 'otp', [$code], $lang);
        return is_array($res) ? ($res['success'] ?? false) : (bool)$res;
    }

    /**
     * Send a template message using WhatsApp templates stored in database.
     */
    public function sendTemplateMessage($phone, $purpose, array $variables = [], $lang = 'ar')
    {
        $phoneFormatted = $this->formatPhone($phone);

        // Find active template matching purpose or template_name
        $template = WhatsappTemplate::where('status', 1)
            ->where(function($q) use ($purpose) {
                $q->where('purpose', $purpose)
                  ->orWhere('template_name', $purpose);
            })
            ->first();

        if (!$template) {
            Log::warning("WhatsApp Cloud: No active template found for purpose/name: {$purpose}");
            return [
                'success' => false,
                'message' => "لم يتم العثور على قالب نشط باسم: {$purpose}"
            ];
        }

        // Prepare components
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

        $languageCode = $template->language ?? 'ar';
        // Normalize language code (e.g. en_US or en)
        if (str_contains($languageCode, '-')) {
            $languageCode = str_replace('-', '_', $languageCode);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phoneFormatted,
            'type' => 'template',
            'template' => [
                'name' => $template->template_name,
                'language' => [
                    'code' => $languageCode
                ]
            ]
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        // Render body
        $renderedBody = $template->body_text ?? "قالب: {$template->template_name}";
        if (!empty($variables)) {
            foreach ($variables as $index => $var) {
                $placeholder = '{{' . ($index + 1) . '}}';
                $renderedBody = str_replace($placeholder, $var, $renderedBody);
            }
        }

        // 1. Conversation
        $conversation = WhatsappConversation::firstOrCreate(
            ['phone_number' => $phoneFormatted],
            ['unread_count' => 0]
        );

        // 2. Create message record
        $message = WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'template',
            'content' => $renderedBody,
            'status' => 'pending'
        ]);

        if ($this->simulation) {
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'meta_message_id' => 'sim_' . uniqid()
            ]);
            $conversation->update([
                'last_message_preview' => Str::limit($renderedBody, 60),
                'last_message_time' => now()
            ]);
            return ['success' => true, 'message' => $message];
        }

        if (!$this->url || !$this->phoneId || !$this->token) {
            $err = 'بيانات اعتماد واتساب كلاود غير مكتملة في ملف .env';
            $message->update(['status' => 'failed', 'error_log' => $err]);
            return ['success' => false, 'message' => $err];
        }

        try {
            $endpoint = "{$this->url}{$this->phoneId}/messages";
            $response = Http::withToken($this->token)->timeout(15)->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $metaId = $data['messages'][0]['id'] ?? null;

                $message->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'meta_message_id' => $metaId,
                    'error_log' => null
                ]);

                $conversation->update([
                    'last_message_preview' => Str::limit($renderedBody, 60),
                    'last_message_time' => now()
                ]);

                return ['success' => true, 'meta_id' => $metaId, 'message' => $message];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body();
            $message->update([
                'status' => 'failed',
                'error_log' => json_encode($errorData)
            ]);

            Log::error("WhatsApp Cloud sendTemplate Error: {$errorMessage}", ['response' => $errorData]);
            return ['success' => false, 'message' => $errorMessage, 'raw_error' => $errorData];
        } catch (\Exception $e) {
            $message->update(['status' => 'failed', 'error_log' => $e->getMessage()]);
            Log::error("WhatsApp Cloud sendTemplate Exception: {$e->getMessage()}");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send a normal text message (only allowed within Meta's 24hr window)
     */
    public function sendTextMessage($phone, $text)
    {
        $phoneFormatted = $this->formatPhone($phone);

        // 1. Conversation
        $conversation = WhatsappConversation::firstOrCreate(
            ['phone_number' => $phoneFormatted],
            ['unread_count' => 0]
        );

        // 2. Create message record
        $message = WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'content' => $text,
            'status' => 'pending'
        ]);

        if ($this->simulation) {
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'meta_message_id' => 'sim_' . uniqid()
            ]);
            $conversation->update([
                'last_message_preview' => Str::limit($text, 60),
                'last_message_time' => now()
            ]);
            return ['success' => true, 'message' => $message];
        }

        if (!$this->url || !$this->phoneId || !$this->token) {
            $err = 'بيانات اعتماد واتساب كلاود غير مكتملة في ملف .env';
            $message->update(['status' => 'failed', 'error_log' => $err]);
            return ['success' => false, 'code' => 'config_missing', 'message' => $err];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phoneFormatted,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text
            ]
        ];

        try {
            $endpoint = "{$this->url}{$this->phoneId}/messages";
            $response = Http::withToken($this->token)->timeout(15)->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $metaId = $data['messages'][0]['id'] ?? null;

                $message->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'meta_message_id' => $metaId,
                    'error_log' => null
                ]);

                $conversation->update([
                    'last_message_preview' => Str::limit($text, 60),
                    'last_message_time' => now()
                ]);

                return ['success' => true, 'meta_id' => $metaId, 'message' => $message];
            }

            $errorData = $response->json();
            $metaCode = $errorData['error']['code'] ?? null;
            $metaSubcode = $errorData['error']['error_subcode'] ?? null;
            $errorMessage = $errorData['error']['message'] ?? $response->body();

            $message->update([
                'status' => 'failed',
                'error_log' => json_encode($errorData)
            ]);

            // Check if Meta rejected due to 24h window (code 131047)
            if ($metaCode == 131047 || str_contains(strtolower($errorMessage), '24 hours') || str_contains(strtolower($errorMessage), 're-engagement')) {
                return [
                    'success' => false,
                    'code' => 'window_closed',
                    'message' => 'عذراً، انتهت نافذة الـ 24 ساعة للمحادثة من قِبل ميتا. يجب إرسال قالب رسمي لإعادة فتحها.',
                    'raw_error' => $errorData
                ];
            }

            Log::error("WhatsApp Cloud sendTextMessage Error: {$errorMessage}", ['response' => $errorData]);
            return ['success' => false, 'code' => 'api_error', 'message' => $errorMessage, 'raw_error' => $errorData];
        } catch (\Exception $e) {
            $message->update(['status' => 'failed', 'error_log' => $e->getMessage()]);
            Log::error("WhatsApp Cloud sendTextMessage Exception: {$e->getMessage()}");
            return ['success' => false, 'code' => 'network_error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Clean and format phone number to international digits without '+'
     */
    protected function formatPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        // If starts with 00, remove
        if (str_starts_with($clean, '00')) {
            $clean = substr($clean, 2);
        }
        // If local Saudi number starting with 05
        if (str_starts_with($clean, '05') && strlen($clean) === 10) {
            $clean = '966' . substr($clean, 1);
        }
        return $clean;
    }
}
