<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Task;
use App\Models\Customs_Clearance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\FunctionsController;
use Exception;

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

            // إنشاء username تلقائياً إذا لم يكن موجوداً
            if (empty($driver->username)) {
                // إنشاء username من البريد الإلكتروني أو الاسم
                $generatedUsername = $this->generateUsername($driver);
                $driver->username = $generatedUsername;
                $driver->save();

                Log::info('Generated username for driver', [
                    'driver_id' => $driver->id,
                    'generated_username' => $generatedUsername
                ]);
            }

            // Debug: طباعة معلومات username
            Log::info('Driver Profile Debug', [
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'username' => $driver->username,
                'username_is_null' => is_null($driver->username),
                'username_is_empty' => empty($driver->username),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'driver' => [
                        'id' => $driver->id,
                        'name' => $driver->name,
                        'email' => $driver->email,
                        'username' => $driver->username,
                        'driver_code' => $driver->driver_code,
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
                        'additional_data' => $driver->driver_visible_additional_data,
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
                        'signature_image' => $driver->signature_image ? url($driver->signature_image) : null,
                        'bank_name' => $driver->bank_name,
                        'account_number' => $driver->account_number,
                        'iban_number' => $driver->iban_number,
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

            // Log incoming request data for debugging
            Log::info('Driver profile update request', [
                'driver_id' => $driver->id,
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_files' => $request->hasFile('image'),
                'all_data' => $request->all(),
                'files' => $request->allFiles(),
            ]);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                // البريد الإلكتروني محذوف لأنه غير قابل للتعديل (يعتبر الهوية الأساسية)
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

            // Email is not updatable as it's the primary identity for login
            // if ($request->has('email')) {
            //     $driver->email = $request->email;
            // }

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
                        'image' => $driver->image ? url($driver->image) : null,
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
     * Update driver signature
     */
    public function updateSignature(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'signature_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('signature_image')) {
                $oldImage = $driver->signature_image;
                $driver->signature_image = (new FunctionsController())->convert($request->file('signature_image'), 'drivers/signatures');

                // Delete old image if exists
                if ($oldImage && file_exists(public_path($oldImage))) {
                    unlink(public_path($oldImage));
                }
            }

            $driver->save();

            return response()->json([
                'success' => true,
                'message' => 'Signature updated successfully',
                'data' => [
                    'signature_image' => $driver->signature_image ? url($driver->signature_image) : null
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update driver signature error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update signature'
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

    /**
     * Generate username for driver if not exists
     */
    private function generateUsername($driver)
    {
        // استخدام البريد الإلكتروني كأساس لـ username
        $baseUsername = explode('@', $driver->email)[0];

        // تنظيف username من الأحرف غير المسموحة
        $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $baseUsername);

        // التأكد من أن username فريد
        $username = $baseUsername;
        $counter = 1;

        while (\App\Models\Driver::where('username', $username)->where('id', '!=', $driver->id)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Get driver's additional data with proper filtering
     */
    public function getAdditionalData(Request $request)
    {
        try {
            $driver = $request->user();

            // Load form template relationship
            $driver->load('formTemplate.fields');

            // Get filtered additional data that driver can read
            $visibleData = $driver->driver_visible_additional_data;

            Log::info('Driver additional data request', [
                'driver_id' => $driver->id,
                'has_template' => $driver->form_template_id ? true : false,
                'raw_data_count' => is_array($driver->additional_data) ? count($driver->additional_data) : 0,
                'visible_data_count' => count($visibleData),
            ]);

            Log::alert('Visible data: ' . json_encode($visibleData));

            return response()->json([
                'success' => true,
                'message' => 'Additional data retrieved successfully',
                'data' => [
                    'additional_data' => $visibleData,
                    'has_template' => $driver->form_template_id ? true : false,
                    'template_name' => $driver->formTemplate?->name ?? null,
                ]
            ], 200);


        } catch (\Exception $e) {
            Log::error('Get driver additional data error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get additional data'
            ], 500);
        }
    }


       public function deleteAccount(Request $request)
    {
        Log::alert("Start delete account");
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string',
                'confirmation' => 'required|string|in:DELETE_MY_ACCOUNT',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ]);
            }

            $driver = $request->user();

            // Verify password
            if (!Hash::check($request->password, $driver->password)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Invalid password'
                ]);
            }

            // Check for active tasks or clearances
            $activeTasks = Task::where('driver_id', $driver->id)
                              ->whereNotIn('status', ['completed', 'canceled', 'refund'])
                              ->where('closed', true)
                              ->count();



            if ($activeTasks > 0 ) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Cannot delete account with active tasks'
                ]);
            }



            // Revoke all tokens
            $driver->tokens()->delete();

            // Soft delete or anonymize driver data
            $driver->update([
                'status' => 'blocked',
            ]);

            $driver->delete();

            Log::alert("Delete account done");
            return response()->json([
                'status' => 200,
                'message' => 'Account deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::alert('Failed to delete account: '.$e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update bank details
     */
    public function updateBankDetails(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'bank_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:255',
                'iban_number' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $driver->bank_name = $request->bank_name;
            $driver->account_number = $request->account_number;
            $driver->iban_number = $request->iban_number;
            $driver->save();

            return response()->json([
                'success' => true,
                'message' => 'Bank details updated successfully',
                'data' => [
                    'bank_details' => [
                        'bank_name' => $driver->bank_name,
                        'account_number' => $driver->account_number,
                        'iban_number' => $driver->iban_number,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update bank details error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update bank details'
            ], 500);
        }
    }
}
