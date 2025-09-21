<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\DriverTaskController;
use App\Http\Controllers\Api\DriverTaskAdsController;
use App\Http\Controllers\Api\DriverLocationController;
use App\Http\Controllers\Api\DriverWalletController;
use App\Http\Controllers\Api\DriverNotificationController;
use App\Http\Controllers\Api\DriverNotificationTestController;
use App\Http\Controllers\Api\DriverRegistrationController;
use App\Http\Controllers\Api\DriverProfileController;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Driver API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes specifically for the driver mobile application.
| These routes use Laravel Sanctum for authentication and are protected
| with appropriate middleware for security.
|
*/

// Public routes (no authentication required)
Route::prefix('driver')->group(function () {

    Route::get('/health', function () {
        Log::alert("check connection is ok ");
        return true;
    });

    // Test endpoint for debugging task ads (no auth required)
    Route::get('/task-ads/test-stats', [DriverTaskAdsController::class, 'testStats']);
    // Get reCAPTCHA site key
    // Route::get('/recaptcha-site-key', [DriverAuthController::class, 'getRecaptchaSiteKey'])
    //     ->name('api.driver.recaptcha-site-key');

    // Authentication routes
    Route::post('/login', [DriverAuthController::class, 'login'])
        ->middleware(['throttle:5,1', 'api.route'])
        ->name('api.driver.login');

    // Password reset routes
    Route::post('/forgot-password', [DriverAuthController::class, 'forgotPassword'])
        ->middleware(['throttle:3,1'])
        ->name('api.driver.forgot-password');

    Route::post('/reset-password', [DriverAuthController::class, 'resetPassword'])
        ->middleware(['throttle:3,1'])
        ->name('api.driver.reset-password');

    // Registration routes
    Route::get('/registration-data', [DriverRegistrationController::class, 'getRegistrationData'])
        ->name('api.driver.registration-data');
    Route::get('/vehicle-types/{vehicleId}', [DriverRegistrationController::class, 'getVehicleTypes'])
        ->name('api.driver.vehicle-types');
    Route::get('/vehicle-sizes/{typeId}', [DriverRegistrationController::class, 'getVehicleSizes'])
        ->name('api.driver.vehicle-sizes');
    Route::post('/register', [DriverRegistrationController::class, 'register'])
        ->middleware(['throttle:3,1'])
        ->name('api.driver.register');

});

// Protected routes (require Sanctum authentication)
Route::prefix('driver')->middleware(['auth:sanctum', 'driver.guard'])->group(function () {

    // Authentication routes
    Route::post('/logout', [DriverAuthController::class, 'logout'])
        ->name('api.driver.logout');

    // Profile management routes
    Route::get('/profile', [DriverProfileController::class, 'show'])
        ->name('api.driver.profile');

    Route::put('/profile', [DriverProfileController::class, 'update'])
        ->name('api.driver.profile.update');

    // Alternative route for multipart form data (POST with _method=PUT)
    Route::post('/profile', [DriverProfileController::class, 'update'])
        ->name('api.driver.profile.update.multipart');

    Route::post('/change-password', [DriverProfileController::class, 'changePassword'])
        ->name('api.driver.change-password');

    Route::get('/profile/stats', [DriverProfileController::class, 'getStats'])
        ->name('api.driver.profile.stats');

    Route::get('/profile/additional-data', [DriverProfileController::class, 'getAdditionalData'])
        ->name('api.driver.profile.additional-data');

    Route::post('/refresh-token', [DriverAuthController::class, 'refreshToken'])
        ->name('api.driver.refresh-token');

    // Task management routes
    Route::prefix('tasks')->group(function () {
        Route::get('/', [DriverTaskController::class, 'index'])
            ->name('api.driver.tasks.index');

        Route::get('/history/completed', [DriverTaskController::class, 'history'])
            ->name('api.driver.tasks.history');

        Route::get('/{task}', [DriverTaskController::class, 'show'])
            ->name('api.driver.tasks.show');

        Route::post('/{task}/accept', [DriverTaskController::class, 'accept'])
            ->name('api.driver.tasks.accept');

        Route::post('/{task}/reject', [DriverTaskController::class, 'reject'])
            ->name('api.driver.tasks.reject');

        Route::put('/{task}/status', [DriverTaskController::class, 'updateStatus'])
            ->name('api.driver.tasks.update-status');

        Route::get('/{task}/logs', [DriverTaskController::class, 'getLogs'])
            ->name('api.driver.tasks.logs');

        Route::post('/{task}/notes', [DriverTaskController::class, 'addNote'])
            ->name('api.driver.tasks.add-note');
    });

    // Task Ads routes
    Route::prefix('task-ads')->group(function () {
        Route::get('/stats', [DriverTaskAdsController::class, 'getStats'])
            ->name('api.driver.task-ads.stats');

        Route::get('/', [DriverTaskAdsController::class, 'index'])
            ->name('api.driver.task-ads.index');

        Route::get('/{id}', [DriverTaskAdsController::class, 'show'])
            ->name('api.driver.task-ads.show');

        Route::get('/{id}/offers', [DriverTaskAdsController::class, 'getAdOffers'])
            ->name('api.driver.task-ads.offers');

        Route::post('/{id}/offers', [DriverTaskAdsController::class, 'submitOffer'])
            ->name('api.driver.task-ads.submit-offer');

        Route::put('/offers/{id}', [DriverTaskAdsController::class, 'updateOffer'])
            ->name('api.driver.task-ads.update-offer');

        Route::post('/offers/{id}/accept', [DriverTaskAdsController::class, 'acceptTask'])
            ->name('api.driver.task-ads.accept-task');

        Route::post('/offers/{id}/assign-task', [DriverTaskAdsController::class, 'assignTaskByOffer'])
            ->name('api.driver.task-ads.assign-task-by-offer');
    });

    // Driver offers routes
    Route::get('/my-offers', [DriverTaskAdsController::class, 'myOffers'])
        ->name('api.driver.my-offers');

    // Direct offers routes (for backward compatibility)
    Route::post('/offers/{id}/assign-task', [DriverTaskAdsController::class, 'assignTaskByOffer'])
        ->name('api.driver.offers.assign-task');

    // Pending task routes
    Route::get('/pending-task', [DriverTaskController::class, 'getPendingTask'])
        ->name('api.driver.pending-task');

    Route::post('/accept-task', [DriverTaskController::class, 'acceptTask'])
        ->name('api.driver.accept-task');

    Route::post('/reject-task', [DriverTaskController::class, 'rejectTask'])
        ->name('api.driver.reject-task');

    // Location and status routes
    Route::prefix('location')->group(function () {
        Route::post('/', [DriverLocationController::class, 'updateLocation'])
            ->middleware('throttle:location')
            ->name('api.driver.location.update');

        Route::get('/status', [DriverLocationController::class, 'getCurrentStatus'])
            ->name('api.driver.location.status');
    });

    Route::post('/status', [DriverLocationController::class, 'updateStatus'])
        ->name('api.driver.status.update');

    Route::post('/fcm-token', [DriverLocationController::class, 'updateFcmToken'])
        ->name('api.driver.fcm-token');

    // Wallet and financial routes
    Route::prefix('wallet')->group(function () {
        Route::get('/', [DriverWalletController::class, 'getWallet'])
            ->name('api.driver.wallet');

        Route::get('/transactions', [DriverWalletController::class, 'getTransactions'])
            ->name('api.driver.wallet.transactions');

        Route::get('/earnings/stats', [DriverWalletController::class, 'getEarningsStats'])
            ->name('api.driver.wallet.earnings-stats');
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [DriverNotificationController::class, 'index'])
            ->name('api.driver.notifications.index');

        Route::post('/{notification}/read', [DriverNotificationController::class, 'markAsRead'])
            ->name('api.driver.notifications.mark-read');

        Route::post('/read-all', [DriverNotificationController::class, 'markAllAsRead'])
            ->name('api.driver.notifications.mark-all-read');

        Route::delete('/{notification}', [DriverNotificationController::class, 'delete'])
            ->name('api.driver.notifications.delete');

        Route::get('/settings', [DriverNotificationController::class, 'getSettings'])
            ->name('api.driver.notifications.settings');

        Route::put('/settings', [DriverNotificationController::class, 'updateSettings'])
            ->name('api.driver.notifications.update-settings');

        // Test routes (for development only)
        Route::post('/test/create', [DriverNotificationTestController::class, 'createTestNotifications'])
            ->name('api.driver.notifications.test.create');

        Route::delete('/test/clear', [DriverNotificationTestController::class, 'clearAllNotifications'])
            ->name('api.driver.notifications.test.clear');

        Route::get('/test/stats', [DriverNotificationTestController::class, 'getNotificationStats'])
            ->name('api.driver.notifications.test.stats');
    });

});

/*
|--------------------------------------------------------------------------
| Rate Limiting Configuration
|--------------------------------------------------------------------------
|
| Define custom rate limits for different types of API calls:
| - login: 5 attempts per minute
| - location: 60 updates per minute (1 per second)
| - general: 60 requests per minute
|
*/
