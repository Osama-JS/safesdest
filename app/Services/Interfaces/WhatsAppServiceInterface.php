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
     * @return bool|array
     */
    public function sendTextMessage($phone, $text);
}
