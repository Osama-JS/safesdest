<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\SaeiOtpService;

class DriverAuthController extends Controller
{
    /**
     * Driver login with Sanctum token
     */
    public function login(Request $request)
    {
        try {

            // Validate request
            $validator = Validator::make($request->all(), [
                'login' => 'required|string',
                'password' => 'required|string|min:6',
                'device_name' => 'required|string|max:255',
                'device_id' => 'nullable|string|max:255',
                'fcm_token' => 'nullable|string',
                'app_version' => 'nullable|string|max:50',
                // 'recaptcha_token' => 'required|string' // reCAPTCHA token from mobile app
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find driver by email or username
            $login = $request->login;
            $driver = Driver::where('email', $login)
                           ->orWhere('username', $login)
                           ->orWhere('phone', $login)
                           ->first();

            // Check if driver exists and password is correct
            if (!$driver || !Hash::check($request->password, $driver->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if driver is active
            if ($driver->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver account is not active'
                ], 403);
            }

            // Revoke existing tokens for this device (optional - for single device login)
            if ($request->device_name) {
                $driver->revokeDeviceTokens($request->device_name);
            }

            // Create new token
            $token = $driver->createDriverToken(
                $request->device_name,
                $request->device_id,
                $request->fcm_token
            );

            // Update last activity and app version
            $driver->update([
                'last_activity_at' => now(),
                'app_version' => $request->app_version
            ]);

            // Log successful login
            Log::info('Driver logged in successfully', [
                'driver_id' => $driver->id,
                'device_name' => $request->device_name,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'token' => $token->plainTextToken,
                    'driver' => [
                        'id' => $driver->id,
                        'name' => $driver->name,
                        'email' => $driver->email,
                        'phone' => $driver->phone,
                        'status' => $driver->status,
                        'online' => $driver->online,
                        'free' => $driver->free,
                        'team_id' => $driver->team_id,
                        'team'  => $driver->team,
                        'vehicle_size_id' => $driver->vehicle_size_id,
                        'commission_type' => $driver->commission_type,
                        'commission_value' => $driver->commission_value
                    ],
                    'abilities' => $token->accessToken->abilities
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Driver login error', [
                'error' => $e->getMessage(),
                'login' => $request->login ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ], 500);
        }
    }
    /**
     * Request OTP for login/registration
     * ─────────────────────────────────────────────────────────
     * يدعم مزوّدَين:
     *   1) ساعي (Saei)  ← عند تفعيل SAEI_OTP_ENABLED=true
     *   2) WhatsApp Cloud API ← الإعداد الافتراضي
     * ─────────────────────────────────────────────────────────
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone'      => 'required|string',
            'phone_code' => 'required|string',
        ]);

        $phone     = $request->phone;
        $phoneCode = $request->phone_code;
        $fullPhone = $phoneCode . ltrim($phone, '0');   // مثال: 966XXXXXXXXX
        $e164Phone = '+' . $fullPhone;                  // مثال: +966XXXXXXXXX
        $lang      = $request->input('language', 'ar');

        // Rate limiting: طلب واحد كل دقيقة لنفس الرقم
        $minuteKey = 'otp_minute_' . $phone;
        if (RateLimiter::tooManyAttempts($minuteKey, 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait a minute before requesting another OTP.',
            ], 429);
        }

        // إنشاء سائق زائر إن لم يكن موجوداً
        $driver = Driver::where('phone', $phone)->first();
        if (!$driver) {
            $driver = Driver::create([
                'name'            => 'سائق زائر - ' . $phone,
                'username'        => 'guest_' . Str::random(8),
                'email'           => 'guest_' . Str::random(8) . '@safedest.app',
                'phone'           => $phone,
                'phone_code'      => $phoneCode,
                'password'        => Hash::make(Str::random(16)),
                'address'         => 'غير محدد',
                'status'          => 'active',
                'is_guest'        => true,
                'vehicle_size_id' => \App\Models\Vehicle_Size::first()->id ?? 1,
            ]);
        }

        // ─── خيار ساعي ─────────────────────────────────────────
        if (env('SAEI_OTP_ENABLED', false)) {

            $saei   = app(SaeiOtpService::class);
            $result = $saei->sendOtp($e164Phone);

            if (!$result['success']) {
                Log::warning('[OTP][Saei] Failed to send OTP', [
                    'phone'   => $e164Phone,
                    'message' => $result['message'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP. Please check the number and try again.',
                ], 500);
            }

            // احفظ verification_id مؤقتاً في الـ Cache (5 دقائق)
            Cache::put(
                'saei_otp_vid_' . $phone,
                $result['verification_id'],
                now()->addMinutes(5)
            );

            RateLimiter::hit($minuteKey, 60);

            Log::info('[OTP][Saei] OTP sent via Saei', [
                'phone'           => $e164Phone,
                'verification_id' => $result['verification_id'],
            ]);

            return response()->json([
                'success'     => true,
                'message'     => 'OTP sent successfully',
                'is_new_user' => $driver->is_guest,
            ]);
        }

        // ─── خيار WhatsApp Cloud API (الافتراضي) ───────────────
        $otpCode = env('WHATSAPP_SIMULATION', true) ? '1234' : rand(1000, 9999);

        $driver->update([
            'otp_code'       => Hash::make($otpCode),
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $whatsAppService = app(\App\Services\Interfaces\WhatsAppServiceInterface::class);
        $isSent = $whatsAppService->sendOTP($fullPhone, $otpCode, $lang);

        if ($isSent) {
            RateLimiter::hit($minuteKey, 60);
            return response()->json([
                'success'     => true,
                'message'     => 'OTP sent successfully',
                'is_new_user' => $driver->is_guest,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send OTP. Please check the number and try again.',
        ], 500);
    }

    /**
     * Verify OTP and Login
     * ─────────────────────────────────────────────────────────
     * يدعم مزوّدَين:
     *   1) ساعي (Saei)  ← عند تفعيل SAEI_OTP_ENABLED=true
     *   2) WhatsApp Cloud API ← الإعداد الافتراضي
     * ─────────────────────────────────────────────────────────
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone'       => 'required|string',
            'otp_code'    => 'required|string',
            'device_name' => 'required|string|max:255',
            'device_id'   => 'nullable|string|max:255',
            'fcm_token'   => 'nullable|string',
            'app_version' => 'nullable|string|max:50',
        ]);

        $driver = Driver::where('phone', $request->phone)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found',
            ], 404);
        }

        // ─── خيار ساعي ─────────────────────────────────────────
        if (env('SAEI_OTP_ENABLED', false)) {

            $verificationId = Cache::get('saei_otp_vid_' . $request->phone);

            if (!$verificationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP expired or not requested. Please request a new code.',
                ], 401);
            }

            $saei   = app(SaeiOtpService::class);
            $result = $saei->verifyOtp((int) $verificationId, $request->otp_code);

            if (!$result['success'] || $result['status'] !== 'approved') {
                $msg = match ($result['status'] ?? '') {
                    'expired' => 'OTP expired or invalid',
                    'denied'  => 'Invalid OTP',
                    default   => 'OTP verification failed',
                };
                Log::warning('[OTP][Saei] Verification failed', [
                    'phone'           => $request->phone,
                    'verification_id' => $verificationId,
                    'status'          => $result['status'] ?? 'unknown',
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 401);
            }

            // نجاح — احذف الـ Cache
            Cache::forget('saei_otp_vid_' . $request->phone);

            Log::info('[OTP][Saei] Verification approved', [
                'phone'     => $request->phone,
                'driver_id' => $driver->id,
            ]);

        } else {
            // ─── خيار WhatsApp Cloud API (الافتراضي) ───────────
            if (!$driver->otp_code || !$driver->otp_expires_at || $driver->otp_expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP expired or invalid',
                ], 401);
            }

            if (!Hash::check($request->otp_code, $driver->otp_code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP',
                ], 401);
            }
        }

        // ─── مشترك: مسح OTP وتسجيل الدخول ─────────────────────
        $driver->update([
            'otp_code'         => null,
            'otp_expires_at'   => null,
            'last_activity_at' => now(),
            'app_version'      => $request->app_version,
        ]);

        if ($request->device_name) {
            $driver->revokeDeviceTokens($request->device_name);
        }

        $token = $driver->createDriverToken(
            $request->device_name,
            $request->device_id,
            $request->fcm_token
        );

        Log::info('Driver OTP login successful', [
            'driver_id'   => $driver->id,
            'device_name' => $request->device_name,
            'provider'    => env('SAEI_OTP_ENABLED', false) ? 'saei' : 'cloud',
            'ip'          => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'token'  => $token->plainTextToken,
                'driver' => [
                    'id'              => $driver->id,
                    'name'            => $driver->name,
                    'email'           => $driver->email,
                    'phone'           => $driver->phone,
                    'status'          => $driver->status,
                    'online'          => $driver->online,
                    'is_guest'        => $driver->is_guest,
                    'team_id'         => $driver->team_id,
                    'vehicle_size_id' => $driver->vehicle_size_id,
                ],
                'abilities' => $token->accessToken->abilities,
            ],
        ], 200);
    }

    /**
     * Driver logout
     */
    public function logout(Request $request)
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Update driver status
            $driver->update([
                'online' => false,
                'last_activity_at' => now()
            ]);

            Log::info('Driver logged out successfully', [
                'driver_id' => $driver->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Driver logout error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    /**
     * Get driver profile
     */
    public function profile(Request $request)
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Load relationships
            $driver->load(['team', 'vehicle_size', 'wallet']);

            return response()->json([
                'success' => true,
                'driver' => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                    'phone_code' => $driver->phone_code,
                    'status' => $driver->status,
                    'online' => $driver->online,
                    'free' => $driver->free,
                    'address' => $driver->address,
                    'longitude' => $driver->longitude,
                    'altitude' => $driver->altitude,
                    'last_seen_at' => $driver->last_seen_at,
                    'commission_type' => $driver->commission_type,
                    'commission_value' => $driver->commission_value,
                    'location_update_interval' => $driver->location_update_interval,
                    'team' => $driver->team_id ? [
                        'id' => $driver->team->id,
                        'name' => $driver->team->name
                    ] : null,
                    'vehicle_size' => $driver->vehicle_size_id ? [
                        'id' => $driver->vehicle_size->id,
                        'name' => $driver->vehicle_size->name
                    ] : null,
                    'wallet_balance' => $driver->wallet ? $driver->wallet->balance : 0,
                    'app_version' => $driver->app_version,
                    'last_activity_at' => $driver->last_activity_at
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get driver profile error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get profile'
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refreshToken(Request $request)
    {
        try {
            $driver = $request->user();
            $currentToken = $request->user()->currentAccessToken();

            if (!$driver || !$currentToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'error_code' => 'TOKEN_INVALID',
                    'action' => 'logout'
                ], 401);
            }

            // Check if driver account is still active
            if ($driver->status !== 'active') {
                // Revoke all tokens for this driver
                $driver->revokeAllTokens();

                return response()->json([
                    'success' => false,
                    'message' => 'Driver account is not active',
                    'error_code' => 'ACCOUNT_INACTIVE',
                    'action' => 'logout'
                ], 403);
            }

            // Create new token with same abilities
            $newToken = $driver->createToken(
                $currentToken->name,
                $currentToken->abilities
            );

            // Delete old token
            $currentToken->delete();

            // Update last activity
            $driver->update(['last_activity_at' => now()]);

            return response()->json([
                'success' => true,
                'token' => $newToken->plainTextToken,
                'expires_at' => null // Sanctum tokens don't expire by default
            ], 200);

        } catch (\Exception $e) {
            Log::error('Token refresh error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error_code' => 'TOKEN_REFRESH_FAILED',
                'action' => 'logout'
            ], 500);
        }
    }

    /**
     * Check driver status and token validity
     */
    public function checkStatus(Request $request)
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token',
                    'error_code' => 'TOKEN_INVALID',
                    'action' => 'logout'
                ], 401);
            }

            // Check if driver account is active
            if ($driver->status !== 'active') {
                // Revoke all tokens for this driver
                $driver->revokeAllTokens();

                Log::warning('Driver account not active', [
                    'driver_id' => $driver->id,
                    'status' => $driver->status,
                    'action' => 'force_logout'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Driver account is not active',
                    'error_code' => 'ACCOUNT_INACTIVE',
                    'action' => 'logout',
                    'status' => $driver->status
                ], 403);
            }

            // Update last activity
            $driver->update(['last_activity_at' => now()]);

            // Return driver data with status
            return response()->json([
                'success' => true,
                'message' => 'Driver status is valid',
                'data' => [
                    'driver' => [
                        'id' => $driver->id,
                        'name' => $driver->name,
                        'email' => $driver->email,
                        'phone' => $driver->phone,
                        'phone_code' => $driver->phone_code,
                        'image' => $driver->image ? url($driver->image) : null,
                        'status' => $driver->status,
                        'online' => $driver->online,
                        'free' => $driver->free,
                        'address' => $driver->address,
                        'commission_type' => $driver->commission_type,
                        'commission_value' => $driver->commission_value,
                        'wallet_balance' => $driver->walletBalance ?? 0,
                        'app_version' => $driver->app_version,
                        'last_activity_at' => $driver->last_activity_at,
                        'created_at' => $driver->created_at,
                    ],
                    'token_valid' => true
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Driver status check failed', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Status check failed',
                'error_code' => 'STATUS_CHECK_FAILED',
                'action' => 'retry'
            ], 500);
        }
    }

    /**
     * Get reCAPTCHA site key for mobile app
     */
    // public function getRecaptchaSiteKey()
    // {
    //     try {
    //         $siteKey = config('services.recaptcha.site_key');

    //         if (!$siteKey) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'reCAPTCHA not configured'
    //             ], 500);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'site_key' => $siteKey,
    //             'version' => 'v3' // Using reCAPTCHA v3
    //         ], 200);

    //     } catch (\Exception $e) {
    //         Log::error('Get reCAPTCHA site key error', [
    //             'error' => $e->getMessage()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to get reCAPTCHA configuration'
    //         ], 500);
    //     }
    // }

    /**
     * Send password reset link to driver's email
     */
    public function forgotPassword(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:drivers,email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'البريد الإلكتروني غير صحيح أو غير موجود',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find driver
            $driver = Driver::where('email', $request->email)->first();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على حساب بهذا البريد الإلكتروني'
                ], 404);
            }

            // Check if driver is active
            if ($driver->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'حساب السائق غير نشط'
                ], 403);
            }

            // Generate reset token
            $token = Str::random(64);

            // Store token in database (you might want to create a password_resets table)
            $driver->update([
                'reset_token' => Hash::make($token),
                'reset_token_expires_at' => now()->addHours(1) // Token expires in 1 hour
            ]);

            // Create reset URL (this should point to your web interface)
            $resetUrl = config('app.frontend_url') . '/driver/reset-password?token=' . $token . '&email=' . urlencode($request->email);

            // Send email (you'll need to create a mail template)
            try {
                Mail::send('emails.driver-password-reset', [
                    'driver' => $driver,
                    'resetUrl' => $resetUrl,
                    'token' => $token
                ], function ($message) use ($driver) {
                    $message->to($driver->email, $driver->name)
                           ->subject('إعادة تعيين كلمة المرور - SafeDests Driver');
                });

                Log::info('Password reset email sent', [
                    'driver_id' => $driver->id,
                    'email' => $driver->email
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني'
                ], 200);

            } catch (\Exception $mailException) {
                Log::error('Failed to send password reset email', [
                    'driver_id' => $driver->id,
                    'email' => $driver->email,
                    'error' => $mailException->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'فشل في إرسال البريد الإلكتروني. يرجى المحاولة مرة أخرى'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Forgot password error', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ. يرجى المحاولة مرة أخرى'
            ], 500);
        }
    }

    /**
     * Reset password using token
     */
    public function resetPassword(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:drivers,email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'البيانات المدخلة غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find driver
            $driver = Driver::where('email', $request->email)->first();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على حساب بهذا البريد الإلكتروني'
                ], 404);
            }

            // Check if token exists and is valid
            if (!$driver->reset_token || !Hash::check($request->token, $driver->reset_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'رمز إعادة التعيين غير صحيح'
                ], 400);
            }

            // Check if token is expired
            if ($driver->reset_token_expires_at && $driver->reset_token_expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'انتهت صلاحية رمز إعادة التعيين. يرجى طلب رمز جديد'
                ], 400);
            }

            // Update password and clear reset token
            $driver->update([
                'password' => Hash::make($request->password),
                'reset_token' => null,
                'reset_token_expires_at' => null
            ]);

            // Revoke all existing tokens for security
            $driver->tokens()->delete();

            Log::info('Password reset successful', [
                'driver_id' => $driver->id,
                'email' => $driver->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح. يرجى تسجيل الدخول مرة أخرى'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Reset password error', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ. يرجى المحاولة مرة أخرى'
            ], 500);
        }
    }
}
