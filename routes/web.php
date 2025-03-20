<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\dashboard\Analytics;
use App\Http\Controllers\admin\RolesController;
use App\Http\Controllers\admin\TeamsController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\laravel_example\UserManagement;
use App\Http\Controllers\admin\settings\SettingsController;
use App\Http\Controllers\admin\settings\VehiclesController;
use App\Http\Controllers\admin\settings\GeofencesController;

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

      Route::get('/', [UsersController::class, 'index'])->name('user.dashboard');


      Route::get('/users', [UsersController::class, 'index'])->name('user.users');
      Route::get('/users/data', [UsersController::class, 'getData'])->name('user.data');
      Route::post('/users', [UsersController::class, 'store'])->name('user.create');

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


        Route::get('/geofences', [GeofencesController::class, 'index'])->name('settings.geofences');
        Route::post('/geofences', [GeofencesController::class, 'store'])->name('settings.geofences.store');
      });
      Route::get('/teams', [TeamsController::class, 'index'])->name('teams.teams');
      Route::post('/teams', [TeamsController::class, 'store'])->name('teams.store');
      Route::get('/teams/edit/{id}', [TeamsController::class, 'edit'])->name('teams.show');
      Route::post('/teams/edit', [TeamsController::class, 'update'])->name('teams.edit');
      Route::get('/teams/data', [TeamsController::class, 'getData'])->name('teams.data');
      Route::post('/teams/delete', [TeamsController::class, 'destroy'])->name('teams.delete');
    });
  });
});
