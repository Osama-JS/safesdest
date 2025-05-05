<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\RolesController;
use App\Http\Controllers\admin\TasksController;
use App\Http\Controllers\admin\TeamsController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\admin\DriversController;
use App\Http\Controllers\admin\WalletsController;
use App\Http\Controllers\admin\CustomersController;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\admin\settings\BlockagesController;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\admin\settings\TagsController;
use App\Http\Controllers\laravel_example\UserManagement;
use App\Http\Controllers\admin\settings\PointsController;
use App\Http\Controllers\admin\settings\RoutesController;
use App\Http\Controllers\admin\settings\PricingController;
use App\Http\Controllers\admin\settings\SettingsController;
use App\Http\Controllers\admin\settings\TemplateController;
use App\Http\Controllers\admin\settings\VehiclesController;
use App\Http\Controllers\admin\settings\GeofencesController;
use App\Http\Controllers\admin\settings\PricingTemplateController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Middleware\EnsureCorrectGuard;
use App\Http\Middleware\EnsureGuardIs;



Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

Route::get('/chosen/vehicles/types/{vehicle}', [VehiclesController::class, 'getTypes']);
Route::get('/chosen/vehicles/sizes/{type}', [VehiclesController::class, 'getSizes']);

Route::get('/mapbox-token', function () {
  return response()->json(['token' => config('services.mapbox.token'), 'style' => config('services.mapbox.style'), 'center' => config('services.mapbox.center')]);
});


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

  Route::post('/custom-logout', [RegisterController::class, 'logout'])->name('custom.logout');

  // Driver routes
  Route::middleware(['guard.strict:driver'])->group(function () {
    Route::get('/driver/dashboard',  [DashboardController::class, 'index'])->name('driver.dashboard');
  });

  // Customer routes

  Route::middleware(['guard.strict:customer'])->group(function () {
    Route::get('/customer/dashboard',  [DashboardController::class, 'index'])->name('customer.dashboard');
  });


  Route::middleware(['guard.strict:web'])->group(function () {
    Route::prefix('admin')->group(function () {

      Route::get('/', [DashboardController::class, 'index'])->name('user.dashboard');

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

        Route::post('/template/pricing', [PricingTemplateController::class, 'store'])->name('settings.templates.pricing.store');
        Route::get('/templates/pricing/data/{id}', [PricingTemplateController::class, 'getData'])->name('settings.templates.pricing.data');
        Route::get('/templates/pricing/edit/{id}', [PricingTemplateController::class, 'edit'])->name('settings.templates.pricing.edit');
        Route::post('/templates/pricing/status/{id}', [PricingTemplateController::class, 'change_state'])->name('settings.templates.pricing.status');
        Route::get('/templates/pricing/methods', [PricingTemplateController::class, 'getPricingMethod'])->name('settings.templates.pricing.methods');
        Route::delete('/templates/pricing/delete/{id}', [PricingTemplateController::class, 'destroy'])->name('settings.templates.pricing.delete');
      });


      Route::get('/customers', [CustomersController::class, 'index'])->name('customers.customers');
      Route::get('/customers/account/{id}/{name}', [CustomersController::class, 'show'])->name('customers.show');
      Route::get('/customers/tasks/', [CustomersController::class, 'getCustomerTasks'])->name('customers.tasks');
      Route::get('/customers/get/customers', [CustomersController::class, 'getCustomers'])->name('customers.get');
      Route::post('/customers', [CustomersController::class, 'store'])->name('customers.create');
      Route::get('/customers/data', [CustomersController::class, 'getData'])->name('customers.data');
      Route::post('/customers/status', [CustomersController::class, 'chang_status'])->name('customers.status');
      Route::get('/customers/edit/{id}', [CustomersController::class, 'edit'])->name('customers.show');
      Route::delete('/customers/delete/{id}', [CustomersController::class, 'destroy'])->name('customers.delete');

      Route::get('/wallets', [WalletsController::class, 'index'])->name('wallets.wallets');
      Route::get('/wallets/data', [WalletsController::class, 'getData'])->name('wallets.data');



      Route::get('/drivers', [DriversController::class, 'index'])->name('drivers.drivers');
      Route::get('/drivers/account/{id}/{name}', [DriversController::class, 'show'])->name('drivers.show');
      Route::get('/drivers/tasks/', [DriversController::class, 'getCustomerTasks'])->name('drivers.tasks');
      Route::post('/drivers', [DriversController::class, 'store'])->name('drivers.create');
      Route::get('/drivers/data', [DriversController::class, 'getData'])->name('drivers.data');
      Route::post('/drivers/status', [DriversController::class, 'chang_status'])->name('drivers.status');
      Route::get('/drivers/edit/{id}', [DriversController::class, 'edit'])->name('drivers.show');
      Route::delete('/drivers/delete/{id}', [DriversController::class, 'destroy'])->name('drivers.delete');


      Route::get('/teams', [TeamsController::class, 'index'])->name('teams.teams');
      Route::post('/teams', [TeamsController::class, 'store'])->name('teams.store');
      Route::get('/teams/edit/{id}', [TeamsController::class, 'edit'])->name('teams.show');
      Route::post('/teams/edit', [TeamsController::class, 'update'])->name('teams.edit');
      Route::get('/teams/data', [TeamsController::class, 'getData'])->name('teams.data');
      Route::delete('/teams/delete/{id}', [TeamsController::class, 'destroy'])->name('teams.delete');

      Route::get('tasks', [TasksController::class, 'index'])->name('tasks.tasks');
      Route::get('/tasks/data', [TasksController::class, 'getData'])->name('tasks.data');
      Route::get('/tasks/{id}', [TasksController::class, 'show'])->name('task.show');
      Route::post('tasks', [TasksController::class, 'store'])->name('tasks.create');
      Route::post('/tasks/validate-step1', [TasksController::class, 'validateStep1'])->name('tasks.validateStep1');
      Route::post('/tasks/validate-step2', [TasksController::class, 'validateStep2'])->name('tasks.validateStep2');
    });
  });
});
