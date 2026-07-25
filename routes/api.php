<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Driver Mobile API Routes
|--------------------------------------------------------------------------
|
| Include all driver-specific API routes for the mobile application.
| These routes are prefixed with /api and use Laravel Sanctum authentication.
|
*/

// HyperPay Webhook
Route::post('/hyperpay/webhook/payout', [\App\Http\Controllers\Api\HyperPayWebhookController::class, 'handlePayout']);

// Signit Webhook
Route::post('/signit/webhook', [\App\Http\Controllers\Api\SignitWebhookController::class, 'handleWebhook']);

// WhatsApp Webhook
Route::get('/whatsapp/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle']);

// TEST Webhook
Route::get('/whatsapp/webhook-test', function(\Illuminate\Http\Request $request) {
    $verifyToken = env('WHATSAPP_VERIFY_TOKEN');
    $mode = $request->query('hub_mode');
    $token = $request->query('hub_verify_token');
    $challenge = $request->query('hub_challenge');
    
    \Illuminate\Support\Facades\Log::info('TEST WEBHOOK GET (Verify):', $request->all());

    if ($mode === 'subscribe' && $token === $verifyToken) {
        return response($challenge, 200);
    }
    return response('Forbidden', 403);
});

Route::post('/whatsapp/webhook-test', function(\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('TEST WEBHOOK POST (Payload):', $request->all());
    return response('EVENT_RECEIVED', 200);
});

// ─────────────────────────────────────────────────────────────
// Saei OTP Service Routes
// ─────────────────────────────────────────────────────────────

// إرسال رمز OTP عبر واتساب (POST /api/saei/otp/send)
Route::post('/saei/otp/send', [\App\Http\Controllers\Api\SaeiOtpController::class, 'send']);

// التحقق من رمز OTP (POST /api/saei/otp/verify)
Route::post('/saei/otp/verify', [\App\Http\Controllers\Api\SaeiOtpController::class, 'verify']);

// استقبال Callback من ساعي بعد التحقق (POST /api/saei/otp/callback)
Route::post('/saei/otp/callback', [\App\Http\Controllers\Api\SaeiOtpController::class, 'callback']);

// Include driver API routes
require __DIR__.'/api_driver.php';
require __DIR__.'/api_customer.php';
