<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\CustomerDashboardController;
use App\Http\Controllers\Api\CustomerTaskController;
use App\Http\Controllers\Api\CustomerWalletController;
use App\Http\Controllers\Api\CustomerCustomsClearanceController;
use App\Http\Controllers\Api\CustomerAdsController;
use App\Http\Controllers\Api\CustomerPaymentController;
use App\Http\Controllers\Api\CustomerNotificationController;
use App\Http\Controllers\Api\CustomerSettingsController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Customer API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the customer mobile application.
| These routes are prefixed with /api/customer and use Laravel Sanctum authentication.
|
*/

// Health check
// Route::get('/health', function () {
//     return response()->json([
//         'status' => 201,
//         'message' => 'Customer API is working',
//         'timestamp' => now()
//     ]);
// });

// Public routes (no authentication required)
Route::prefix('customer')->group(function () {

  Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'min_version' => \App\Models\Settings::getValue('min_customer_app_version', '1.0.0'),
            'update_url' => \App\Models\Settings::getValue('customer_app_update_url', 'https://play.google.com/store/apps'),
            'update_url_ios' => \App\Models\Settings::getValue('customer_app_ios_update_url', 'https://apps.apple.com/us/'),
            'server_time' => now()->toDateTimeString(),
        ]);
    });


    Route::get('/template', [CustomerAuthController::class, 'getTemplate'])
        ->name('api.customer.template');



    Route::post('/register', [CustomerAuthController::class, 'register'])
        ->middleware(['throttle:3,1'])
        ->name('api.customer.register');

    Route::post('/resend-verification', [CustomerAuthController::class, 'resendVerification'])
        ->middleware(['throttle:3,1'])
        ->name('api.customer.resend-verification');

    // Authentication routes
    Route::post('/login', [CustomerAuthController::class, 'login'])
        ->middleware(['throttle:5,1'])
        ->name('api.customer.login');

    Route::post('/auth/request-otp', [CustomerAuthController::class, 'requestOtp'])
        ->middleware(['throttle:5,1'])
        ->name('api.customer.request-otp');

    Route::post('/auth/verify-otp', [CustomerAuthController::class, 'verifyOtp'])
        ->middleware(['throttle:5,1'])
        ->name('api.customer.verify-otp');


    Route::post('/forgot-password', [CustomerAuthController::class, 'forgotPassword'])
        ->middleware(['throttle:3,1'])
        ->name('api.customer.forgot-password');

    Route::post('/check-reset-code', [CustomerAuthController::class, 'checkResetCode'])
    ->middleware(['throttle:3,1'])
    ->name('api.customer.check-reset-code');

    Route::post('/reset-password', [CustomerAuthController::class, 'verifyResetCode'])
        ->middleware(['throttle:3,1'])
        ->name('api.customer.verify-reset-code');


    Route::post('/verify-email-code', [CustomerAuthController::class, 'verifyEmailCode'])
    ->middleware(['throttle:3,1'])
    ->name('api.customer.verify-email-code');

    Route::get('/tasks/{id}/report', [CustomerTaskController::class, 'downloadTaskReport'])
        ->name('api.customer.tasks.report');
    Route::get('/tasks/{id}/invoice', [CustomerTaskController::class, 'downloadTaskInvoice'])
        ->name('api.customer.tasks.invoice');
});

// Protected routes (require Sanctum authentication)
Route::prefix('customer')->middleware(['auth:sanctum'])->group(function () {

    // Authentication routes
    Route::post('/check-token', [CustomerAuthController::class, 'checkToken'])
        ->name('api.customer.check-token');

    Route::post('/logout', [CustomerAuthController::class, 'logout'])
        ->name('api.customer.logout');

    Route::post('/change-password', [CustomerAuthController::class, 'changePassword'])
        ->name('api.customer.change-password');

    // Profile management routes
    Route::get('/profile', [CustomerProfileController::class, 'show'])
        ->name('api.customer.profile');

    Route::put('/profile', [CustomerProfileController::class, 'update'])
        ->name('api.customer.profile.update');

    Route::post('/profile/avatar', [CustomerProfileController::class, 'uploadAvatar'])
        ->name('api.customer.profile.avatar');

    Route::post('/profile/signature', [CustomerProfileController::class, 'updateSignature'])
        ->name('api.customer.profile.signature');

    Route::get('/profile/stats', [CustomerProfileController::class, 'getStats'])
        ->name('api.customer.profile.stats');

    Route::delete('/profile', [CustomerProfileController::class, 'deleteAccount'])
        ->name('api.customer.profile.delete');

    Route::post('/delete-account', [CustomerProfileController::class, 'deleteAccount'])
        ->name('api.customer.delete-account');

    // Tasks management routes (Allowed for guests to view)
    Route::get('/inti-tasks', [CustomerTaskController::class, 'getInitData'])
       ->name('api.init.tasks');

    Route::get('/tasks/map-data', [CustomerTaskController::class, 'getTasksMap'])
      ->name('api.customer.tasks.map-data');

    Route::post('/tasks/data', [CustomerTaskController::class, 'getTasks'])
    ->name('api.customer.tasks.data');

    Route::get('/tasks/{id}', [CustomerTaskController::class, 'show'])
        ->name('api.customer.tasks.show');

    Route::get('/tasks/{id}/payment-status', [CustomerTaskController::class, 'getPaymentStatus'])
        ->name('api.customer.tasks.payment-status');

    // Wallet view routes (Allowed for guests to view)
    Route::post('/wallet', [CustomerWalletController::class, 'show'])
    ->name('api.customer.wallet');

    Route::post('/wallet/transactions', [CustomerWalletController::class, 'getTransactions'])
    ->name('api.customer.wallet-transactions');

    Route::get('/wallet/recharge/{id}/status', [CustomerWalletController::class, 'checkRechargeStatus'])
    ->name('api.customer.wallet-recharge-status');

    // Customs clearance & ads view routes (Allowed for guests to view)
    Route::get('/customs-clearances/template', [CustomerCustomsClearanceController::class, 'getTemplate'])
        ->name('api.customer.customs-clearances.template');

    Route::post('/customs-clearances/data', [CustomerCustomsClearanceController::class, 'index'])
        ->name('api.customer.customs-clearances.index');

    Route::post('/customs-clearances/{id}', [CustomerCustomsClearanceController::class, 'show'])
        ->name('api.customer.customs-clearances.show');

    Route::post('/customs-clearances/{id}/status', [CustomerCustomsClearanceController::class, 'getStatus'])
        ->name('api.customer.customs-clearances.status');

    Route::post('/customs-clearances/{id}/offers', [CustomerCustomsClearanceController::class, 'offers'])
        ->name('api.customer.customs-clearances.offers');

    Route::post('/ads/data', [CustomerAdsController::class, 'getData'])
        ->name('api.customer.ads.data');

    Route::post('/ads/{id}', [CustomerAdsController::class, 'show'])
        ->name('api.customer.ads.show');

    Route::post('/ads/{id}/offers', [CustomerAdsController::class, 'getOffers'])
        ->name('api.customer.ads.offers');

    // Routes requiring complete customer profile (Blocked for guests)
    Route::middleware('customer.profile.completed')->group(function () {
        // Task creation, editing and cancellation
        Route::post('/tasks/validate-step1', [CustomerTaskController::class, 'validateStep1'])
            ->name('api.customer.tasks.validate-step1');

        Route::post('/tasks/validate-step2', [CustomerTaskController::class, 'validateStep2'])
            ->name('api.customer.tasks.validate-step2');

        Route::post('/tasks', [CustomerTaskController::class, 'store'])
            ->name('api.customer.tasks.store');

        Route::get('/tasks/{id}/edit', [CustomerTaskController::class, 'edit'])
            ->name('api.customer.tasks.edit');

        Route::post('/tasks/update', [CustomerTaskController::class, 'update'])
            ->name('api.customer.tasks.update');

        Route::post('/tasks/{id}/cancel', [CustomerTaskController::class, 'customerCancelTask'])
            ->name('api.customer.tasks.cancel');

        Route::post('/tasks/{id}/undo-cancel', [CustomerTaskController::class, 'undoCustomerCancelTask'])
            ->name('api.customer.tasks.undo-cancel');

        Route::post('/initiate-payment', [PaymentController::class, 'initiatePayment'])
            ->name('api.customer.payment.initiate');

        // Wallet transactions
        Route::post('/wallet/recharge/initiate', [CustomerWalletController::class, 'initiateRecharge'])
            ->name('api.customer.wallet-recharge-initiate');

        // Customs clearance actions
        Route::post('/customs-clearances', [CustomerCustomsClearanceController::class, 'store'])
            ->name('api.customer.customs-clearances.store');

        Route::post('/customs-clearances/{id}/documents', [CustomerCustomsClearanceController::class, 'uploadDocuments'])
            ->name('api.customer.customs-clearances.documents');

        Route::post('/customs-clearances/{id}/update', [CustomerCustomsClearanceController::class, 'update'])
            ->name('api.customer.customs-clearances.update');

        Route::post('/customs-clearances/offers/{id}/accept', [CustomerCustomsClearanceController::class, 'acceptOffer'])
            ->name('api.customer.customs-clearances.accept-offer');

        // Ads offers
        Route::post('/ads/offers/{id}/accept', [CustomerAdsController::class, 'acceptOffer'])
            ->name('api.customer.ads.accept-offer');

        Route::post('/ads/offers/{id}/retract', [CustomerAdsController::class, 'retractOffer'])
            ->name('api.customer.ads.retract-offer');
    });

    // // Payment routes
    // Route::get('/payments/methods', [CustomerPaymentController::class, 'getPaymentMethods'])
    //     ->name('api.customer.payments.methods');

    // Route::post('/payments/initiate', [CustomerPaymentController::class, 'initiatePayment'])
    //     ->name('api.customer.payments.initiate');

    Route::get('/payments/{id}/status', [PaymentController::class, 'getStatus'])
        ->name('api.customer.payments.status');

    // Route::post('/payments/{id}/confirm', [CustomerPaymentController::class, 'confirmPayment'])
    //     ->name('api.customer.payments.confirm');

    // Route::post('/payments/{id}/cancel', [CustomerPaymentController::class, 'cancelPayment'])
    //     ->name('api.customer.payments.cancel');

    // Route::get('/payments/history', [CustomerPaymentController::class, 'getPaymentHistory'])
    //     ->name('api.customer.payments.history');

    // Route::get('/payments/{id}/receipt', [CustomerPaymentController::class, 'getReceipt'])
    //     ->name('api.customer.payments.receipt');

    // // Notification routes
    // Route::get('/notifications', [CustomerNotificationController::class, 'index'])
    //     ->name('api.customer.notifications');

    // Route::post('/notifications/{id}/read', [CustomerNotificationController::class, 'markAsRead'])
    //     ->name('api.customer.notifications.read');

    // Route::post('/notifications/mark-all-read', [CustomerNotificationController::class, 'markAllAsRead'])
    //     ->name('api.customer.notifications.mark-all-read');

    // Route::get('/notifications/settings', [CustomerNotificationController::class, 'getSettings'])
    //     ->name('api.customer.notifications.settings');

    // Route::put('/notifications/settings', [CustomerNotificationController::class, 'updateSettings'])
    //     ->name('api.customer.notifications.update-settings');

    // Route::post('/fcm-token', [CustomerNotificationController::class, 'registerFcmToken'])
    //     ->name('api.customer.fcm-token');

    // // Settings and configuration routes
    // Route::get('/settings', [CustomerSettingsController::class, 'getSettings'])
    //     ->name('api.customer.settings');

    // Route::put('/settings', [CustomerSettingsController::class, 'updateSettings'])
    //     ->name('api.customer.settings.update');

    // Route::get('/geofences', [CustomerSettingsController::class, 'getGeofences'])
    //     ->name('api.customer.geofences');

    // Route::get('/app-version', [CustomerSettingsController::class, 'getAppVersion'])
    //     ->name('api.customer.app-version');

    // // Dynamic fields validation
    // Route::post('/form-fields/validate', [CustomerSettingsController::class, 'validateFormFields'])
    //     ->name('api.customer.form-fields.validate');
});
