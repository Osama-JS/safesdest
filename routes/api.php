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
Route::any('/hyperpay/webhook', [\App\Http\Controllers\Api\HyperPayWebhookController::class, 'handleWebhook']);

// Signit Webhook
Route::post('/signit/webhook', [\App\Http\Controllers\Api\SignitWebhookController::class, 'handleWebhook']);

// Include driver API routes
require __DIR__.'/api_driver.php';
require __DIR__.'/api_customer.php';
