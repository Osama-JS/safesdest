<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Driver;
use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Actions\Fortify\UpdateUserProfileInformation;


class FortifyServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    // Fortify::createUsersUsing(CreateNewUser::class);
    Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
    Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
    // Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

    Fortify::authenticateUsing(function (Request $request) {
      $guard = $request->input('account_type');
      $email = $request->input('email');
      $password = $request->input('password');

      switch ($guard) {
        case 'driver':
          $user = Driver::where('email', $email)->first();
          break;
        case 'customer':
          $user = Customer::where('email', $email)->first();
          break;
        default:
          $user = User::where('email', $email)->first();
          break;
      }

      if (!$user || !Hash::check($password, $user->password)) {
        throw ValidationException::withMessages([
          'email' => ['These credentials do not match our records.']
        ]);
      }

      if ($user->status != 'active') {
        throw ValidationException::withMessages([
          'email' => ['Your account is pending or inactive.']
        ]);
      }

      if ($user->reset_password) {
        throw ValidationException::withMessages([
          'email' => ['You need to reset your password before logging in.']
        ]);
      }

      return $user;
    });

    RateLimiter::for('login', function (Request $request) {
      $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
      return Limit::perMinute(5)->by($throttleKey);
    });

    RateLimiter::for('two-factor', function (Request $request) {
      return Limit::perMinute(5)->by($request->session()->get('login.id'));
    });
  }
}
