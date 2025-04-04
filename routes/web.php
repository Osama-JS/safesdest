<?php

use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\admin\DriversController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\RolesController;
use App\Http\Controllers\admin\TeamsController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\laravel_example\UserManagement;
use App\Http\Controllers\admin\settings\SettingsController;
use App\Http\Controllers\admin\settings\VehiclesController;
use App\Http\Controllers\admin\settings\GeofencesController;
use App\Http\Controllers\admin\settings\PricingController;
use App\Http\Controllers\admin\settings\PricingTemplateController;
use App\Http\Controllers\admin\settings\RoutesController;
use App\Http\Controllers\admin\settings\TemplateController;

Route::get('/lang/{locale}', [LanguageController::class, 'swap']);


Route::middleware([config('jetstream.auth_session')])->group(function () {


  Route::middleware(['auth:web,customer,driver'])->group(function () {
    Route::middleware(['auth'])->group(function () {
      Route::get('/', function () {
        if (auth()->guard('driver')->user()  instanceof \App\Models\Driver) {
          return redirect()->route('driver.dashboard');
        } elseif (auth()->guard('driver')->user()  instanceof \App\Models\Customer) {
          return redirect()->route('customer.dashboard');
        } else {
          return redirect()->route('user.dashboard');
        }
      });
    });
  });

  Route::middleware(['auth:web'])->group(function () {
    Route::prefix('admin')->group(function () {
      Route::get('/laravel/user-management', [UserManagement::class, 'UserManagement'])
        ->name('laravel-example-user-management');


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

        Route::get('/vehicles', [VehiclesController::class, 'index'])->name('settings.vehicles');
        Route::post('/vehicles', [VehiclesController::class, 'store'])->name('settings.vehicles.store');
        Route::post('/vehicles/type', [VehiclesController::class, 'store_type'])->name('settings.vehicles.store.type');
        Route::post('/vehicles/size', [VehiclesController::class, 'store_size'])->name('settings.vehicles.store.size');
        Route::get('/vehicles/data', [VehiclesController::class, 'getData'])->name('settings.vehicles.data');
        Route::delete('/vehicles/delete/{id}', [VehiclesController::class, 'destroy'])->name('settings.vehicles.delete');
        Route::delete('/vehicles/type/delete/{id}', [VehiclesController::class, 'destroy_type'])->name('settings.vehicles.delete.type');
        Route::delete('/vehicles/size/delete/{id}', [VehiclesController::class, 'destroy_size'])->name('settings.vehicles.delete.size');


        Route::get('/geofences', [GeofencesController::class, 'index'])->name('settings.geofences');
        Route::get('/geofences/data', [GeofencesController::class, 'getData'])->name('settings.geofences.data');
        Route::post('/geofences', [GeofencesController::class, 'store'])->name('settings.geofences.store');
        Route::get('/geofences/edit/{id}', [GeofencesController::class, 'edit'])->name('settings.geofences.show');
        Route::post('/geofences/delete/{id}', [GeofencesController::class, 'destroy'])->name('settings.geofences.delete');

        Route::get('/routes', [RoutesController::class, 'index'])->name('settings.routes');


        Route::get('/pricing', [PricingController::class, 'index'])->name('settings.pricing');
        Route::get('/pricing/data', [PricingController::class, 'getData'])->name('settings.pricing.data');
        Route::post('/pricing', [PricingController::class, 'store'])->name('settings.pricing.store');
        Route::get('/pricing/edit/{id}', [PricingController::class, 'edit'])->name('settings.pricing.show');
        Route::post('/pricing/status/{id}', [PricingController::class, 'change_state'])->name('settings.pricing.status');
        Route::post('/pricing/edit', [PricingController::class, 'update'])->name('settings.pricing.edit');
        Route::post('/pricing/delete/{id}', [PricingController::class, 'destroy'])->name('settings.pricing.delete');

        Route::get('/templates', [TemplateController::class, 'index'])->name('settings.templates');
        Route::get('/templates/data', [TemplateController::class, 'getData'])->name('settings.templates.data');
        Route::get('/templates/fields', [TemplateController::class, 'getFields'])->name('settings.templates.fields');
        Route::post('/templates', [TemplateController::class, 'store'])->name('settings.templates.store');
        Route::get('/templates/edit/{id}', [TemplateController::class, 'edit'])->name('settings.templates.edit');
        Route::post('/templates/update/', [TemplateController::class, 'update'])->name('settings.templates.update');

        Route::get('/templates/pricing/data/{id}', [PricingTemplateController::class, 'getData'])->name('settings.templates.pricing.data');
        Route::get('/templates/pricing/methods', [PricingTemplateController::class, 'getPricingMethod'])->name('settings.templates.pricing.methods');
      });

      Route::get('/drivers', [DriversController::class, 'index'])->name('drivers.drivers');
      Route::post('/drivers', [DriversController::class, 'store'])->name('drivers.create');
      Route::get('/drivers/data', [DriversController::class, 'getData'])->name('drivers.data');


      Route::get('/teams', [TeamsController::class, 'index'])->name('teams.teams');
      Route::post('/teams', [TeamsController::class, 'store'])->name('teams.store');
      Route::get('/teams/edit/{id}', [TeamsController::class, 'edit'])->name('teams.show');
      Route::post('/teams/edit', [TeamsController::class, 'update'])->name('teams.edit');
      Route::get('/teams/data', [TeamsController::class, 'getData'])->name('teams.data');
      Route::delete('/teams/delete/{id}', [TeamsController::class, 'destroy'])->name('teams.delete');
    });
  });
});
