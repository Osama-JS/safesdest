<?php

namespace App\Services;

use App\Models\Settings;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Services\Interfaces\WhatsAppServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Saei WhatsApp Business API Service (Automize / Meta BSP)
 * =========================================================
 * Documentation: https://app.saei.automize.sa/docs/reference/messages
 * Base Endpoint: https://api.saei.automize.sa/v1
 */
class SaeiWhatsAppService implements WhatsAppServiceInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $fromPhoneId;
    protected bool $simulation;

    public function __construct()
    {
        // Saei API structure: https://api.saei.automize.sa/v1
        $rawUrl = Settings::getValue('saei_base_url', env('SAEI_BASE_URL', 'https://api.saei.automize.sa/v1'));
        $this->baseUrl     = self::normalizeBaseUrl($rawUrl);
        $this->apiKey      = Settings::getValue('saei_api_key', env('SAEI_API_KEY', ''));
        $this->fromPhoneId = Settings::getValue('saei_from_phone_id', env('SAEI_FROM_PHONE_ID'));
        $this->simulation  = filter_var(Settings::getValue('saei_simulation', env('SAEI_SIMULATION', false)), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Normalize any Saei base URL to always end in /v1
     * Accepts: https://api.saei.automize.sa
     *          https://api.saei.automize.sa/v1
     *          https://api.saei.automize.sa/api (strips /api)
     */
    protected static function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');
        // Strip trailing /api/v1, /api, or /v1
        $url = preg_replace('#/(api/v1|api|v1)$#', '', $url);
        return $url . '/v1';
    }

    /**
     * Override credentials dynamically (useful for connection testing in settings)
     */
    public function setCredentials(?string $apiKey, ?string $baseUrl = null, ?string $phoneId = null): self
    {
        if ($apiKey !== null) {
            $this->apiKey = trim($apiKey);
        }
        if ($baseUrl !== null && !empty($baseUrl)) {
            $this->baseUrl = self::normalizeBaseUrl($baseUrl);
        }
        if ($phoneId !== null) {
            $this->fromPhoneId = trim($phoneId);
        }
        return $this;
    }

    /**
     * Send an OTP via WhatsApp. If Saei OTP service is available, uses template or OTP endpoint.
     */
    public function sendOTP($phone, $code, $lang = 'ar')
    {
        $saeiEnabled = filter_var(Settings::getValue('saei_otp_enabled', env('SAEI_OTP_ENABLED', true)), FILTER_VALIDATE_BOOLEAN);
        if ($saeiEnabled) {
            $saeiOtp = app(SaeiOtpService::class);
            $res = $saeiOtp->sendOtp($phone);
            return $res['success'] ?? false;
        }

        if ($this->simulation) {
            Log::info("[SaeiWhatsApp][SIMULATION] OTP sent to {$phone}: {$code}");
            return true;
        }

        $res = $this->sendTemplateMessage($phone, 'otp', [$code], $lang);
        return is_array($res) ? ($res['success'] ?? false) : (bool)$res;
    }

    /**
     * Send a normal text message (strictly within Meta's 24-hr window)
     */
    public function sendTextMessage($phone, $text, $reference = null)
    {
        $phoneFormatted = $this->formatPhone($phone);
        $reference = $reference ?: 'msg_' . Str::random(16);

        // 1. Conversation
        $conversation = WhatsappConversation::firstOrCreate(
            ['phone_number' => $phoneFormatted],
            ['unread_count' => 0]
        );

        // 2. Create message record
        $message = WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'message_type'    => 'text',
            'content'         => $text,
            'reference'       => $reference,
            'status'          => 'pending',
        ]);

        if ($this->simulation) {
            $simId = 'msg_sim_' . uniqid();
            $message->update([
                'status'          => 'sent',
                'saei_message_id' => $simId,
                'sent_at'         => now(),
            ]);
            $conversation->update([
                'last_message_preview' => Str::limit($text, 60),
                'last_message_time'    => now(),
            ]);
            return ['success' => true, 'id' => $simId, 'message' => $message];
        }

        if (empty($this->apiKey)) {
            $err = 'مفتاح API الخاص بمنصة ساعي غير مضبوط في الإعدادات.';
            $message->update(['status' => 'failed', 'error_log' => $err, 'error_code' => 'config_missing']);
            return ['success' => false, 'code' => 'config_missing', 'message' => $err];
        }

        $payload = [
            'to'   => $phoneFormatted,
            'type' => 'text',
            'text' => [
                'body'        => $text,
                'preview_url' => true,
            ],
            'reference' => $reference,
        ];

        if (!empty($this->fromPhoneId)) {
            $payload['from'] = $this->fromPhoneId;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(15)
                ->post("{$this->baseUrl}/messages", $payload);

            if ($response->successful()) {
                $resData = $response->json();
                $saeiMsgId = $resData['id'] ?? null;
                $status = $resData['status'] ?? 'accepted';

                $message->update([
                    'saei_message_id'    => $saeiMsgId,
                    'provider_message_id'=> $resData['provider_message_id'] ?? null,
                    'status'             => ($status === 'accepted' || $status === 'sent') ? 'sent' : $status,
                    'sent_at'            => now(),
                ]);

                $conversation->update([
                    'last_message_preview' => Str::limit($text, 60),
                    'last_message_time'    => now(),
                ]);

                return ['success' => true, 'id' => $saeiMsgId, 'message' => $message];
            }

            $errorData = $response->json();
            $errorCode = $errorData['error']['code'] ?? ($errorData['code'] ?? 'api_error');
            $errorTitle = $errorData['error']['title'] ?? ($errorData['title'] ?? 'حدث خطأ أثناء الإرسال');
            $errorMessage = $errorData['error']['message'] ?? ($errorData['message'] ?? $response->body());

            $message->update([
                'status'      => 'failed',
                'error_code'  => $errorCode,
                'error_title' => $errorTitle,
                'error_log'   => json_encode($errorData, JSON_UNESCAPED_UNICODE),
            ]);

            Log::error("[SaeiWhatsApp] sendTextMessage Error: {$errorCode} - {$errorMessage}", ['response' => $errorData]);

            return [
                'success'   => false,
                'code'      => $errorCode,
                'title'     => $errorTitle,
                'message'   => $this->translateError($errorCode, $errorMessage),
                'raw_error' => $errorData,
            ];
        } catch (\Throwable $e) {
            $message->update([
                'status'    => 'failed',
                'error_log' => $e->getMessage(),
            ]);
            Log::error("[SaeiWhatsApp] sendTextMessage Exception: {$e->getMessage()}");
            return ['success' => false, 'code' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Send an approved template message
     */
    public function sendTemplateMessage($phone, $purpose, array $variables = [], $lang = 'ar')
    {
        $phoneFormatted = $this->formatPhone($phone);

        // Find active template
        $template = WhatsappTemplate::where('status', 1)
            ->where(function($q) use ($purpose) {
                $q->where('purpose', $purpose)
                  ->orWhere('template_name', $purpose);
            })
            ->first();

        if (!$template) {
            Log::warning("[SaeiWhatsApp] No active template found for purpose/name: {$purpose}");
            return [
                'success' => false,
                'code'    => 'template_not_found',
                'message' => "لم يتم العثور على قالب نشط وموثق باسم: {$purpose}",
            ];
        }

        $templateName = $template->template_name;
        $templateLang = $template->language ?: $lang;
        $reference = 'tpl_' . Str::random(16);

        // Conversation
        $conversation = WhatsappConversation::firstOrCreate(
            ['phone_number' => $phoneFormatted],
            ['unread_count' => 0]
        );

        // Render preview body
        $renderedBody = $template->body_text ?? "قالب: {$templateName}";
        if (!empty($variables)) {
            foreach ($variables as $index => $val) {
                $renderedBody = str_replace('{{' . ($index + 1) . '}}', (string)$val, $renderedBody);
            }
        }

        $message = WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'message_type'    => 'template',
            'content'         => $renderedBody,
            'reference'       => $reference,
            'status'          => 'pending',
        ]);

        if ($this->simulation) {
            $simId = 'msg_sim_' . uniqid();
            $message->update([
                'status'          => 'sent',
                'saei_message_id' => $simId,
                'sent_at'         => now(),
            ]);
            $conversation->update([
                'last_message_preview' => Str::limit($renderedBody, 60),
                'last_message_time'    => now(),
            ]);
            return ['success' => true, 'id' => $simId, 'message' => $message];
        }

        if (empty($this->apiKey)) {
            $err = 'مفتاح API الخاص بمنصة ساعي غير مضبوط في الإعدادات.';
            $message->update(['status' => 'failed', 'error_log' => $err, 'error_code' => 'config_missing']);
            return ['success' => false, 'code' => 'config_missing', 'message' => $err];
        }

        // Format body variables for Saei
        $bodyVariables = [];
        if (!empty($variables)) {
            foreach ($variables as $var) {
                $bodyVariables[] = (string)$var;
            }
        }

        $payload = [
            'to'       => $phoneFormatted,
            'type'     => 'template',
            'template' => [
                'name'      => $templateName,
                'language'  => $templateLang,
                'variables' => [
                    'body' => $bodyVariables,
                ],
            ],
            'reference' => $reference,
        ];

        if (!empty($this->fromPhoneId)) {
            $payload['from'] = $this->fromPhoneId;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(15)
                ->post("{$this->baseUrl}/messages", $payload);

            if ($response->successful()) {
                $resData = $response->json();
                $saeiMsgId = $resData['id'] ?? null;
                $status = $resData['status'] ?? 'accepted';

                $message->update([
                    'saei_message_id' => $saeiMsgId,
                    'status'          => ($status === 'accepted' || $status === 'sent') ? 'sent' : $status,
                    'sent_at'         => now(),
                ]);

                $conversation->update([
                    'last_message_preview' => Str::limit($renderedBody, 60),
                    'last_message_time'    => now(),
                ]);

                return ['success' => true, 'id' => $saeiMsgId, 'message' => $message];
            }

            $errorData = $response->json();
            $errorCode = $errorData['error']['code'] ?? ($errorData['code'] ?? 'template_error');
            $errorMessage = $errorData['error']['message'] ?? ($errorData['message'] ?? $response->body());

            $message->update([
                'status'     => 'failed',
                'error_code' => $errorCode,
                'error_log'  => json_encode($errorData, JSON_UNESCAPED_UNICODE),
            ]);

            Log::error("[SaeiWhatsApp] sendTemplate Error: {$errorCode} - {$errorMessage}", ['response' => $errorData]);

            return [
                'success'   => false,
                'code'      => $errorCode,
                'message'   => $this->translateError($errorCode, $errorMessage),
                'raw_error' => $errorData,
            ];
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'error_log' => $e->getMessage()]);
            Log::error("[SaeiWhatsApp] sendTemplate Exception: {$e->getMessage()}");
            return ['success' => false, 'code' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Send a media message (image, video, audio, document)
     */
    public function sendMediaMessage($phone, string $type, string $mediaUrl, ?string $caption = null, ?string $filename = null, ?string $reference = null)
    {
        $phoneFormatted = $this->formatPhone($phone);
        $reference = $reference ?: 'med_' . Str::random(16);

        $conversation = WhatsappConversation::firstOrCreate(
            ['phone_number' => $phoneFormatted],
            ['unread_count' => 0]
        );

        $previewText = match ($type) {
            'image'    => '📷 صورة' . ($caption ? ": {$caption}" : ''),
            'document' => '📄 مستند' . ($filename ? ": {$filename}" : ($caption ? ": {$caption}" : '')),
            'video'    => '🎥 فيديو' . ($caption ? ": {$caption}" : ''),
            'audio'    => '🎤 مقطع صوتي',
            default    => "ملف ({$type})"
        };

        $message = WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'message_type'    => $type,
            'content'         => $caption,
            'media_url'       => $mediaUrl,
            'media_filename'  => $filename,
            'reference'       => $reference,
            'status'          => 'pending',
        ]);

        if ($this->simulation) {
            $simId = 'msg_sim_' . uniqid();
            $message->update([
                'status'          => 'sent',
                'saei_message_id' => $simId,
                'sent_at'         => now(),
            ]);
            $conversation->update([
                'last_message_preview' => Str::limit($previewText, 60),
                'last_message_time'    => now(),
            ]);
            return ['success' => true, 'id' => $simId, 'message' => $message];
        }

        $mediaPayload = ['link' => $mediaUrl];
        if (!empty($caption) && in_array($type, ['image', 'video', 'document'])) {
            $mediaPayload['caption'] = $caption;
        }
        if (!empty($filename) && $type === 'document') {
            $mediaPayload['filename'] = $filename;
        }

        $payload = [
            'to'        => $phoneFormatted,
            'type'      => $type,
            $type       => $mediaPayload,
            'reference' => $reference,
        ];

        if (!empty($this->fromPhoneId)) {
            $payload['from'] = $this->fromPhoneId;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(20)
                ->post("{$this->baseUrl}/messages", $payload);

            if ($response->successful()) {
                $resData = $response->json();
                $saeiMsgId = $resData['id'] ?? null;
                $status = $resData['status'] ?? 'accepted';

                $message->update([
                    'saei_message_id' => $saeiMsgId,
                    'status'          => ($status === 'accepted' || $status === 'sent') ? 'sent' : $status,
                    'sent_at'         => now(),
                ]);

                $conversation->update([
                    'last_message_preview' => Str::limit($previewText, 60),
                    'last_message_time'    => now(),
                ]);

                return ['success' => true, 'id' => $saeiMsgId, 'message' => $message];
            }

            $errorData = $response->json();
            $errorCode = $errorData['error']['code'] ?? ($errorData['code'] ?? 'media_error');
            $errorMessage = $errorData['error']['message'] ?? ($errorData['message'] ?? $response->body());

            $message->update([
                'status'     => 'failed',
                'error_code' => $errorCode,
                'error_log'  => json_encode($errorData, JSON_UNESCAPED_UNICODE),
            ]);

            return [
                'success' => false,
                'code'    => $errorCode,
                'message' => $this->translateError($errorCode, $errorMessage),
            ];
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'error_log' => $e->getMessage()]);
            return ['success' => false, 'code' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Mark an incoming message as read, with optional typing indicator
     */
    public function markAsRead(string $messageId, bool $typingIndicator = false)
    {
        if ($this->simulation || empty($this->apiKey)) {
            return ['success' => true];
        }

        try {
            $payload = [];
            if ($typingIndicator) {
                $payload['typing_indicator'] = true;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->timeout(10)
                ->post("{$this->baseUrl}/messages/{$messageId}/read", $payload);

            return ['success' => $response->successful(), 'data' => $response->json()];
        } catch (\Throwable $e) {
            Log::warning("[SaeiWhatsApp] markAsRead error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify Saei credentials by calling GET /v1/me
     * Returns success=true with workspace data, or success=false with a clear Arabic error message.
     */
    public function getAccountInfo(): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'لم يتم إدخال مفتاح الـ API الخاص بساعي. يرجى إدخاله في حقل «مفتاح API» أعلاه ثم حفظه.'
            ];
        }

        try {
            // Official Saei verification endpoint: GET /v1/me
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(15)
                ->get("{$this->baseUrl}/me");

            Log::info('[SaeiWhatsApp] getAccountInfo', [
                'url'    => "{$this->baseUrl}/me",
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $workspaceName = $data['workspace']['name'] ?? 'ساعي';
                $keyName = $data['api_key']['name'] ?? '';
                $isLive = !empty($data['livemode']);
                $modeText = $isLive ? 'Live' : 'Test';
                $msg = "تم الاتصال بساعي بنجاح! ✅ (مساحة العمل: {$workspaceName} | الوضع: {$modeText})";

                return [
                    'success'   => true,
                    'message'   => $msg,
                    'workspace' => $data['workspace'] ?? null,
                    'api_key'   => $data['api_key'] ?? null,
                    'account'   => $data,
                ];
            }

            $status = $response->status();
            $body   = $response->json();

            if ($status === 401) {
                return [
                    'success' => false,
                    'message' => 'مفتاح الـ API غير مصرح به (401). يرجى التأكد من نسخه بالكامل من لوحة تحكم ساعي.',
                ];
            }

            if ($status === 403) {
                return [
                    'success' => false,
                    'message' => 'ليس لديك صلاحية الوصول إلى هذا الحساب (403). تحقق من صلاحيات المفتاح في ساعي.',
                ];
            }

            $msg = $body['error']['message'] ?? ($body['message'] ?? "خطأ غير معروف (HTTP {$status})");
            return ['success' => false, 'message' => "فشل الاتصال بساعي: {$msg}"];

        } catch (\Throwable $e) {
            Log::error('[SaeiWhatsApp] getAccountInfo exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'تعذر الاتصال بخادم ساعي: ' . $e->getMessage()];
        }
    }

    /**
     * Standard headers with Idempotency Key
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization'   => "Bearer {$this->apiKey}",
            'Content-Type'    => 'application/json',
            'Accept'          => 'application/json',
            'Idempotency-Key' => (string) Str::uuid(),
        ];
    }

    /**
     * Format phone to E.164 format (+966XXXXXXXXX)
     */
    protected function formatPhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 2);
        }
        if (str_starts_with($cleaned, '05') && strlen($cleaned) === 10) {
            $cleaned = '966' . substr($cleaned, 1);
        }
        return '+' . $cleaned;
    }

    /**
     * Translate Saei/Meta error codes to user-friendly Arabic
     */
    protected function translateError(string $code, string $defaultMsg): string
    {
        return match ($code) {
            'window_closed' => 'نافذة الـ 24 ساعة مغلقة. تفرض سياسات واتساب إرسال قالب معتمد أولاً لإعادة فتح المحادثة.',
            'parameter_invalid' => 'يوجد حقل غير صالح في بيانات الإرسال.',
            'rate_limited' => 'تم تجاوز حد الطلبات المسموح به حالياً من قبل واتساب. يرجى الانتظار قليلاً.',
            'sender_not_configured' => 'لم يتم إعداد رقم الهاتف المُرسل في حساب ساعي.',
            'channel_error' => 'رفض واتساب تسليم الرسالة لهذا الرقم (قد يكون غير مفعل على واتساب أو محظور).',
            'resource_missing' => 'العنصر المطلوب غير موجود في النظام.',
            default => $defaultMsg ?: 'تعذر إرسال الرسالة عبر واتساب.'
        };
    }
}
