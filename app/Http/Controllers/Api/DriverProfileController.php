<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\FunctionsController;

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
            $driver->load(['team', 'vehicle_size']);

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'driver' => [
                        'id' => $driver->id,
                        'name' => $driver->name,
                        'email' => $driver->email,
                        'phone' => $driver->phone,
                        'phone_code' => $driver->phone_code,
                        'address' => $driver->address,
                        'image' => $driver->image,
                        'status' => $driver->status,
                        'online' => $driver->online,
                        'free' => $driver->free,
                        'team_id' => $driver->team_id,
                        'vehicle_size_id' => $driver->vehicle_size_id,
                        'commission_type' => $driver->commission_type,
                        'commission_value' => $driver->commission_value,
                        'last_activity_at' => $driver->last_activity_at,
                        'app_version' => $driver->app_version,
                        'additional_data' => $driver->additional_data ? json_decode($driver->additional_data, true) : null,
                        'created_at' => $driver->created_at,
                        'updated_at' => $driver->updated_at,
                        'team' => $driver->team ? [
                            'id' => $driver->team->id,
                            'name' => $driver->team->name,
                        ] : null,
                        'vehicle_size' => $driver->vehicle_size ? [
                            'id' => $driver->vehicle_size->id,
                            'name' => $driver->vehicle_size->type->vehicle->name . ' - ' . $driver->vehicle_size->type->name .' - '.  $driver->vehicle_size->name,
                            'description' => $driver->vehicle_size->description,
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
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:drivers,email,' . $driver->id,
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

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

            if ($request->has('email')) {
                $driver->email = $request->email;
            }

            if ($request->has('phone')) {
                $driver->phone = $request->phone;
            }

            if ($request->has('address')) {
                $driver->address = $request->address;
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $oldImage = $driver->image;
                $driver->image = (new FunctionsController())->convert($request->file('image'), 'drivers');

                // Delete old image if exists
                if ($oldImage && file_exists(public_path($oldImage))) {
                    unlink(public_path($oldImage));
                }
            }

            // Update password if provided
            // if ($request->has('new_password')) {
            //     if (!Hash::check($request->current_password, $driver->password)) {
            //         return response()->json([
            //             'success' => false,
            //             'message' => 'Current password is incorrect'
            //         ], 422);
            //     }

            //     $driver->password = Hash::make($request->new_password);
            // }

            $driver->save();

            // Load relationships for response
            $driver->load(['team', 'vehicle_size']);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'driver' => [
                        'id' => $driver->id,
                        'name' => $driver->name,
                        'email' => $driver->email,
                        'phone' => $driver->phone,
                        'phone_code' => $driver->phone_code,
                        'address' => $driver->address,
                        'image' => $driver->image,
                        'status' => $driver->status,
                        'online' => $driver->online,
                        'free' => $driver->free,
                        'team_id' => $driver->team_id,
                        'vehicle_size_id' => $driver->vehicle_size_id,
                        'commission_type' => $driver->commission_type,
                        'commission_value' => $driver->commission_value,
                        'last_activity_at' => $driver->last_activity_at,
                        'app_version' => $driver->app_version,
                        'additional_data' => $driver->additional_data ? $driver->additional_data : null,
                        'created_at' => $driver->created_at,
                        'updated_at' => $driver->updated_at,
                        'team' => $driver->team ? [
                            'id' => $driver->team->id,
                            'name' => $driver->team->name,
                        ] : null,
                        'vehicle_size' => $driver->vehicle_size ? [
                            'id' => $driver->vehicle_size->id,
                            'name' => $driver->vehicle_size->type->vehicle->name . '-' . $driver->vehicle_size->type->name .' - '.  $driver->vehicle_size->name,
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

    /**
     * Change driver password
     */
    public function changePassword(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check current password
            if (!Hash::check($request->current_password, $driver->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }

            // Update password
            $driver->password = Hash::make($request->new_password);
            $driver->save();

            Log::info('Driver password changed successfully', [
                'driver_id' => $driver->id,
                'driver_email' => $driver->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Change driver password error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change password'
            ], 500);
        }
    }
}
