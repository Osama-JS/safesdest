<?php

namespace App\Services\Interfaces;

interface WhatsAppServiceInterface
{
    /**
     * Send an OTP via WhatsApp.
     *
     * @param string $phone
     * @param string $code
     * @param string $lang
     * @return bool|array
     */
    public function sendOTP($phone, $code, $lang = 'ar');
    
    /**
     * Send a template message using WhatsApp templates stored in database.
     *
     * @param string $phone
     * @param string $purpose
     * @param array $variables
     * @param string $lang
     * @return bool|array
     */
    public function sendTemplateMessage($phone, $purpose, array $variables = [], $lang = 'ar');

    /**
     * Send a normal text message (only allowed within Meta's 24hr customer service window).
     *
     * @param string $phone
     * @param string $text
     * @param string|null $reference
     * @return bool|array
     */
     public function sendTextMessage($phone, $text, $reference = null);

    /**
     * Send a media message (image, video, audio, document) via URL or media ID.
     *
     * @param string $phone
     * @param string $type ('image', 'video', 'audio', 'document')
     * @param string $mediaUrl
     * @param string|null $caption
     * @param string|null $filename
     * @param string|null $reference
     * @return bool|array
     */
    public function sendMediaMessage($phone, string $type, string $mediaUrl, ?string $caption = null, ?string $filename = null, ?string $reference = null);

    /**
     * Mark a customer's incoming message as read, with optional typing indicator.
     *
     * @param string $messageId (Saei or WhatsApp message ID)
     * @param bool $typingIndicator
     * @return bool|array
     */
    public function markAsRead(string $messageId, bool $typingIndicator = false);

    /**
     * Check API connection & account information.
     *
     * @return array
     */
    public function getAccountInfo(): array;
}
