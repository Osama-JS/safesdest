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
use Illuminate\Support\Facades\Validator;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Support\Facades\Log;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );
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
            // تحقق من الـ captcha فقط إذا لم يتم التحقق منه في هذا الطلب
            if (!$request->attributes->get('captcha_validated')) {
                Log::info('Validating reCAPTCHA - First attempt...');

                // Google reCAPTCHA validation (currently active)
                // $validator = Validator::make($request->all(), [
                //   'g-recaptcha-response' => 'required|recaptcha',
                // ]);

                // Custom captcha validation (commented for easy switching)
                // To switch to custom captcha:
                // 1. Comment out the above reCAPTCHA validation
                // 2. Uncomment the below custom captcha validation
                // 3. Uncomment the custom captcha section in custom-login.blade.php
                // 4. Comment out the reCAPTCHA section in custom-login.blade.php

                $validator = Validator::make($request->all(), [
                  'captcha' => 'required|captcha',
                ]);


                if ($validator->fails()) {
                    // Check which captcha failed and throw appropriate error
                    if ($validator->errors()->has('g-recaptcha-response')) {
                        throw ValidationException::withMessages([
                          'recaptcha' => ['reCAPTCHA verification failed.'],
                        ]);
                    }

                    if ($validator->errors()->has('captcha')) {
                        throw ValidationException::withMessages([
                          'captcha' => ['Captcha verification failed. Please try again.'],
                        ]);
                    }
                }

                Log::info('Captcha validated successfully');
                // ضع علامة في الـ request attributes أن الـ captcha تم التحقق منه
                $request->attributes->set('captcha_validated', true);
            } else {
                Log::info('Captcha already validated in this request, skipping validation');
            }

            $guard = $request->input('account_type');
            $email = $request->input('email');
            $password = $request->input('password');

            switch ($guard) {
                case 'driver':
                    $user = Driver::where(function($q) use ($email) {
                        $q->where('email', $email)
                          ->orWhere('phone', $email)
                          ->orWhere('username', $email);
                    })->first();
                    break;
                case 'customer':
                    $user = Customer::where(function($q) use ($email) {
                        $q->where('email', $email)
                          ->orWhere('phone', $email);
                    })->where('is_customs_clearance_agent', 0)->first();
                    break;
                case 'broker':
                    $user = Customer::where(function($q) use ($email) {
                        $q->where('email', $email)
                          ->orWhere('phone', $email);
                    })->where('is_customs_clearance_agent', 1)->first();
                    break;
                default:
                    $user = User::where(function($q) use ($email) {
                        $q->where('email', $email)
                          ->orWhere('phone', $email);
                    })->first();
                    break;
            }

            if (!$user || !Hash::check($password, $user->password)) {
                throw ValidationException::withMessages([
                  'email' => [__('auth.failed')]
                ]);
            }

            if ($user->status === 'verified') {
                throw ValidationException::withMessages([
                  'email' => [__('Your email is not verified.') . ' <a href="' . route('verify.manual', ['email' => $user->email]) . '">' . __('Click here to verify') . '</a>']
                ]);
            }

            if ($user->status != 'active') {
                throw ValidationException::withMessages([
                  'email' => [__('Your account is pending or inactive.')]
                ]);
            }

            if ($user->reset_password) {
                throw ValidationException::withMessages([
                  'email' => [__('You need to reset your password before logging in.')]
                ]);
            }

            // تجديد الـ session token وإعداد الـ captcha flag
            $request->session()->regenerateToken();
            $request->session()->put('captcha', true);

            // تسجيل الدخول بالـ guard المناسب
            if ($guard == 'customer' || $guard == 'broker') {
                Auth::guard('web')->logout();
                Auth::guard('driver')->logout();
                Auth::guard('customer')->login($user);
                session(['guard' => 'customer']);
            } elseif ($guard == 'driver') {
                Auth::guard('customer')->logout();
                Auth::guard('web')->logout();
                Auth::guard('driver')->login($user);
                session(['guard' => 'driver']);
            } else {
                Auth::guard('customer')->logout();
                Auth::guard('driver')->logout();
                Auth::guard('web')->login($user);
                session(['guard' => 'web']);
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
