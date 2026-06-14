<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureGuardIs;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\EnsureCorrectGuard;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\admin\RolesController;
use App\Http\Controllers\admin\TasksController;
use App\Http\Controllers\admin\TeamsController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\admin\UserCommissionsController;
use App\Http\Controllers\admin\UserWalletsController;
use App\Http\Controllers\Auth\CaptchaController;
use App\Http\Controllers\admin\DriversController;
use App\Http\Controllers\admin\WalletsController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\admin\TaskClaimsController;
use App\Http\Controllers\admin\TasksAdsController;
use App\Http\Controllers\admin\CustomersController;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\admin\TeamWalletController;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\PushNotificationsController;
use App\Http\Controllers\admin\settings\TagsController;
use App\Http\Controllers\admin\PlatformWalletController;
use App\Http\Controllers\laravel_example\UserManagement;
use App\Http\Controllers\admin\settings\BackupController;
use App\Http\Controllers\admin\settings\PointsController;
use App\Http\Controllers\admin\settings\RoutesController;
use App\Http\Controllers\admin\CustomsClearanceController;
use App\Http\Controllers\admin\settings\PricingController;
use App\Http\Controllers\admin\settings\SettingsController;
use App\Http\Controllers\admin\settings\TemplateController;
use App\Http\Controllers\admin\settings\VehiclesController;
use App\Http\Controllers\admin\settings\BlockagesController;
use App\Http\Controllers\admin\settings\GeofencesController;
use App\Http\Controllers\admin\settings\PricingTemplateController;
use App\Http\Controllers\admin\settings\SystemStatisticsController;
use App\Http\Controllers\admin\settings\ClearancePricingTemplateController;
use App\Http\Controllers\admin\CustomsClearanceOffersController as AdminOffersController;
use App\Http\Controllers\admin\PlatformReportsController;
use App\Http\Controllers\admin\ProductController;
use App\Http\Controllers\admin\CompanyManagementController;

Route::get('/lang/{locale}', [LanguageController::class, 'swap'])->name('lang.switch');
Route::get('/active-account', function () {

    dd('hello');
})->name("active-account-test");

Route::get('/chosen/vehicles/types/{vehicle}', [VehiclesController::class, 'getTypes']);
Route::get('/chosen/vehicles/sizes/{type}', [VehiclesController::class, 'getSizes']);

Route::get('/refresh-captcha', [CaptchaController::class, 'refresh'])->name('captcha.refresh');
Route::get('/test-signit', [SignatureController::class, 'testOAuth']);

Route::get('/test-signature', [SignatureController::class, 'testSignatureRequest']);

// Public Share Task Route
Route::get('/share/task/{id}', [App\Http\Controllers\admin\TaskShareController::class, 'share'])->name('admin.tasks.share_link');

// ──────────────────────────────────────────────────────────────────────────────
// Payment Routes (no session auth required — secured via payment_token)
// ──────────────────────────────────────────────────────────────────────────────
Route::prefix('payment')->name('payment.')->group(function () {
    Route::post('/initiate',            [PaymentController::class, 'initiatePayment'])->name('initiate');
    Route::get('/callback',             [PaymentController::class, 'handleCallback'])->name('callback');
    Route::get('/result/{status}/{token}', [PaymentController::class, 'showResult'])->name('result');
    Route::get('/{token}',              [PaymentController::class, 'showPaymentPage'])->name('page');
});

Route::middleware('rate.limit')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('/register/', [RegisterController::class, 'index'])->name('auth.register');

        Route::post('/register/customer', [RegisterController::class, 'registerCustomer'])->name('register.customer');
        Route::post('/register/driver', [RegisterController::class, 'registerDriver'])->name('register.driver');

        // 4. Verify Email Route
        Route::get('/verify/email/{token}', [RegisterController::class, 'verifyEmail'])->name('verify.email');
        Route::post('/resend-verification', [RegisterController::class, 'resendVerification'])->name('resend.verification');

        Route::get('/verify/sent/{email}', function ($email) {
            return view('auth.verify-email', compact('email'));
        })->name('verify.email.sent');

        Route::get('/verify/manual', function (Request $request) {
            $email = $request->email;
            return view('auth.verify-email-manual', compact('email'));
        })->name('verify.manual');

        Route::get('/forgot-password', [RegisterController::class, 'showRequestForm'])->name('password.request');
        Route::post('/forgot-password', [RegisterController::class, 'sendResetLink'])->name('password.reset.request');

        // Reset password
        Route::get('/reset-password', [RegisterController::class, 'showResetForm'])->name('password.reset.form');
        Route::post('/reset-password', [RegisterController::class, 'updatePassword'])->name('password.reset.submit');
    });





    Route::middleware([config('jetstream.auth_session')])->group(function () {
        // Payment routes have been moved to the unified 'payment.' prefix earlier in this file.

        Route::get('/', function () {
            if (Auth::guard('driver')->check()) {
                return redirect()->route('driver.dashboard');
            } elseif (Auth::guard('customer')->check()) {
                return redirect()->route('customer.dashboard');
            } elseif (Auth::guard('web')->check()) {
                if (Auth::guard('web')->user()->investor) {
                    return redirect()->route('investor.dashboard');
                }
                return redirect()->route('user.dashboard');
            } else {
                return redirect()->route('login');
            }
        });

        Route::get('/profile', function () {
            if (Auth::guard('driver')->check()) {
                return redirect()->route('driver.profile');
            } elseif (Auth::guard('customer')->check()) {
                return redirect()->route('customer.profile');
            } elseif (Auth::guard('web')->check()) {
                if (Auth::guard('web')->user()->investor) {
                    return redirect()->route('investor.profile');
                }
                return redirect()->route('user.profile');
            } else {
                return redirect()->route('login');
            }
        });

        Route::post('/custom-logout', [RegisterController::class, 'logout'])->name('custom.logout');



        Route::middleware(['guard.strict:driver'])->group(function () {
            Route::prefix('driver')->group(function () {
                Route::get('/dashboard', [App\Http\Controllers\driver\DashboardController::class, 'index'])->name('driver.dashboard');

                Route::get('/profile', [App\Http\Controllers\driver\DashboardController::class, 'profile'])->name('driver.profile');
                Route::post('/profile/update', [App\Http\Controllers\driver\DashboardController::class, 'updateProfile'])->name('driver.profile.update');
                Route::delete('/profile/delete/{id}', [App\Http\Controllers\driver\DashboardController::class, 'deleteAccount'])->name('driver.profile.delete');

                Route::post('/update-location', [App\Http\Controllers\driver\DashboardController::class, 'updateLocation'])->name('driver.location');
                Route::post('/respond/task', [App\Http\Controllers\driver\DashboardController::class, 'respondToTask'])->name('driver.respond.task');
                Route::post('/task/histories', [App\Http\Controllers\driver\DashboardController::class, 'taskAddToHistories'])->name('task-histories.store');
                Route::post('/task/histories', [App\Http\Controllers\driver\DashboardController::class, 'taskAddToHistories'])->name('task-histories.store');
                Route::get('/task/current/history/{id}', [App\Http\Controllers\driver\DashboardController::class, 'getCurrentTaskHistory'])->name('task-histories.current');
                Route::post('/task/update-status', [App\Http\Controllers\driver\DashboardController::class, 'updateStatus'])->name('driver.task.updateStatus');

                Route::get('/task/list', [App\Http\Controllers\driver\TasksController::class, 'index'])->name('driver.task.list');
                Route::get('/task/list/data', [App\Http\Controllers\driver\TasksController::class, 'getData'])->name('driver.task.data');
                Route::get('/task/list/show/{id}', [App\Http\Controllers\driver\TasksController::class, 'show'])->name('driver.task.show');


                Route::get('/wallet', [App\Http\Controllers\driver\WalletController::class, 'index'])->name('driver.wallet.wallet');
                Route::get('/wallet/data', [App\Http\Controllers\driver\WalletController::class, 'getData'])->name('driver.wallet.data');

                Route::get('/ads', [App\Http\Controllers\driver\TasksAdsController::class, 'index'])->name('driver.ads.ads');
                Route::get('/ads/data', [App\Http\Controllers\driver\TasksAdsController::class, 'getData'])->name('driver.ads.data');
                Route::get('/ads/show/{id}', [App\Http\Controllers\driver\TasksAdsController::class, 'show'])->name('driver.ads.show');
                Route::get('/ads/offers/show/', [App\Http\Controllers\driver\TasksAdsController::class, 'getOffers'])->name('driver.offers.data');
                Route::post('/ads/offers/store/', [App\Http\Controllers\driver\TasksAdsController::class, 'storeOffers'])->name('driver.offers.store');
                Route::get('/ads/offers/accept/task/{id}', [App\Http\Controllers\driver\TasksAdsController::class, 'assignTaskByOffer'])->name('driver.offers.assign');
            });
        });

        // Customer routes


        Route::middleware(['guard.strict:customer'])->group(function () {
            Route::prefix('customer')->group(function () {
                Route::get('/dashboard', [App\Http\Controllers\customer\DashboardController::class, 'index'])->name('customer.dashboard');
                Route::get('/profile', [App\Http\Controllers\customer\DashboardController::class, 'profile'])->name('customer.profile');
                Route::post('/profile/update', [App\Http\Controllers\customer\DashboardController::class, 'updateProfile'])->name('customer.profile.update');
                Route::delete('/profile/delete/{id}', [App\Http\Controllers\customer\DashboardController::class, 'deleteAccount'])->name('customer.profile.delete');

                Route::post('/tasks/validate-step1', [App\Http\Controllers\customer\TasksController::class, 'validateStep1'])->name('customer.task.validateStep1');
                Route::post('/tasks/validate-step2', [App\Http\Controllers\customer\TasksController::class, 'validateStep2'])->name('customer.task.validateStep2');
                Route::post('/tasks/store', [App\Http\Controllers\customer\TasksController::class, 'store'])->name('customer.task.store');
                Route::get('tasks/edit/{id}', [App\Http\Controllers\customer\TasksController::class, 'edit'])->name('customer.tasks.edit');
                Route::post('tasks/edit', [App\Http\Controllers\customer\TasksController::class, 'update'])->name('customer.tasks.update');

                Route::get('/tasks/get/tasks', [App\Http\Controllers\customer\DashboardController::class, 'getTasks'])->name('customer.task.get');
                Route::get('tasks/tracking/{id}', [App\Http\Controllers\customer\TasksController::class, 'taskTracking'])->name('customer.tasks.tracking');

                // Customer Tasks Management
                Route::get('/tasks', [App\Http\Controllers\customer\TasksController::class, 'index'])->name('customer.tasks.index');
                Route::get('/tasks/data', [App\Http\Controllers\customer\TasksController::class, 'getData'])->name('customer.tasks.data');
                Route::get('/tasks/show/{id}', [App\Http\Controllers\customer\TasksController::class, 'show'])->name('customer.tasks.show');
                Route::get('/tasks/track/{id}', [App\Http\Controllers\customer\TasksController::class, 'track'])->name('customer.tasks.track');
                Route::get('/tasks/report', [App\Http\Controllers\customer\TasksController::class, 'generateReport'])->name('customer.tasks.report');
                Route::get('/tasks/download-policy/{id}', [App\Http\Controllers\customer\TasksController::class, 'downloadTaskPolicy'])->name('customer.tasks.download-policy');
                Route::get('/tasks/policy-custom/{id}', [App\Http\Controllers\customer\TasksController::class, 'printCustomPolicy'])->name('customer.tasks.policy_custom');
                Route::get('/tasks/invoice/{id}', [App\Http\Controllers\customer\TasksController::class, 'downloadTaskInvoice'])->name('customer.tasks.invoice');

                Route::post('/tasks/export-excel', [App\Http\Controllers\customer\TasksController::class, 'exportToExcel'])->name('customer.tasks.export-excel');

                // Customer Wallet Management
                Route::get('/wallet', [App\Http\Controllers\customer\WalletController::class, 'index'])->name('customer.wallet.index');
                Route::get('/wallet/data', [App\Http\Controllers\customer\WalletController::class, 'getData'])->name('customer.wallet.data');
                Route::get('/wallet/transactions/{id}/receipt', [App\Http\Controllers\customer\WalletController::class, 'downloadCreditReceipt'])->name('customer.wallet.receipt');

                // Customer Ads Management
                Route::get('/ads', [App\Http\Controllers\customer\AdsController::class, 'index'])->name('customer.ads.index');
                Route::get('/ads/data', [App\Http\Controllers\customer\AdsController::class, 'getData'])->name('customer.ads.data');
                Route::get('/ads/show/{id}', [App\Http\Controllers\customer\AdsController::class, 'show'])->name('customer.ads.show');
                Route::get('/tasks/invoice/{id}', [App\Http\Controllers\customer\TasksController::class, 'downloadTaskInvoice'])->name('customer.tasks.invoice');
                Route::get('/ads/offers/show/', [App\Http\Controllers\customer\AdsController::class, 'getOffers'])->name('customer.offers.data');
                Route::get('/ads/offers/accept/{id}', [App\Http\Controllers\customer\AdsController::class, 'acceptOffer'])->name('customer.ads.offers.accept');
                Route::get('/ads/offers/retract/{id}', [App\Http\Controllers\customer\AdsController::class, 'retractOffer'])->name('customer.ads.offers.retract');

                // Customs Clearance Agent Mange Ads
                Route::get('customs-clearances/ads', [App\Http\Controllers\customer\CustomsClearanceController::class, 'index'])->name('customer.customs-clearances.ads');
                Route::get('customs-clearances/ads/data', [App\Http\Controllers\customer\CustomsClearanceController::class, 'getData'])->name('customer.customs-clearances.ads.data');
                Route::get('customs-clearances/ads/show/{id}', [App\Http\Controllers\customer\CustomsClearanceController::class, 'show'])->name('customer.customs-clearances.ads.show');
                Route::get('customs-clearances/ads/offers/show/', [App\Http\Controllers\customer\CustomsClearanceController::class, 'getOffers'])->name('customer.customs-clearances.offers.data');
                Route::post('customs-clearances/ads/offers/store/', [App\Http\Controllers\customer\CustomsClearanceController::class, 'storeOffers'])->name('customer.customs-clearances.offers.store');
                Route::get('customs-clearances/ads/offers/accept/task/{id}', [App\Http\Controllers\customer\CustomsClearanceController::class, 'assignTaskByOffer'])->name('customer.customs-clearances.offers.assign');


                // Customer Customs Clearance orders - تم نقله إلى ملف منفصل
                Route::get('customs-clearances/orders', [App\Http\Controllers\customer\CustomsClearanceOrdersController::class, 'index'])->name('customer.customs-clearances.orders');
                Route::get('customs-clearances/orders/data', [App\Http\Controllers\customer\CustomsClearanceOrdersController::class, 'data'])->name('customer.customs-clearances.orders.data');
                Route::get('customs-clearances/orders/show/{id}', [App\Http\Controllers\customer\CustomsClearanceOrdersController::class, 'show'])->name('customer.customs-clearances.orders.show');
                Route::post('/customs-clearances/orders/histories', [App\Http\Controllers\customer\CustomsClearanceOrdersController::class, 'taskAddToHistories'])->name('customer.customs-clearances.orders.histories.store');
                Route::post('/customs-clearances/orders/update-status', [App\Http\Controllers\customer\CustomsClearanceOrdersController::class, 'updateStatus'])->name('customer.customs-clearances.orders.updateStatus');
            });
        });


        Route::middleware(['guard.strict:web'])->group(function () {
            Route::post('/push-subscribe', function (Request $request) {
                $request->user()->updatePushSubscription(
                    $request->endpoint,
                    $request->keys['p256dh'],
                    $request->keys['auth']
                );
            })->name('notifications.subscribe');

            Route::get('/send-notification', [PushNotificationsController::class, 'index'])->name('notifications.send');
            Route::prefix('admin')->middleware('block.investor')->group(function () {

                Route::get('/', [DashboardController::class, 'index'])->name('user.dashboard');

                // Task Invoice Download
                Route::get('/tasks/{id}/invoice', [TasksController::class, 'downloadTaskInvoice'])->name('tasks.invoice');

                // Wallet Credit Receipt Download
                Route::get('/wallets/transactions/{id}/receipt', [WalletsController::class, 'downloadCreditReceipt'])->name('wallets.transaction.receipt');

                // Admin System Notifications
                Route::middleware(['can:view_notifications'])->group(function () {
                    Route::get('/system-notifications', [App\Http\Controllers\admin\NotificationController::class, 'index'])->name('system.notifications.index');
                    Route::get('/system-notifications/unread-count', [App\Http\Controllers\admin\NotificationController::class, 'unreadCount'])->name('system.notifications.unread-count');
                    Route::post('/system-notifications/{id}/read', [App\Http\Controllers\admin\NotificationController::class, 'markAsRead'])->name('system.notifications.read');
                    Route::post('/system-notifications/mark-all-read', [App\Http\Controllers\admin\NotificationController::class, 'markAllAsRead'])->name('system.notifications.mark-all-read');
                });

                // Signature Management Routes
                Route::prefix('signature')->name('admin.signature.')->group(function () {
                    Route::post('/upload', [App\Http\Controllers\admin\SignatureController::class, 'upload'])->name('upload');
                    Route::get('/get', [App\Http\Controllers\admin\SignatureController::class, 'get'])->name('get');
                    Route::post('/delete', [App\Http\Controllers\admin\SignatureController::class, 'delete'])->name('delete');
                });

                // Manual Notifications Routes
                Route::prefix('notifications')->name('admin.notifications.')->group(function () {
                    Route::post('send-to-driver', [App\Http\Controllers\admin\ManualNotificationController::class, 'sendToDriver'])
                        ->name('send.driver');

                    Route::post('send-to-multiple-drivers', [App\Http\Controllers\admin\ManualNotificationController::class, 'sendToMultipleDrivers'])
                        ->name('send.multiple.drivers');

                    Route::post('send-to-customer', [App\Http\Controllers\admin\ManualNotificationController::class, 'sendToCustomer'])
                        ->name('send.customer');

                    Route::post('send-to-task-driver', [App\Http\Controllers\admin\ManualNotificationController::class, 'sendToTaskDriver'])
                        ->name('send.task.driver');

                    Route::post('send-to-task-customer', [App\Http\Controllers\admin\ManualNotificationController::class, 'sendToTaskCustomer'])
                        ->name('send.task.customer');
                });
                Route::get('/dashboard', [DashboardController::class, 'driversIndex'])->name('dashboard.dashboard');
                Route::get('dashboard/tasks/data', [DashboardController::class, 'getTasksData'])->name('dashboard.tasks.data');
                Route::get('dashboard/drivers/data', [DashboardController::class, 'getDriversData'])->name('dashboard.drivers.data');

                Route::get('/profile', [UsersController::class, 'profile'])->name('user.profile');
                Route::post('/profile/update', [UsersController::class, 'updateProfile'])->name('user.profile.update');

                // Notes routes
                Route::prefix('notes')->group(function () {
                    Route::get('/', [App\Http\Controllers\NotesController::class, 'index'])->name('notes.index');
                    Route::post('/', [App\Http\Controllers\NotesController::class, 'store'])->name('notes.store');
                    Route::put('/{note}', [App\Http\Controllers\NotesController::class, 'update'])->name('notes.update');
                    Route::delete('/{note}', [App\Http\Controllers\NotesController::class, 'destroy'])->name('notes.destroy');
                });


                Route::get('/users', [UsersController::class, 'index'])->name('user.users');
                Route::get('/users/data', [UsersController::class, 'getData'])->name('user.data');
                Route::post('/users', [UsersController::class, 'store'])->name('user.create');
                Route::post('/users/reset-password/{id}', [UsersController::class, 'resetPass'])->name('user.reset');
                Route::post('/users/status', [UsersController::class, 'chang_status'])->name('user.status');
                Route::get('/users/edit/{id}', [UsersController::class, 'edit'])->name('user.show');
                Route::delete('/users/delete/{id}', [UsersController::class, 'destroy'])->name('user.delete');

                // Investor Management Routes
                
        // Banks
        Route::resource('banks', \App\Http\Controllers\admin\BankController::class)->names('admin.banks');

        Route::prefix('investors')->name('admin.investors.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\admin\InvestorController::class, 'index'])->name('index');
                    Route::get('/data', [\App\Http\Controllers\admin\InvestorController::class, 'getData'])->name('data');
                    Route::post('/store', [\App\Http\Controllers\admin\InvestorController::class, 'store'])->name('store');
                    Route::post('/reset-password', [\App\Http\Controllers\admin\InvestorController::class, 'resetPass'])->name('reset-password');
                    Route::get('/show/{id}', [\App\Http\Controllers\admin\InvestorController::class, 'show'])->name('show');
                    Route::get('/{id}/available-tasks', [\App\Http\Controllers\admin\InvestorController::class, 'getAvailableHistoricalTasks'])->name('available-tasks');
                    Route::post('/link-tasks', [\App\Http\Controllers\admin\InvestorController::class, 'linkHistoricalTasks'])->name('link-tasks');
                    Route::delete('/delete/{id}', [\App\Http\Controllers\admin\InvestorController::class, 'destroy'])->name('delete');

                    // Wallet management
                    Route::get('/{userId}/invest-wallet', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'show'])->name('invest-wallet');
                    Route::get('/{userId}/invest-wallet/transactions', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'getTransactions'])->name('invest-wallet.getTransactions');
                    Route::post('/invest-wallet/transaction', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'addTransaction'])->name('invest-wallet.addTransaction');
                    Route::post('/invest-wallet/transaction/convert/{id}', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'convertTransactionToRefund'])->name('invest-wallet.convertTransaction');
                    Route::delete('/invest-wallet/transaction/delete/{id}', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'destroyTransaction'])->name('invest-wallet.destroyTransaction');
                    Route::delete('/invest-wallet/transaction/delete-settlement/{id}', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'destroySettlementTransaction'])->name('invest-wallet.destroySettlement');
                    Route::delete('/invest-wallet/transaction/cancel-investment/{id}', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'cancelInvestment'])->name('invest-wallet.cancelInvestment');
                    Route::get('/invest-wallet/transaction/receipt/{id}', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'downloadReceipt'])->name('invest-wallet.downloadReceipt');
                    
                    Route::get('/{userId}/invest-wallet/check-funding', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'checkFunding'])->name('invest-wallet.checkFunding');
                    Route::post('/{userId}/invest-wallet/fix-funding', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'fixFunding'])->name('invest-wallet.fixFunding');

                    // Missing Payments Tool
                    Route::get('/{userId}/invest-wallet/missing-payments', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'getMissingPayments'])->name('invest-wallet.missingPayments');
                    Route::post('/invest-wallet/restore-payment', [\App\Http\Controllers\admin\InvestorWalletsController::class, 'restorePayment'])->name('invest-wallet.restorePayment');
                });

                // B2B Module Routes
                Route::prefix('b2b')->name('b2b.')->group(function () {
                    // Provinces Management
                    Route::get('/provinces', [CompanyManagementController::class, 'provincesIndex'])->name('provinces');
                    Route::get('/provinces/data', [CompanyManagementController::class, 'getProvincesData'])->name('provinces.data');
                    Route::post('/provinces/store', [CompanyManagementController::class, 'storeProvince'])->name('provinces.store');
                    Route::get('/provinces/{id}', [CompanyManagementController::class, 'getProvince'])->name('provinces.show');
                    Route::delete('/provinces/{id}', [CompanyManagementController::class, 'deleteProvince'])->name('provinces.delete');

                    // Companies Management
                    Route::get('/companies', [CompanyManagementController::class, 'companiesIndex'])->name('companies');
                    Route::get('/companies/data', [CompanyManagementController::class, 'getCompaniesData'])->name('companies.data');

                    // Warehouses Management
                    Route::get('/warehouses', [CompanyManagementController::class, 'warehousesIndex'])->name('warehouses');
                    Route::get('/warehouses/data', [CompanyManagementController::class, 'getWarehousesData'])->name('warehouses.data');
                    Route::post('/warehouses/store', [CompanyManagementController::class, 'storeWarehouse'])->name('warehouses.store');
                    Route::get('/warehouses/{id}', [CompanyManagementController::class, 'getWarehouse'])->name('warehouses.show');
                    Route::delete('/warehouses/{id}', [CompanyManagementController::class, 'deleteWarehouse'])->name('warehouses.delete');

                    // End Clients Management
                    Route::get('/end-clients', [CompanyManagementController::class, 'endClientsIndex'])->name('end-clients');
                    Route::get('/end-clients/data', [CompanyManagementController::class, 'getEndClientsData'])->name('end-clients.data');
                    Route::post('/end-clients/store', [CompanyManagementController::class, 'storeEndClient'])->name('end-clients.store');
                    Route::post('/end-clients/import', [CompanyManagementController::class, 'importEndClients'])->name('end-clients.import');
                    Route::get('/end-clients/{id}', [CompanyManagementController::class, 'getEndClient'])->name('end-clients.show');
                    Route::delete('/end-clients/{id}', [CompanyManagementController::class, 'deleteEndClient'])->name('end-clients.delete');

                    // Pricing Matrix Management
                    Route::get('/pricing/{companyId}', [\App\Http\Controllers\admin\CompanyRoutePricingController::class, 'index'])->name('pricing.index');
                    Route::get('/pricing/{companyId}/routes', [\App\Http\Controllers\admin\CompanyRoutePricingController::class, 'getRoutes'])->name('pricing.routes');
                    Route::post('/pricing/routes/store', [\App\Http\Controllers\admin\CompanyRoutePricingController::class, 'storeRoute'])->name('pricing.routes.store');
                    Route::get('/pricing/routes/{id}', [\App\Http\Controllers\admin\CompanyRoutePricingController::class, 'getRoute'])->name('pricing.routes.show');
                    Route::delete('/pricing/routes/{id}', [\App\Http\Controllers\admin\CompanyRoutePricingController::class, 'deleteRoute'])->name('pricing.routes.delete');

                    // Company Pricing Config (Commission / VAT)
                    Route::get('/config/{companyId}', [\App\Http\Controllers\admin\CompanyRoutePricingController::class, 'configIndex'])->name('config.index');
                    Route::post('/config/{companyId}', [\App\Http\Controllers\admin\CompanyRoutePricingController::class, 'saveConfig'])->name('config.save');

                    // AJAX helpers for Task Creation
                    Route::get('/get-warehouses/{companyId}', [CompanyManagementController::class, 'getWarehouses'])->name('get-warehouses');
                    Route::get('/get-end-clients/{companyId}', [CompanyManagementController::class, 'getEndClients'])->name('get-end-clients');
                    Route::post('/resolve-price', [\App\Http\Controllers\admin\CompanyRoutePricingController::class, 'resolvePrice'])->name('resolve-price');

                    // ── B2B Task Routes ───────────────────────────────────
                    Route::prefix('tasks')->name('tasks.')->group(function () {
                        Route::post('/', [\App\Http\Controllers\admin\B2bTaskController::class, 'store'])->name('store');
                        Route::put('/{task}', [\App\Http\Controllers\admin\B2bTaskController::class, 'update'])->name('update');
                        Route::get('/{task}/data', [\App\Http\Controllers\admin\B2bTaskController::class, 'getData'])->name('data');
                    });

                    // ── B2B API Helpers for Modal ─────────────────────────
                    Route::prefix('api')->name('api.')->group(function () {
                        Route::get('/companies/{companyId}/warehouses', [\App\Http\Controllers\admin\B2bTaskController::class, 'getWarehouses'])->name('warehouses');
                        Route::get('/companies/{companyId}/end-clients', [\App\Http\Controllers\admin\B2bTaskController::class, 'getEndClients'])->name('end-clients');
                        Route::get('/vehicles', [\App\Http\Controllers\admin\B2bTaskController::class, 'getVehicles'])->name('vehicles');
                        Route::get('/vehicle-sizes', [\App\Http\Controllers\admin\B2bTaskController::class, 'getVehicleSizes'])->name('vehicle-sizes');
                        Route::post('/calculate-price', [\App\Http\Controllers\admin\B2bTaskController::class, 'calculatePrice'])->name('calculate-price');
                    });
                });

                // Is-Company check (used in Task creation form)
                Route::get('/customers/{id}/is-company', [\App\Http\Controllers\admin\CompanyRoutePricingController::class, 'isCompany'])->name('customers.is-company');


                // User Commissions Routes
                Route::get('/commissions', [UserCommissionsController::class, 'index'])->name('admin.commissions.index');
                Route::get('/commissions/data', [UserCommissionsController::class, 'getData'])->name('admin.commissions.getData');
                Route::post('/commissions', [UserCommissionsController::class, 'store'])->name('admin.commissions.store');
                Route::get('/commissions/edit/{id}', [UserCommissionsController::class, 'edit'])->name('admin.commissions.edit');
                Route::delete('/commissions', [UserCommissionsController::class, 'destroy'])->name('admin.commissions.destroy');
                Route::post('/commissions/status', [UserCommissionsController::class, 'changeStatus'])->name('admin.commissions.changeStatus');
                Route::get('/commissions/customer/{customerId}', [UserCommissionsController::class, 'getCommissionsByCustomer'])->name('admin.commissions.byCustomer');
                Route::get('/commissions/generate-old-commissions', [UserCommissionsController::class, 'generateOldCommissions'])->name('admin.commissions.generateOldCommissions');

                // User Wallets Routes
                Route::get('/users/{userId}/wallet', [UserWalletsController::class, 'show'])->name('admin.user-wallets.show');
                Route::get('/users/{userId}/wallet/transactions', [UserWalletsController::class, 'getTransactions'])->name('admin.user-wallets.getTransactions');
                Route::post('/user-wallets/transaction', [UserWalletsController::class, 'addTransaction'])->name('admin.user-wallets.addTransaction');
                Route::get('/user-wallets/transaction/edit/{id}', [UserWalletsController::class, 'editTransaction'])->name('admin.user-wallets.editTransaction');
                Route::delete('/user-wallets/transaction/delete/{id}', [UserWalletsController::class, 'destroyTransaction'])->name('admin.user-wallets.destroyTransaction');
                Route::delete('/user-wallets/duplicate-commission/delete/{id}', [UserWalletsController::class, 'destroyDuplicateCommission'])->name('admin.user-wallets.destroyDuplicateCommission');

                Route::post('/user-wallets/withdrawal', [UserWalletsController::class, 'processWithdrawal'])->name('admin.user-wallets.withdrawal');
                Route::get('/user-wallets/{userId}/stats', [UserWalletsController::class, 'getWalletStats'])->name('admin.user-wallets.stats');
                Route::post('/users/{userId}/wallet/clear', [UserWalletsController::class, 'clearWallet'])->name('admin.user-wallets.clear');
                Route::get('/users/{userId}/wallet/search-task', [UserWalletsController::class, 'searchTaskForCommission'])->name('admin.user-wallets.search-task');
                Route::post('/users/{userId}/wallet/calculate-manual', [UserWalletsController::class, 'calculateManualCommission'])->name('admin.user-wallets.calculate-manual');
                Route::post('/users/{userId}/wallet/calculate-general', [UserWalletsController::class, 'calculateGeneralCommissions'])->name('admin.user-wallets.calculate-general');
                Route::post('/users/{userId}/wallet/calculate-broker', [UserWalletsController::class, 'calculateBrokerCommissions'])->name('admin.user-wallets.calculate-broker');
                Route::post('/users/{userId}/wallet/reinvest-profits', [UserWalletsController::class, 'reinvestProfits'])->name('admin.user-wallets.reinvest-profits');
                Route::get('/users/{userId}/wallet/tasks-funding', [UserWalletsController::class, 'tasksForFunding'])->name('admin.user-wallets.tasks-funding');
                Route::post('/users/{userId}/wallet/pay-task/{task}', [UserWalletsController::class, 'fundTask'])->name('admin.user-wallets.pay-task');



                Route::get('/roles', [RolesController::class, 'index'])->name('role.roles');
                Route::post('/roles', [RolesController::class, 'store'])->name('role.create');
                Route::post('/roles/edit', [RolesController::class, 'update'])->name('role.edit');
                Route::delete('/roles/delete/{id}', [RolesController::class, 'destroy'])->name('role.delete');
                Route::get('/roles/data', [RolesController::class, 'getData'])->name('role.data');
                Route::get('/roles/permissions/{guard}', [RolesController::class, 'getPermissions'])->name('role.permissions');


                Route::prefix('settings')->group(function () {
                    Route::get('/', [SettingsController::class, 'index'])->name('settings.general');
                    Route::post('/set-template', [SettingsController::class, 'setTemplate'])->name('settings.setTemplate');

                    Route::get('statistics/', [SystemStatisticsController::class, 'index'])->name('settings.statistics');
                    Route::get('statistics/data', [SystemStatisticsController::class, 'getData'])->name('settings.statistics.data');
                    Route::post('statistics/export', [SystemStatisticsController::class, 'export'])->name('settings.statistics.export');

                    Route::get('backup/', [BackupController::class, 'index'])->name('settings.backup');
                    Route::get('backup/data', [BackupController::class, 'getData'])->name('settings.backup.data');
                    Route::post('backup/create', [BackupController::class, 'create'])->name('settings.backup.create');
                    Route::get('backup/download/{backupName}', [BackupController::class, 'download'])->name('settings.backup.download');
                    Route::delete('backup/delete/{backupName}', [BackupController::class, 'delete'])->name('settings.backup.delete');
                    Route::post('backup/restore', [BackupController::class, 'restore'])->name('settings.backup.restore');
                    Route::post('backup/upload-restore', [BackupController::class, 'uploadAndRestore'])->name('settings.backup.upload-restore');
                    Route::get('backup/statistics', [BackupController::class, 'getStatistics'])->name('settings.backup.statistics');


                    Route::get('/vehicles', [VehiclesController::class, 'index'])->name('settings.vehicles');
                    Route::post('/vehicles', [VehiclesController::class, 'store'])->name('settings.vehicles.store');
                    Route::post('/vehicles/type', [VehiclesController::class, 'store_type'])->name('settings.vehicles.store.type');
                    Route::post('/vehicles/size', [VehiclesController::class, 'store_size'])->name('settings.vehicles.store.size');
                    Route::get('/vehicles/data', [VehiclesController::class, 'getData'])->name('settings.vehicles.data');
                    Route::delete('/vehicles/delete/{id}', [VehiclesController::class, 'destroy'])->name('settings.vehicles.delete');
                    Route::delete('/vehicles/type/delete/{id}', [VehiclesController::class, 'destroy_type'])->name('settings.vehicles.delete.type');
                    Route::delete('/vehicles/size/delete/{id}', [VehiclesController::class, 'destroy_size'])->name('settings.vehicles.delete.size');
                    Route::get('/vehicles/types/{vehicle}', [VehiclesController::class, 'getTypes']);
                    Route::get('/vehicles/sizes/{type}', [VehiclesController::class, 'getSizes']);


                    Route::get('/points', [PointsController::class, 'index'])->name('settings.points');
                    Route::get('/points/data', [PointsController::class, 'getData'])->name('settings.points.data');
                    Route::post('/points/get', [PointsController::class, 'getPoints'])->name('settings.points.get');
                    Route::post('/points', [PointsController::class, 'store'])->name('settings.points.store');
                    Route::get('/points/edit/{id}', [PointsController::class, 'edit'])->name('settings.points.show');
                    Route::post('/points/status/{id}', [PointsController::class, 'change_state'])->name('settings.points.status');
                    Route::delete('/points/delete/{id}', [PointsController::class, 'destroy'])->name('settings.points.delete');

                    Route::get('/tags', [TagsController::class, 'index'])->name('settings.tags');
                    Route::get('/tags/data', [TagsController::class, 'getData'])->name('settings.tags.data');
                    Route::post('/tags', [TagsController::class, 'store'])->name('settings.tags.store');
                    Route::get('/tags/edit/{id}', [TagsController::class, 'edit'])->name('settings.tags.show');
                    Route::delete('/tags/delete/{id}', [TagsController::class, 'destroy'])->name('settings.tags.delete');


                    Route::get('/geofences', [GeofencesController::class, 'index'])->name('settings.geofences');
                    Route::get('/geofences/data', [GeofencesController::class, 'getData'])->name('settings.geofences.data');
                    Route::post('/geofences', [GeofencesController::class, 'store'])->name('settings.geofences.store');
                    Route::get('/geofences/edit/{id}', [GeofencesController::class, 'edit'])->name('settings.geofences.show');
                    Route::delete('/geofences/delete/{id}', [GeofencesController::class, 'destroy'])->name('settings.geofences.delete');


                    Route::get('/blockages', [BlockagesController::class, 'index'])->name('settings.blockages');
                    Route::get('/blockages/data', [BlockagesController::class, 'getData'])->name('settings.blockages.data');
                    Route::get('/blockages/get', [BlockagesController::class, 'getBlockages'])->name('settings.blockages.get');
                    Route::post('/blockages', [BlockagesController::class, 'store'])->name('settings.blockages.store');
                    Route::get('/blockages/edit/{id}', [BlockagesController::class, 'edit'])->name('settings.blockages.show');
                    Route::post('/blockages/status/{id}', [BlockagesController::class, 'change_state'])->name('settings.blockages.status');
                    Route::delete('/blockages/delete/{id}', [BlockagesController::class, 'destroy'])->name('settings.blockages.delete');




                    Route::get('/pricing', [PricingController::class, 'index'])->name('settings.pricing');
                    Route::get('/pricing/data', [PricingController::class, 'getData'])->name('settings.pricing.data');
                    Route::post('/pricing', [PricingController::class, 'store'])->name('settings.pricing.store');
                    Route::get('/pricing/edit/{id}', [PricingController::class, 'edit'])->name('settings.pricing.show');
                    Route::post('/pricing/status/{id}', [PricingController::class, 'change_state'])->name('settings.pricing.status');
                    Route::post('/pricing/edit', [PricingController::class, 'update'])->name('settings.pricing.edit');
                    Route::delete('/pricing/delete/{id}', [PricingController::class, 'destroy'])->name('settings.pricing.delete');



                    Route::get('/templates', [TemplateController::class, 'index'])->name('settings.templates');
                    Route::get('/templates/data', [TemplateController::class, 'getData'])->name('settings.templates.data');
                    Route::get('/templates/fields', [TemplateController::class, 'getFields'])->name('settings.templates.fields');
                    Route::get('/templates/pricing', [TemplateController::class, 'getPricing'])->name('settings.templates.pricing');
                    Route::post('/templates', [TemplateController::class, 'store'])->name('settings.templates.store');
                    Route::get('/templates/edit/{id}', [TemplateController::class, 'edit'])->name('settings.templates.edit');
                    Route::post('/templates/update/', [TemplateController::class, 'update'])->name('settings.templates.update');
                    Route::post('/templates/duplicate/{id}', [TemplateController::class, 'duplicate'])->name('settings.templates.duplicate');
                    Route::delete('/templates/delete/{id}', [TemplateController::class, 'destroy'])->name('settings.templates.delete');

                    Route::post('/template/pricing', [PricingTemplateController::class, 'store'])->name('settings.templates.pricing.store');
                    Route::get('/templates/pricing/data/{id}', [PricingTemplateController::class, 'getData'])->name('settings.templates.pricing.data');
                    Route::get('/templates/pricing/edit/{id}', [PricingTemplateController::class, 'edit'])->name('settings.templates.pricing.edit');
                    Route::post('/templates/pricing/status/{id}', [PricingTemplateController::class, 'change_state'])->name('settings.templates.pricing.status');
                    Route::get('/templates/pricing/methods', [PricingTemplateController::class, 'getPricingMethod'])->name('settings.templates.pricing.methods');
                    Route::delete('/templates/pricing/delete/{id}', [PricingTemplateController::class, 'destroy'])->name('settings.templates.pricing.delete');

                    Route::post('/template/clearance/pricing', [ClearancePricingTemplateController::class, 'store'])->name('settings.templates.clearance.pricing.store');
                    Route::get('/templates/clearance/pricing/data/{id}', [ClearancePricingTemplateController::class, 'getData'])->name('settings.templates.clearance.pricing.data');
                    Route::get('/templates/clearance/pricing/edit/{id}', [ClearancePricingTemplateController::class, 'edit'])->name('settings.templates.clearance.pricing.edit');
                    Route::delete('/templates/clearance/pricing/delete/{id}', [ClearancePricingTemplateController::class, 'destroy'])->name('settings.templates.clearance.pricing.delete');
                });

                // Platform Wallet Routes
                Route::get('platform-wallet', [PlatformWalletController::class, 'index'])->name('admin.platform-wallet.index');
                Route::get('platform-wallet/data', [PlatformWalletController::class, 'data'])->name('admin.platform-wallet.data');
                Route::get('platform-wallet/statistics', [PlatformWalletController::class, 'statistics'])->name('admin.platform-wallet.statistics');
                Route::get('platform-wallet/export', [PlatformWalletController::class, 'export'])->name('admin.platform-wallet.export');
                Route::get('platform-wallet/export-excel', [PlatformWalletController::class, 'exportExcel'])->name('admin.platform-wallet.export-excel');


                Route::get('/products', [ProductController::class, 'index'])->name('products.products');
                Route::get('/products/data', [ProductController::class, 'getData'])->name('products.data');
                Route::get('/products/details/{id}/{name}', [ProductController::class, 'show'])->name('products.show');
                Route::post('/products', [ProductController::class, 'store'])->name('products.create');
                Route::get('/products/edit/{id}', [ProductController::class, 'edit'])->name('products.edit');
                Route::post('/products/status', [ProductController::class, 'chang_status'])->name('products.status');
                Route::delete('/products/delete/{id}', [ProductController::class, 'destroy'])->name('products.delete');

                // Products Vehicles Routes
                Route::get('/products/vehicles/get', [ProductController::class, 'getVehicles'])->name('products.vehicles.get');
                Route::get('/products/vehicles/data', [ProductController::class, 'getVehiclesData'])->name('products.vehicles.data');
                Route::post('/products/vehicles/store', [ProductController::class, 'storeVehicle'])->name('products.vehicles.store');
                Route::get('/products/vehicles/edit/{id}', [ProductController::class, 'editVehicle'])->name('products.vehicles.edit');
                Route::delete('/products/vehicles/delete/{id}', [ProductController::class, 'destroyVehicle'])->name('products.vehicles.delete');

                // Products Pricing Routes
                Route::get('/products/pricing/data', [ProductController::class, 'getPricingData'])->name('products.pricing.data');
                Route::post('/products/pricing/store', [ProductController::class, 'storePricing'])->name('products.pricing.store');
                Route::get('/products/pricing/edit/{id}', [ProductController::class, 'editPricing'])->name('products.pricing.edit');
                Route::delete('/products/pricing/delete/{id}', [ProductController::class, 'destroyPricing'])->name('products.pricing.delete');



                Route::get('/customers', [CustomersController::class, 'index'])->name('customers.customers');
                Route::get('/customers/account/{id}/{name}', [CustomersController::class, 'show'])->name('customers.show');
                Route::get('/customers/tasks/', [CustomersController::class, 'getCustomerTasks'])->name('customers.tasks');
                Route::get('/customers/get/customers', [CustomersController::class, 'getCustomers'])->name('customers.get');
                Route::post('/customers', [CustomersController::class, 'store'])->name('customers.create');
                Route::get('/customers/data', [CustomersController::class, 'getData'])->name('customers.data');
                Route::post('/customers/status', [CustomersController::class, 'chang_status'])->name('customers.status');
                Route::post('/customers/broker/status', [CustomersController::class, 'chang_broker_status'])->name('customers.broker.status');
                Route::get('/customers/edit/{id}', [CustomersController::class, 'edit'])->name('customers.show');
                Route::delete('/customers/delete/{id}', [CustomersController::class, 'destroy'])->name('customers.delete');
                Route::post('/customers/wallet/create', [CustomersController::class, 'createWallet'])->name('customers.wallet.create');

                Route::get('/wallets', [WalletsController::class, 'index'])->name('wallets.wallets');
                Route::get('/wallets/data', [WalletsController::class, 'getData'])->name('wallets.data');
                Route::get('/wallets/statistics', [WalletsController::class, 'getStatistics'])->name('wallets.statistics');
                Route::post('/wallets/update', [WalletsController::class, 'update'])->name('wallets.update');
                Route::get('/wallets/{wallet}/fetch-unsettled-tasks', [WalletsController::class, 'fetchUnsettledTasks'])->name('wallets.fetch-unsettled-tasks');
                Route::post('/wallets/status/{id}', [WalletsController::class, 'chang_status'])->name('wallets.status');
                Route::post('/wallets/preview/{id}', [WalletsController::class, 'change_preview'])->name('wallets.preview');
                Route::get('/wallets/transaction/show/{id}/{name}', [WalletsController::class, 'show'])->name('wallets.transaction');
                Route::get('/wallets/driver/show/{id}', [WalletsController::class, 'driverShow'])->name('wallets.driver.show');
                Route::post('/wallets/driver/payment', [WalletsController::class, 'processDriverPayment'])->name('wallets.driver.payment');
                Route::post('/wallets/{wallet}/log-payment-request', [WalletsController::class, 'logPaymentRequest'])->name('wallets.log-payment-request');
                Route::get('/wallets/{wallet}/payment-request-logs', [WalletsController::class, 'getPaymentRequestLogs'])->name('wallets.payment-request-logs');
                Route::get('/wallets/driver-tasks/{driverId}', [WalletsController::class, 'getDriverTasks'])->name('wallets.driver-tasks');
                Route::get('/wallets/transactions/{id}', [WalletsController::class, 'getDataTransactions'])->name('wallets.transactions');
                Route::get('/wallets/transaction/data', [WalletsController::class, 'getDataTransactions'])->name('wallets.transaction.data');
                Route::post('/wallets/transaction/store', [WalletsController::class, 'storeTransaction'])->name('wallets.transaction.store');
                Route::get('/wallets/transaction/edit/{id}', [WalletsController::class, 'editTransaction'])->name('wallets.transaction.edit');
                Route::delete('/wallets/transaction/delete/{id}', [WalletsController::class, 'destroy'])->name('wallets.transaction.delete');

                // Withdrawal Requests Routes
                Route::get('/wallets/withdrawals', [App\Http\Controllers\admin\WithdrawalRequestsController::class, 'index'])->name('wallets.withdrawals.index');
                Route::get('/wallets/withdrawals/data', [App\Http\Controllers\admin\WithdrawalRequestsController::class, 'getData'])->name('wallets.withdrawals.data');
                Route::post('/wallets/withdrawals/{id}/process', [App\Http\Controllers\admin\WithdrawalRequestsController::class, 'process'])->name('wallets.withdrawals.process');

                Route::get('/wallets/payment/request/{id}', [WalletsController::class, 'paymentRequest'])->name('wallets.payment_request');


                Route::get('/drivers', [DriversController::class, 'index'])->name('drivers.drivers');
                Route::get('/drivers/account/{id}/{name}', [DriversController::class, 'show'])->name('drivers.show');
                Route::get('/drivers/tasks/', [DriversController::class, 'getCustomerTasks'])->name('drivers.tasks');
                Route::post('/drivers', [DriversController::class, 'store'])->name('drivers.create');
                Route::get('/drivers/data', [DriversController::class, 'getData'])->name('drivers.data');
                Route::get('/drivers/git', [DriversController::class, 'getDrivers'])->name('drivers.git');
                Route::post('/drivers/status', [DriversController::class, 'chang_status'])->name('drivers.status');
                Route::get('/drivers/edit/{id}', [DriversController::class, 'edit'])->name('drivers.edit');
                Route::delete('/drivers/delete/{id}', [DriversController::class, 'destroy'])->name('drivers.delete');
                Route::post('/drivers/wallet/create', [DriversController::class, 'createWallet'])->name('drivers.wallet.create');


                // Basic Teams CRUD Routes
                Route::get('/teams', [TeamsController::class, 'index'])->name('teams.teams');
                Route::post('/teams', [TeamsController::class, 'store'])->name('teams.store');
                Route::get('/teams/data', [TeamsController::class, 'getData'])->name('teams.data');
                Route::get('/teams/edit/{id}', [TeamsController::class, 'edit'])->name('teams.edit');
                Route::delete('/teams/delete/{id}', [TeamsController::class, 'destroy'])->name('teams.delete');

                // Legacy route for backward compatibility
                Route::get('/teams/details/{id}', [TeamsController::class, 'show'])->name('teams.show');

                // New Team Dashboard Routes
                Route::prefix('teams/{team}')->name('teams.dashboard.')->group(function () {
                    Route::get('/dashboard', [TeamsController::class, 'dashboard'])->name('index');
                    Route::get('/drivers', [TeamsController::class, 'driversPage'])->name('drivers');
                    Route::get('/tasks', [TeamsController::class, 'tasksPage'])->name('tasks');
                    Route::get('/wallet', [TeamsController::class, 'walletPage'])->name('wallet');
                    Route::get('/task-distribution', [TeamsController::class, 'taskDistributionPage'])->name('task-distribution');
                    Route::get('/analytics', [TeamsController::class, 'analyticsPage'])->name('analytics');
                });

                // Team Data API Routes (for DataTables and AJAX)
                Route::get('/teams/drivers/', [TeamsController::class, 'getTeamDrivers'])->name('teams.drivers');
                Route::get('/teams/tasks/', [TeamsController::class, 'getTeamTasks'])->name('teams.tasks');
                Route::get('/teams/transactions/', [TeamsController::class, 'getTeamTransactions'])->name('teams.transactions');
                Route::get('/teams/{team}/filtered-tasks', [TeamsController::class, 'getFilteredTasks'])->name('teams.filtered-tasks');
                Route::get('/teams/{team}/analytics-data', [TeamsController::class, 'getAnalyticsData'])->name('teams.analytics-data');


                Route::get('/teams/wallet/transactions/data', [TeamWalletController::class, 'getDataTransactions'])->name('teams.wallet.transactions.data');
                Route::post('/teams/{team}/pay-transactions', [TeamsController::class, 'processTeamPayment'])->name('teams.pay.transactions');
                Route::get('/teams/tasks/show/{id}', [App\Http\Controllers\driver\TasksController::class, 'show'])->name('teams.task.show');

                // Team Wallet Transaction Routes
                Route::post('/teams/wallet/transaction/store', [TeamWalletController::class, 'storeTransaction'])->name('teams.wallet.transaction.store');
                Route::get('/teams/wallet/transaction/edit/{id}', [TeamWalletController::class, 'editTransaction'])->name('teams.wallet.transaction.edit');


                Route::delete('/teams/wallet/transaction/delete/{id}', [TeamWalletController::class, 'destroy'])->name('teams.wallet.transaction.delete');
                Route::get('/teams/wallets/{id}/{name}', [TeamWalletController::class, 'index'])->name('teams.wallet');

                // Team Payment Request Routes
                Route::get('/teams/wallet/check-team-leader', [TeamWalletController::class, 'checkTeamLeader'])->name('teams.wallet.check-team-leader');
                Route::post('/teams/wallet/log-payment-request', [TeamWalletController::class, 'logTeamPaymentRequest'])->name('teams.wallet.log-payment-request');
                Route::get('/teams/wallet/payment-request-logs', [TeamWalletController::class, 'getTeamPaymentRequestLogs'])->name('teams.wallet.payment-request-logs');
                Route::get('/teams/wallet/team-tasks/{teamId}', [TeamWalletController::class, 'getTeamTasks'])->name('teams.wallet.team-tasks');
                Route::post('/teams/wallet/generate-payment-pdf', [TeamWalletController::class, 'generateTeamPaymentPDF'])->name('teams.wallet.generate-payment-pdf');



                // Customs Clearances Routes
                Route::get('customs-clearances', [CustomsClearanceController::class, 'index'])->name('admin.customs-clearances.index');
                Route::get('customs-clearances/data', [CustomsClearanceController::class, 'data'])->name('admin.customs-clearances.data');
                Route::get('customs-clearances/statistics', [CustomsClearanceController::class, 'statistics'])->name('admin.customs-clearances.statistics');
                Route::get('customs-clearances/{id}', [CustomsClearanceController::class, 'show'])->name('admin.customs-clearances.show');
                Route::get('customs-clearances/{id}/edit', [CustomsClearanceController::class, 'edit'])->name('admin.customs-clearances.edit');
                Route::post('customs-clearances', [CustomsClearanceController::class, 'store'])->name('admin.customs-clearances.store');
                Route::put('customs-clearances/{id}', [CustomsClearanceController::class, 'update'])->name('admin.customs-clearances.update');
                Route::delete('customs-clearances/delete/{id}', [CustomsClearanceController::class, 'destroy'])->name('admin.customs-clearances.destroy');
                Route::delete('customs-clearances/delete-all/{id}', [CustomsClearanceController::class, 'destroyAll'])->name('admin.customs-clearances.destroy.all');
                Route::get('/customs-clearances/assign/{id}', [CustomsClearanceController::class, 'getToAssign'])->name('customs-clearances.get.assign');
                Route::post('customs-clearances/assign', [CustomsClearanceController::class, 'assign'])->name('customs-clearances.assign');
                Route::post('customs-clearances/{id}/create-ad', [CustomsClearanceController::class, 'createAd'])->name('admin.customs-clearances.create-ad');
                Route::post('customs-clearances/status', [CustomsClearanceController::class, 'chang_status'])->name('customs-clearances.status');
                Route::post('customs-clearances/{id}/close', [CustomsClearanceController::class, 'close'])->name('admin.customs-clearances.close');


                Route::get('customs-clearances/payment/{id}', [CustomsClearanceController::class, 'paymentInfo'])->name('customs-clearances.payment.info');


                Route::get('customs-clearances/payment/confirm/{id}', [CustomsClearanceController::class, 'confirmPayment'])->name('customs-clearances.payment.confirm');
                Route::get('customs-clearances/payment/cancel/{id}', [CustomsClearanceController::class, 'cancelPayment'])->name('customs-clearances.payment.cancel');


                Route::get('/customs-clearances/offers/show/{id}', [CustomsClearanceController::class, 'showOffers'])->name('customs-clearances.offers');
                Route::get('/customs-clearances/offers/show/', [CustomsClearanceController::class, 'getOffers'])->name('customs-clearances.offers.data');
                Route::get('/customs-clearances/offers/accept/{id}', [CustomsClearanceController::class, 'acceptOffer'])->name('customs-clearances.offers.accept');
                Route::get('/customs-clearances/offers/retract/{id}', [CustomsClearanceController::class, 'retractOffer'])->name('customs-clearances.offers.retract');


                Route::get('tasks', [TasksController::class, 'index'])->name('tasks.tasks');
                Route::get('/tasks/data', [TasksController::class, 'getData'])->name('tasks.data');
                Route::get('/tasks/show/{id}', [TasksController::class, 'show'])->name('task.show');

                // Task Claim Management Routes
                Route::get('task-claims', [TaskClaimsController::class, 'index'])->name('admin.task-claims.index');
                Route::get('task-claims/data', [TaskClaimsController::class, 'getData'])->name('admin.task-claims.data');
                Route::post('task-claims/{id}/approve', [TaskClaimsController::class, 'approve'])->name('admin.task-claims.approve');
                Route::post('task-claims/{id}/reject', [TaskClaimsController::class, 'reject'])->name('admin.task-claims.reject');
                Route::post('tasks', [TasksController::class, 'store'])->name('tasks.create');
                Route::get('/tasks/create-from-invoice', [App\Http\Controllers\admin\TasksController::class, 'createFromInvoice'])->name('tasks.create_from_invoice');
                Route::post('/tasks/validate-step1', [TasksController::class, 'validateStep1'])->name('tasks.validateStep1');
                Route::post('/tasks/validate-step2', [TasksController::class, 'validateStep2'])->name('tasks.validateStep2');
                Route::post('/tasks/status', [TasksController::class, 'chang_status'])->name('tasks.status');
                Route::post('/tasks/add-note', [TasksController::class, 'taskAddNote'])->name('tasks.note');
                Route::get('/tasks/assign/{id}', [TasksController::class, 'getToAssign'])->name('tasks.get.assign');
                Route::post('/tasks/assign/', [TasksController::class, 'assign'])->name('tasks.assign');
                Route::post('/tasks/drop', [TasksController::class, 'dropTask'])->name('tasks.drop');
                Route::post('/tasks/approve-cancellation', [TasksController::class, 'approveCancellation'])->name('tasks.approve_cancellation');
                Route::post('/tasks/reject-cancellation', [TasksController::class, 'rejectCancellation'])->name('tasks.reject_cancellation');
                Route::get('/tasks/verify-signature/{id}', [TasksController::class, 'verifySignatureStatus'])->name('tasks.verify_signature');
                Route::get('tasks/edit/{id}', [TasksController::class, 'edit'])->name('tasks.edit');
                Route::post('tasks/edit', [TasksController::class, 'update'])->name('tasks.update');
                Route::post('/tasks/close', [TasksController::class, 'closeTask'])->name('tasks.close');
                Route::post('/tasks/refund', [TasksController::class, 'refundTask'])->name('tasks.refund');
                Route::delete('/tasks/delete', [TasksController::class, 'destroy'])->name('tasks.delete');
                Route::delete('/tasks/connect/{id}', [TasksController::class, 'connectTeam'])->name('tasks.connect');
                Route::get('tasks/list', [TasksController::class, 'indexList'])->name('tasks.list');
                Route::get('tasks/list/data', [TasksController::class, 'getListData'])->name('tasks.list.data');
                Route::get('tasks/order-share-data/{orderId}', [TasksController::class, 'getOrderShareData'])->name('tasks.order_share_data');
                Route::post('tasks/bulk-share-data', [TasksController::class, 'getBulkShareData'])->name('tasks.bulk_share_data');
                Route::get('tasks/list/show/{id}', [TasksController::class, 'showDetails'])->name('tasks.list.show');

                Route::get('tasks/pricing/edit/{id}', [TasksController::class, 'editPricing'])->name('tasks.pricing.edit');
                Route::post('tasks/pricing/edit/', [TasksController::class, 'updatePricing'])->name('tasks.pricing.update');


                Route::get('tasks/payment/{id}', [TasksController::class, 'paymentInfo'])->name('tasks.payment.info');
                Route::get('tasks/payment-request/{id}', [TasksController::class, 'paymentRequestInfo'])->name('tasks.payment.request.info');


                Route::get('tasks/payment/confirm/{id}', [TasksController::class, 'confirmPayment'])->name('tasks.payment.confirm');
                Route::get('tasks/payment/cancel/{id}', [TasksController::class, 'cancelPayment'])->name('tasks.payment.cancel');
                Route::get('tasks/payment/cancel-paid/{id}', [TasksController::class, 'cancelPaidPayment'])->name('tasks.payment.cancel_paid');


                Route::get('tasks/tracking/{id}', [TasksController::class, 'taskTracking'])->name('tasks.tracking');


                Route::get('/task/{id}/report', [TasksController::class, 'downloadTaskReport'])->name('tasks.report');
                Route::get('/task/{id}/policy-custom', [TasksController::class, 'printCustomPolicy'])->name('tasks.policy_custom');
                Route::post('/tasks/duplicate', [TasksController::class, 'duplicateTask'])->name('tasks.duplicate');

                Route::get('/tasks/fix-connection/{id}', [TasksController::class, 'fixTeamConnection'])->name('tasks.fix-connection');

                // B2B Company Module Routes
                Route::get('/company/warehouses/{company}', [App\Http\Controllers\admin\CompanyManagementController::class, 'getWarehouses'])->name('company.warehouses');
                Route::get('/company/end-clients/{company}', [App\Http\Controllers\admin\CompanyManagementController::class, 'getEndClients'])->name('company.end-clients');



                Route::get('ads', [TasksAdsController::class, 'index'])->name('ads.ads');
                Route::get('/ads/data', [TasksAdsController::class, 'getData'])->name('ads.data');
                Route::get('/ads/show/{id}', [TasksAdsController::class, 'show'])->name('ads.show');
                Route::get('/ads/edit/{id}', [TasksAdsController::class, 'edit'])->name('ads.edit');
                Route::get('/ads/task/edit/{id}', [TasksAdsController::class, 'editByTask'])->name('ads.task.edit');
                Route::post('/ads/edit', [TasksAdsController::class, 'update'])->name('ads.update');

                Route::get('/ads/offers/show/', [TasksAdsController::class, 'getOffers'])->name('ads.offers.data');
                Route::get('/ads/offers/accept/{id}', [TasksAdsController::class, 'acceptOffer'])->name('ads.offers.accept');
                Route::get('/ads/offers/retract/{id}', [TasksAdsController::class, 'retractOffer'])->name('ads.offers.retract');

                // تضمين routes التخليص  الجديدة
                // require __DIR__ . '/customs_clearance.php';

                // Platform Reports Routes - Simple Test
                Route::get('reports', function () {
                    return view('admin.reports.index');
                })->name('admin.reports.index');

                Route::get('reports/customer-tasks', [PlatformReportsController::class, 'customerReport'])->name('admin.reports.customer-tasks');
                // Keep the original controller routes for POST requests
                Route::post('admin/reports/customer-tasks/generate', [PlatformReportsController::class, 'generateCustomerTasksReport'])->name('admin.reports.customer-tasks.generate');
                Route::post('admin/reports/customer-tasks/preview', [PlatformReportsController::class, 'getReportPreview'])->name('admin.reports.customer-tasks.preview');

                // Driver Tasks Report Routes
                Route::get('reports/driver-tasks', [PlatformReportsController::class, 'driverReport'])->name('admin.reports.driver-tasks');
                Route::post('admin/reports/driver-tasks/generate', [PlatformReportsController::class, 'generateDriverTasksReport'])->name('admin.reports.driver-tasks.generate');
                Route::post('admin/reports/driver-tasks/preview', [PlatformReportsController::class, 'getDriverTasksPreview'])->name('admin.reports.driver-tasks.preview');

                // Team Tasks Report Routes
                Route::get('reports/team-tasks', function () {
                    $customers = App\Models\Customer::select('id', 'name', 'company_name')->get();
                    $drivers = App\Models\Driver::with('team:id,name')->select('id', 'name', 'phone', 'team_id')->get();
                    $teams = App\Models\Teams::select('id', 'name')->get();

                    $taskStatuses = [
                        'pending' => __('Pending'),
                        'confirmed' => __('Confirmed'),
                        'in_progress' => __('In Progress'),
                        'completed' => __('Completed'),
                        'canceled' => __('Canceled'),
                        'refund' => __('Refund')
                    ];

                    $paymentStatuses = [
                        'pending' => __('Pending'),
                        'completed' => __('Completed'),
                        'waiting' => __('Waiting')
                    ];

                    $paymentMethods = [
                        'cash' => 'cash',
                        'bank_transfer' => 'bank transfer',
                        'credit_card' => 'credit card',
                        'wallet' => 'wallet'
                    ];

                    return view('admin.reports.team-tasks', compact(
                        'customers',
                        'drivers',
                        'teams',
                        'taskStatuses',
                        'paymentStatuses',
                        'paymentMethods'
                    ));
                })->name('admin.reports.team-tasks');

                Route::post('admin/reports/team-tasks/generate', [App\Http\Controllers\admin\PlatformReportsController::class, 'generateTeamTasksReport'])->name('admin.reports.team-tasks.generate');
                Route::post('admin/reports/team-tasks/preview', [App\Http\Controllers\admin\PlatformReportsController::class, 'getTeamTasksPreview'])->name('admin.reports.team-tasks.preview');

                // Wallet Reports Routes
                Route::get('reports/wallet', [App\Http\Controllers\admin\WalletReportsController::class, 'index'])->name('admin.reports.wallet.index');
                Route::post('reports/wallet/preview', [App\Http\Controllers\admin\WalletReportsController::class, 'getWalletPreview'])->name('admin.reports.wallet.preview');
                Route::post('reports/wallet/generate', [App\Http\Controllers\admin\WalletReportsController::class, 'generateReport'])->name('admin.reports.wallet.generate');
                Route::post('reports/wallet/get-owners', [App\Http\Controllers\admin\WalletReportsController::class, 'getOwnersByType'])->name('admin.reports.wallet.get-owners');

                // Sales Routes
                Route::group(['prefix' => 'sales'], function () {
                    Route::get('/', [App\Http\Controllers\admin\SalesController::class, 'index'])->name('sales.index');
                    Route::get('/data', [App\Http\Controllers\admin\SalesController::class, 'getData'])->name('sales.data');
                    Route::get('/products', [App\Http\Controllers\admin\SalesController::class, 'getProducts'])->name('sales.products');
                    Route::post('/matching-vehicles', [App\Http\Controllers\admin\SalesController::class, 'getMatchingVehicles'])->name('sales.matching_vehicles');
                    Route::post('/calculate-price', [App\Http\Controllers\admin\SalesController::class, 'calculatePrice'])->name('sales.calculate_price');
                    Route::get('/create', [App\Http\Controllers\admin\SalesController::class, 'create'])->name('sales.create');
                    Route::post('/store', [App\Http\Controllers\admin\SalesController::class, 'store'])->name('sales.store');
                    Route::get('/{id}', [App\Http\Controllers\admin\SalesController::class, 'show'])->name('sales.show');
                    Route::post('/{id}/status', [App\Http\Controllers\admin\SalesController::class, 'updateStatus'])->name('sales.status');
                    Route::get('/{id}/create-task', [App\Http\Controllers\admin\SalesController::class, 'createTask'])->name('sales.create_task');
                });
            });
        });


    });
});


// ══════════════════════════════════════════════════════════════
// لوحة تحكم المستثمر
// ══════════════════════════════════════════════════════════════
Route::middleware(['auth:web', 'investor'])
    ->prefix('investor')
    ->name('investor.')
    ->group(function () {

        // الصفحة الرئيسية
        Route::get('dashboard', [App\Http\Controllers\investor\InvestorDashboardController::class, 'index'])
            ->name('dashboard');

        // محفظة الاستثمار
        Route::get('investment-wallet', [App\Http\Controllers\investor\InvestorWalletController::class, 'investmentWallet'])
            ->name('investment-wallet');
        Route::get('investment-wallet/export', [App\Http\Controllers\investor\InvestorWalletController::class, 'exportInvestmentWallet'])
            ->name('investment-wallet.export');
        Route::post('investment-wallet/deposit', [App\Http\Controllers\investor\InvestorWalletController::class, 'initiateDeposit'])
            ->name('investment-wallet.deposit.initiate');
        Route::get('investment-wallet/deposit/callback', [App\Http\Controllers\investor\InvestorWalletController::class, 'handleDepositCallback'])
            ->name('investment-wallet.deposit.callback');

        // المحفظة الشخصية (العمولات)
        Route::get('personal-wallet', [App\Http\Controllers\investor\InvestorWalletController::class, 'personalWallet'])
            ->name('personal-wallet');
        Route::get('personal-wallet/export', [App\Http\Controllers\investor\InvestorWalletController::class, 'exportPersonalWallet'])
            ->name('personal-wallet.export');

        // احتساب عمولات المستثمر العام (زر الاحتساب)
        Route::post('personal-wallet/calculate-commissions', [App\Http\Controllers\investor\InvestorWalletController::class, 'calculateGeneralCommissions'])
            ->name('personal-wallet.calculate');

        // إعادة استثمار الأرباح من محفظة العمولات إلى محفظة المضاربة
        Route::post('personal-wallet/reinvest-profits', [App\Http\Controllers\investor\InvestorWalletController::class, 'reinvestProfits'])
            ->name('personal-wallet.reinvest');

        // Delete duplicate commission
        Route::delete('personal-wallet/transaction/{id}/delete', [App\Http\Controllers\investor\InvestorWalletController::class, 'deleteCommissionTransaction'])
            ->name('personal-wallet.transaction.delete');

        // دفع المهام (مستثمر بالمهام فقط)
        Route::get('task-payment', [App\Http\Controllers\investor\InvestorTaskPaymentController::class, 'index'])
            ->name('task-payment');
        Route::post('task-payment/{task}/pay', [App\Http\Controllers\investor\InvestorTaskPaymentController::class, 'pay'])
            ->name('task-payment.pay');

        // المهام المدفوعة
        Route::get('paid-tasks', [App\Http\Controllers\investor\InvestorTaskPaymentController::class, 'paidTasks'])
            ->name('paid-tasks');
        Route::get('paid-tasks/export', [App\Http\Controllers\investor\InvestorTaskPaymentController::class, 'exportPaidTasks'])
            ->name('paid-tasks.export');
        Route::get('paid-tasks/{task}/report', [App\Http\Controllers\investor\InvestorTaskPaymentController::class, 'downloadReport'])
            ->name('paid-tasks.report');

        // الملف الشخصي
        Route::get('profile', [App\Http\Controllers\investor\InvestorProfileController::class, 'show'])
            ->name('profile');
        Route::put('profile', [App\Http\Controllers\investor\InvestorProfileController::class, 'update'])
            ->name('profile.update');
        Route::put('password', [App\Http\Controllers\investor\InvestorProfileController::class, 'updatePassword'])
            ->name('password.update');

        // إدارة التوقيع الإلكتروني
        Route::prefix('signature')->name('signature.')->group(function () {
            Route::post('upload', [App\Http\Controllers\investor\InvestorSignatureController::class, 'upload'])->name('upload');
            Route::get('get', [App\Http\Controllers\investor\InvestorSignatureController::class, 'get'])->name('get');
            Route::post('delete', [App\Http\Controllers\investor\InvestorSignatureController::class, 'delete'])->name('delete');
        });
    });

// Firebase Testing Routes
Route::prefix('test-firebase')->group(function () {
    Route::get('/connection', [App\Http\Controllers\TestFirebaseController::class, 'testConnection']);
    Route::post('/send-notification', [App\Http\Controllers\TestFirebaseController::class, 'sendTestNotification']);
    Route::post('/send-to-all', [App\Http\Controllers\TestFirebaseController::class, 'sendTestNotificationToAll']);
    Route::get('/drivers-with-tokens', [App\Http\Controllers\TestFirebaseController::class, 'getDriversWithTokens']);
    Route::post('/test-new-task', [App\Http\Controllers\TestFirebaseController::class, 'testNewTaskNotification']);
    Route::post('/test-payment', [App\Http\Controllers\TestFirebaseController::class, 'testPaymentNotification']);
    Route::post('/validate-token', [App\Http\Controllers\TestFirebaseController::class, 'validateToken']);
});
