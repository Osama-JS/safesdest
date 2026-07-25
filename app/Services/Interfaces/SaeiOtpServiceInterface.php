<?php

namespace App\Services\Interfaces;

/**
 * عقد خدمة التحقق عبر OTP من منصة ساعي (Automize)
 * https://saei.automize.sa
 */
interface SaeiOtpServiceInterface
{
    /**
     * إرسال رمز OTP إلى رقم هاتف عبر واتساب
     *
     * @param  string  $phone  رقم الهاتف بصيغة E.164 مثل: +966XXXXXXXXX
     * @return array{
     *     success: bool,
     *     verification_id: int|null,
     *     message: string
     * }
     */
    public function sendOtp(string $phone): array;

    /**
     * التحقق من الرمز الذي أدخله المستخدم
     *
     * @param  int     $verificationId  المعرّف الذي أعادته دالة sendOtp
     * @param  string  $code            الرمز الذي أدخله المستخدم
     * @return array{
     *     success: bool,
     *     status: string,  // approved | denied | expired
     *     message: string
     * }
     */
    public function verifyOtp(int $verificationId, string $code): array;

    /**
     * التحقق من توقيع رد Callback الوارد من ساعي
     * X-OTP-Signature = sha256=HMAC(secret, body)
     *
     * @param  string  $payload    جسم الطلب الخام (raw body)
     * @param  string  $signature  قيمة هيدر X-OTP-Signature
     * @param  string  $timestamp  قيمة هيدر X-OTP-Timestamp
     * @return bool
     */
    public function verifyCallbackSignature(string $payload, string $signature, string $timestamp): bool;
}
