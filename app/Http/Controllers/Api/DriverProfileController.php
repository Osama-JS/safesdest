<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class DriverProfileController extends Controller
{
    /**
     * Get driver profile
     */
    public function show(Request $request)
    {
        try {
            $driver = $request->user();
            
            // Load relationships
            $driver->load(['team', 'vehicleSize']);

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
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
                        'commission_value' => $driver->commission_value,
                        'last_activity_at' => $driver->last_activity_at,
                        'app_version' => $driver->app_version,
                        'created_at' => $driver->created_at,
                        'updated_at' => $driver->updated_at,
                        'team' => $driver->team ? [
                            'id' => $driver->team->id,
                            'name' => $driver->team->name,
                        ] : null,
                        'vehicle_size' => $driver->vehicleSize ? [
                            'id' => $driver->vehicleSize->id,
                            'name' => $driver->vehicleSize->name,
                            'description' => $driver->vehicleSize->description,
                        ] : null,
                    ]
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
     * Update driver profile
     */
    public function update(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'phone' => 'sometimes|required|string|max:20',
                'current_password' => 'sometimes|required_with:new_password|string',
                'new_password' => 'sometimes|required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update basic info
            if ($request->has('name')) {
                $driver->name = $request->name;
            }

            if ($request->has('phone')) {
                $driver->phone = $request->phone;
            }

            // Update password if provided
            if ($request->has('new_password')) {
                if (!Hash::check($request->current_password, $driver->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect'
                    ], 422);
                }

                $driver->password = Hash::make($request->new_password);
            }

            $driver->save();

            // Load relationships for response
            $driver->load(['team', 'vehicleSize']);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
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
                        'commission_value' => $driver->commission_value,
                        'last_activity_at' => $driver->last_activity_at,
                        'app_version' => $driver->app_version,
                        'created_at' => $driver->created_at,
                        'updated_at' => $driver->updated_at,
                        'team' => $driver->team ? [
                            'id' => $driver->team->id,
                            'name' => $driver->team->name,
                        ] : null,
                        'vehicle_size' => $driver->vehicleSize ? [
                            'id' => $driver->vehicleSize->id,
                            'name' => $driver->vehicleSize->name,
                            'description' => $driver->vehicleSize->description,
                        ] : null,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update driver profile error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile'
            ], 500);
        }
    }

    /**
     * Get driver statistics
     */
    public function getStats(Request $request)
    {
        try {
            $driver = $request->user();

            // Get task statistics
            $totalTasks = $driver->tasks()->count();
            $completedTasks = $driver->tasks()->where('status', 'delivered')->count();
            $cancelledTasks = $driver->tasks()->where('status', 'cancelled')->count();
            $activeTasks = $driver->tasks()->whereIn('status', ['accepted', 'picked_up', 'in_transit'])->count();

            // Get earnings
            $totalEarnings = $driver->tasks()->where('status', 'delivered')->sum('commission');
            $thisMonthEarnings = $driver->tasks()
                ->where('status', 'delivered')
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->sum('commission');

            // Calculate completion rate
            $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => [
                    'stats' => [
                        'total_tasks' => $totalTasks,
                        'completed_tasks' => $completedTasks,
                        'cancelled_tasks' => $cancelledTasks,
                        'active_tasks' => $activeTasks,
                        'completion_rate' => round($completionRate, 2),
                        'total_earnings' => (float) $totalEarnings,
                        'this_month_earnings' => (float) $thisMonthEarnings,
                        'member_since' => $driver->created_at->format('Y-m-d'),
                        'last_activity' => $driver->last_activity_at,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get driver stats error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }
}
