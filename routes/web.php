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
use App\Http\Controllers\Auth\CaptchaController;
use App\Http\Controllers\admin\DriversController;
use App\Http\Controllers\admin\WalletsController;
use App\Http\Controllers\Auth\RegisterController;
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

Route::get('/lang/{locale}', [LanguageController::class, 'swap'])->name('lang.switch');
Route::get('/active-account', function () {

    dd('hello');
})->name("active-account-test");

Route::get('/chosen/vehicles/types/{vehicle}', [VehiclesController::class, 'getTypes']);
Route::get('/chosen/vehicles/sizes/{type}', [VehiclesController::class, 'getSizes']);

Route::get('/refresh-captcha', [CaptchaController::class, 'refresh'])->name('captcha.refresh');
Route::get('/test-signit', [SignatureController::class, 'testOAuth']);

Route::get('/test-signature', [SignatureController::class, 'testSignatureRequest']);

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
        Route::post('/initiate-payment', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');
        Route::post('clearance/initiate-payment', [PaymentController::class, 'initiatePaymentClearance'])->name('payment.clearance.initiate');

        // Route لاستقبال الـ callback من HyperPay
        Route::get('/payment-callback', [PaymentController::class, 'handlePaymentCallback'])->name('payment.callback');

        Route::get('/payment/form', function (Request $request) {
            return view('payment.form', ['checkout_id' => $request->checkout_id]);
        })->name('payment.form');

        // Route لعرض صفحة النجاح
        Route::get('/payment/success', function () {
            return view('payment.success');
        })->name('payment.success');

        // Route لعرض صفحة الفشل
        Route::get('/payment/failure', function () {
            return view('payment.failure');
        })->name('payment.failure');

        Route::get('/', function () {
            if (Auth::guard('driver')->check()) {
                return redirect()->route('driver.dashboard');
            } elseif (Auth::guard('customer')->check()) {
                return redirect()->route('customer.dashboard');
            } elseif (Auth::guard('web')->check()) {
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

                // Customer Wallet Management
                Route::get('/wallet', [App\Http\Controllers\customer\WalletController::class, 'index'])->name('customer.wallet.index');
                Route::get('/wallet/data', [App\Http\Controllers\customer\WalletController::class, 'getData'])->name('customer.wallet.data');

                // Customer Ads Management
                Route::get('/ads', [App\Http\Controllers\customer\AdsController::class, 'index'])->name('customer.ads.index');
                Route::get('/ads/data', [App\Http\Controllers\customer\AdsController::class, 'getData'])->name('customer.ads.data');
                Route::get('/ads/show/{id}', [App\Http\Controllers\customer\AdsController::class, 'show'])->name('customer.ads.show');
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
            Route::prefix('admin')->group(function () {

                Route::get('/', [DashboardController::class, 'index'])->name('user.dashboard');
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
                Route::post('/wallets/status/{id}', [WalletsController::class, 'chang_status'])->name('wallets.status');
                Route::post('/wallets/preview/{id}', [WalletsController::class, 'change_preview'])->name('wallets.preview');
                Route::get('/wallets/transaction/show/{id}/{name}', [WalletsController::class, 'show'])->name('wallets.transaction');
                Route::get('/wallets/driver/show/{id}', [WalletsController::class, 'driverShow'])->name('wallets.driver.show');
                Route::post('/wallets/driver/payment', [WalletsController::class, 'processDriverPayment'])->name('wallets.driver.payment');
                Route::get('/wallets/transactions/{id}', [WalletsController::class, 'getDataTransactions'])->name('wallets.transactions');
                Route::get('/wallets/transaction/data', [WalletsController::class, 'getDataTransactions'])->name('wallets.transaction.data');
                Route::post('/wallets/transaction/store', [WalletsController::class, 'storeTransaction'])->name('wallets.transaction.store');
                Route::get('/wallets/transaction/edit/{id}', [WalletsController::class, 'editTransaction'])->name('wallets.transaction.edit');
                Route::delete('/wallets/transaction/delete/{id}', [WalletsController::class, 'destroy'])->name('wallets.transaction.delete');



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
                Route::post('tasks', [TasksController::class, 'store'])->name('tasks.create');
                Route::post('/tasks/validate-step1', [TasksController::class, 'validateStep1'])->name('tasks.validateStep1');
                Route::post('/tasks/validate-step2', [TasksController::class, 'validateStep2'])->name('tasks.validateStep2');
                Route::post('/tasks/status', [TasksController::class, 'chang_status'])->name('tasks.status');
                Route::post('/tasks/add-note', [TasksController::class, 'taskAddNote'])->name('tasks.note');
                Route::get('/tasks/assign/{id}', [TasksController::class, 'getToAssign'])->name('tasks.get.assign');
                Route::post('/tasks/assign/', [TasksController::class, 'assign'])->name('tasks.assign');
                Route::get('tasks/edit/{id}', [TasksController::class, 'edit'])->name('tasks.edit');
                Route::post('tasks/edit', [TasksController::class, 'update'])->name('tasks.update');
                Route::post('/tasks/close', [TasksController::class, 'closeTask'])->name('tasks.close');
                Route::post('/tasks/refund', [TasksController::class, 'refundTask'])->name('tasks.refund');
                Route::delete('/tasks/delete', [TasksController::class, 'destroy'])->name('tasks.delete');
                Route::delete('/tasks/connect/{id}', [TasksController::class, 'connectTeam'])->name('tasks.connect');
                Route::get('tasks/list', [TasksController::class, 'indexList'])->name('tasks.list');
                Route::get('tasks/list/data', [TasksController::class, 'getListData'])->name('tasks.list.data');
                Route::get('tasks/list/show/{id}', [TasksController::class, 'showDetails'])->name('tasks.list.show');

                Route::get('tasks/pricing/edit/{id}', [TasksController::class, 'editPricing'])->name('tasks.pricing.edit');
                Route::post('tasks/pricing/edit/', [TasksController::class, 'updatePricing'])->name('tasks.pricing.update');


                Route::get('tasks/payment/{id}', [TasksController::class, 'paymentInfo'])->name('tasks.payment.info');


                Route::get('tasks/payment/confirm/{id}', [TasksController::class, 'confirmPayment'])->name('tasks.payment.confirm');
                Route::get('tasks/payment/cancel/{id}', [TasksController::class, 'cancelPayment'])->name('tasks.payment.cancel');


                Route::get('tasks/tracking/{id}', [TasksController::class, 'taskTracking'])->name('tasks.tracking');


                Route::get('/task/{id}/report', [TasksController::class, 'downloadTaskReport'])->name('tasks.report');


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

                Route::get('admin/reports/customer-tasks', function () {
                    // Get required data for the view
                    $customers = \App\Models\Customer::select('id', 'name', 'company_name')->get();
                    $drivers = \App\Models\Driver::select('id', 'name', 'phone')->get();
                    $teams = \App\Models\Teams::select('id', 'name')->get();

                    $taskStatuses = [
                        'in_progress' => 'in_progress',
                        'advertised' => 'advertised',
                        'assign' => 'assign',
                        'started' => 'started',
                        'in pickup point' => 'in pickup point',
                        'loading' => 'loading',
                        'in the way' => 'in the way',
                        'in delivery point' => 'in delivery point',
                        'unloading' => 'unloading',
                        'completed' => 'completed',
                        'canceled' => 'canceled',
                    ];

                    $paymentStatuses = [
                         'waiting' => 'waiting',
                        'completed' => 'completed',
                        'pending' => 'pending'
                    ];

                    $paymentMethods = [
                        'bank_transfer' => 'bank transfer',
                        'credit_card' => 'credit card',
                        'wallet' => 'wallet'
                    ];

                    return view('admin.reports.customer-tasks', compact(
                        'customers',
                        'drivers',
                        'teams',
                        'taskStatuses',
                        'paymentStatuses',
                        'paymentMethods'
                    ));
                })->name('admin.reports.customer-tasks');

                // Keep the original controller routes for POST requests
                Route::post('admin/reports/customer-tasks/generate', [App\Http\Controllers\admin\PlatformReportsController::class, 'generateCustomerTasksReport'])->name('admin.reports.customer-tasks.generate');
                Route::post('admin/reports/customer-tasks/preview', [App\Http\Controllers\admin\PlatformReportsController::class, 'getReportPreview'])->name('admin.reports.customer-tasks.preview');

            });
        });
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
