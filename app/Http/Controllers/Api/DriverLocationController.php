<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DriverLocationController extends Controller
{
    /**
     * Update driver location
     */
    public function updateLocation(Request $request)
    {
        Log::alert('start update location');
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric|min:0',
                'speed' => 'nullable|numeric|min:0',
                'heading' => 'nullable|numeric|between:0,360',
                'timestamp' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update driver location
            $updateData = [
                'longitude' => $request->longitude,
                'altitude' => $request->latitude, // Note: altitude field stores latitude in your DB
                'last_seen_at' => $request->timestamp ? $request->timestamp : now(),
                'last_activity_at' => now()
            ];

            $driver->update($updateData);

            // Log location update for debugging (optional)
            Log::debug('Driver location updated', [
                'driver_id' => $driver->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'location' => [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'updated_at' => $updateData['last_seen_at']
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update driver location error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update location'
            ], 500);
        }
    }

    /**
     * Update driver status (online/offline/busy)
     */
    public function updateStatus(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'online' => 'required|boolean',
                'status' => 'nullable|string|in:available,busy,offline'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [
                'online' => $request->online,
                'last_activity_at' => now()
            ];


            $driver->update($updateData);

            Log::info('Driver status updated', [
                'driver_id' => $driver->id,
                'online' => $request->online,
                'free' => $updateData['free'] ?? $driver->free,
                'status' => $request->status,
                'note' => 'Free status is system-controlled'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'driver_status' => [
                    'online' => $driver->online,
                    'free' => $driver->free,
                    'status' => $driver->status,
                    'updated_at' => now()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update driver status error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Get current location and status
     */
    public function getCurrentStatus(Request $request)
    {
        Log::alert('start update location 2');

        try {
            $driver = $request->user();

            return response()->json([
                'success' => true,
                'location' => [
                    'latitude' => $driver->altitude, // altitude field stores latitude
                    'longitude' => $driver->longitude,
                    'last_seen_at' => $driver->last_seen_at
                ],
                'status' => [
                    'online' => $driver->online,
                    'free' => $driver->free,
                    'status' => $driver->status,
                    'last_activity_at' => $driver->last_activity_at
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get driver status error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get status'
            ], 500);
        }
    }

    /**
     * Update FCM token for push notifications
     */
    public function updateFcmToken(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'fcm_token' => 'required|string',
                'device_id' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [
                'fcm_token' => $request->fcm_token,
                'last_activity_at' => now()
            ];

            if ($request->device_id) {
                $updateData['device_id'] = $request->device_id;
            }

            $driver->update($updateData);

            Log::info('FCM token updated', [
                'driver_id' => $driver->id,
                'device_id' => $request->device_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update FCM token error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update FCM token'
            ], 500);
        }
    }
}
