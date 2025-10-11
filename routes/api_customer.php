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
Route::get('/health', function () {
    return response()->json([
        'status' => 201,
        'message' => 'Customer API is working',
        'timestamp' => now()
    ]);
});

// Public routes (no authentication required)
Route::prefix('customer')->group(function () {

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

    Route::get('/profile/stats', [CustomerProfileController::class, 'getStats'])
        ->name('api.customer.profile.stats');

    Route::delete('/profile', [CustomerProfileController::class, 'deleteAccount'])
        ->name('api.customer.profile.delete');


    // Route::get('/vehicles', [CustomerSettingsController::class, 'getVehicles'])
    //   ->name('api.customer.vehicles');
    // // Dashboard routes
    // Route::get('/dashboard', [CustomerDashboardController::class, 'index'])
    //     ->name('api.customer.dashboard');

    // Route::get('/dashboard/stats', [CustomerDashboardController::class, 'getStats'])
    //     ->name('api.customer.dashboard.stats');

    // Route::get('/dashboard/recent-activities', [CustomerDashboardController::class, 'getRecentActivities'])
    //     ->name('api.customer.dashboard.activities');

    // Route::get('/dashboard/notifications', [CustomerDashboardController::class, 'getNotifications'])
    //     ->name('api.customer.dashboard.notifications');

    // // Task management routes
    // Route::get('/tasks', [CustomerTaskController::class, 'index'])
    //     ->name('api.customer.tasks');

    // Route::post('/tasks', [CustomerTaskController::class, 'store'])
    //     ->name('api.customer.tasks.store');

    // Route::get('/tasks/{id}', [CustomerTaskController::class, 'show'])
    //     ->name('api.customer.tasks.show');

    // Route::put('/tasks/{id}', [CustomerTaskController::class, 'update'])
    //     ->name('api.customer.tasks.update');

    // Route::post('/tasks/{id}/cancel', [CustomerTaskController::class, 'cancel'])
    //     ->name('api.customer.tasks.cancel');

    // Route::get('/tasks/{id}/track', [CustomerTaskController::class, 'track'])
    //     ->name('api.customer.tasks.track');

    // Route::get('/tasks/{id}/history', [CustomerTaskController::class, 'getHistory'])
    //     ->name('api.customer.tasks.history');

    // Route::post('/tasks/{id}/rate', [CustomerTaskController::class, 'rate'])
    //     ->name('api.customer.tasks.rate');

    // // Task pricing and validation
    // Route::post('/tasks/validate', [CustomerTaskController::class, 'validateTask'])
    //     ->name('api.customer.tasks.validate');

    // Route::post('/tasks/calculate-pricing', [CustomerTaskController::class, 'calculatePricing'])
    //     ->name('api.customer.tasks.pricing');

    // // Map and location routes
    // Route::get('/tasks/map-data', [CustomerTaskController::class, 'getMapData'])
    //     ->name('api.customer.tasks.map-data');

    // Route::get('/drivers/locations', [CustomerTaskController::class, 'getDriverLocations'])
    //     ->name('api.customer.drivers.locations');

    // // Wallet management routes
    // Route::get('/wallet', [CustomerWalletController::class, 'show'])
    //     ->name('api.customer.wallet');

    // Route::get('/wallet/transactions', [CustomerWalletController::class, 'getTransactions'])
    //     ->name('api.customer.wallet.transactions');

    // Route::post('/wallet/deposit', [CustomerWalletController::class, 'deposit'])
    //     ->name('api.customer.wallet.deposit');

    // Route::post('/wallet/withdraw', [CustomerWalletController::class, 'withdraw'])
    //     ->name('api.customer.wallet.withdraw');

    // Route::post('/wallet/transfer', [CustomerWalletController::class, 'transfer'])
    //     ->name('api.customer.wallet.transfer');

    // Route::get('/wallet/statements', [CustomerWalletController::class, 'getStatements'])
    //     ->name('api.customer.wallet.statements');

    // // Customs clearance routes
    // Route::get('/customs-clearances', [CustomerCustomsClearanceController::class, 'index'])
    //     ->name('api.customer.customs-clearances');

    // Route::post('/customs-clearances', [CustomerCustomsClearanceController::class, 'store'])
    //     ->name('api.customer.customs-clearances.store');

    // Route::get('/customs-clearances/{id}', [CustomerCustomsClearanceController::class, 'show'])
    //     ->name('api.customer.customs-clearances.show');

    // Route::post('/customs-clearances/{id}/documents', [CustomerCustomsClearanceController::class, 'uploadDocuments'])
    //     ->name('api.customer.customs-clearances.documents');

    // Route::get('/customs-clearances/{id}/status', [CustomerCustomsClearanceController::class, 'getStatus'])
    //     ->name('api.customer.customs-clearances.status');

    // Route::get('/customs-clearances/ads', [CustomerCustomsClearanceController::class, 'getAds'])
    //     ->name('api.customer.customs-clearances.ads');

    // Route::post('/customs-clearances/offers', [CustomerCustomsClearanceController::class, 'submitOffer'])
    //     ->name('api.customer.customs-clearances.offers');

    // // Task ads and bidding routes
    // Route::get('/ads', [CustomerAdsController::class, 'index'])
    //     ->name('api.customer.ads');

    // Route::post('/ads', [CustomerAdsController::class, 'store'])
    //     ->name('api.customer.ads.store');

    // Route::get('/ads/{id}', [CustomerAdsController::class, 'show'])
    //     ->name('api.customer.ads.show');

    // Route::get('/ads/{id}/offers', [CustomerAdsController::class, 'getOffers'])
    //     ->name('api.customer.ads.offers');

    // Route::post('/ads/{id}/offers/{offerId}/accept', [CustomerAdsController::class, 'acceptOffer'])
    //     ->name('api.customer.ads.accept-offer');

    // Route::post('/ads/{id}/close', [CustomerAdsController::class, 'closeAd'])
    //     ->name('api.customer.ads.close');

    // Route::post('/ads/{id}/extend', [CustomerAdsController::class, 'extendAd'])
    //     ->name('api.customer.ads.extend');

    // // Payment routes
    // Route::get('/payments/methods', [CustomerPaymentController::class, 'getPaymentMethods'])
    //     ->name('api.customer.payments.methods');

    // Route::post('/payments/initiate', [CustomerPaymentController::class, 'initiatePayment'])
    //     ->name('api.customer.payments.initiate');

    // Route::get('/payments/{id}/status', [CustomerPaymentController::class, 'getPaymentStatus'])
    //     ->name('api.customer.payments.status');

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
