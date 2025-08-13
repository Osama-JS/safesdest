<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
                'email' => 'required|email',
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

            // Find driver by email
            $driver = Driver::where('email', $request->email)->first();

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
                'email' => $request->email ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ]);
        }
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
                    'message' => 'Invalid token'
                ], 401);
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
                'message' => 'Token refresh failed'
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
}
