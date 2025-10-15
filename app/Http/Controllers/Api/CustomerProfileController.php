<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Task;
use App\Models\Customer;
use App\Models\Form_Field;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Models\Customs_Clearance;
use App\Models\Wallet_Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FunctionsController;

class CustomerProfileController extends Controller
{
    /**
     * Get customer profile information
     */
    public function show(Request $request)
    {
        try {
            $customer = $request->user();

            // Load form template and fields if exists
            $formTemplate = null;
            $additionalFields = [];

            if ($customer->form_template_id) {
                $formTemplate = Form_Template::with('fields')->find($customer->form_template_id);
                if ($formTemplate && $customer->additional_data) {
                    $additionalFields = $customer->additional_data;
                }
            }

            return response()->json([
                'status' => 200,
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'phone_code' => $customer->phone_code,
                        'image' => $customer->image ? url($customer->image) : null,
                        'company_name' => $customer->company_name,
                        'company_address' => $customer->company_address,
                        'status' => $customer->status,
                        'created_at' => $customer->created_at,
                    ],
                    'form_template' => $formTemplate ? [
                        'id' => $formTemplate->id,
                        'name' => $formTemplate->name,
                        'fields' => $formTemplate->fields->map(function ($field) {
                            return [
                                'id' => $field->id,
                                'name' => $field->name,
                                'label' => $field->label,
                                'type' => $field->type,
                                'required' => $field->required,
                                'customer_can' => $field->customer_can,
                                'order' => $field->order,
                                'value' => $field->value,
                            ];
                        })
                    ] : null,
                    'additional_fields' => $additionalFields,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get profile',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update customer profile
     */
    public function update(Request $request)
    {
        try {
            $customer = $request->user();
            // Base validation rules
            $baseRules = [
                'name' => 'sometimes|required|string|max:255',
                'phone' => 'sometimes|required|unique:customers,phone,' . $customer->id,
                'phone_code' => 'sometimes|required|string',
                'company_name' => 'nullable|string|max:255',
                'company_address' => 'nullable|string|max:255',
            ];


            $validator = Validator::make($request->all(), $baseRules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ]);
            }

            // Prepare update data
            $updateData = $request->only(['name',  'phone', 'phone_code', 'company_name', 'company_address']);

            // Update customer
            $customer->update($updateData);

            return response()->json([
                'status' => 200,
                'message' => 'Profile updated successfully',
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'phone_code' => $customer->phone_code,
                        'image' => $customer->image ? url($customer->image) : null,
                        'company_name' => $customer->company_name,
                        'company_address' => $customer->company_address,
                        'status' => $customer->status,
                        'is_customs_clearance_agent' => $customer->is_customs_clearance_agent,
                        'email_verified_at' => $customer->email_verified_at,
                        'created_at' => $customer->created_at,
                    ],
                    ]

            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Upload customer avatar
     */
    public function uploadAvatar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ]);
            }

            $customer = $request->user();

            $oldImage = null;
            // Delete old avatar if exists
            if ($customer->image) {
                $oldImage = $customer->image;
            }

            // Upload new avatar
            $file = $request->file('avatar');
            $path = (new FunctionsController())->convert($file, 'customers');

            // Update customer
            $customer->update(['image' => $path]);

            return response()->json([
                'status' => 200,
                'message' => 'Avatar uploaded successfully',
                'avatar_url' => url($path)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get customer statistics
     */
    public function getStats(Request $request)
    {
        try {
            $customer = $request->user();

            // Get task statistics
            $taskStats = [
                'total_tasks' => Task::where('customer_id', $customer->id)->count(),
                'completed_tasks' => Task::where('customer_id', $customer->id)->where('status', 'completed')->count(),
                'in_progress_tasks' => Task::where('customer_id', $customer->id)->whereIn('status', ['in_progress', 'assign', 'started', 'in pickup point', 'loading', 'in the way', 'in delivery point', 'unloading'])->count(),
                'canceled_tasks' => Task::where('customer_id', $customer->id)->where('status', 'canceled')->count(),
            ];

            // Get customs clearance statistics
            $clearanceStats = [
                'total_clearances' => Customs_Clearance::where('customer_id', $customer->id)->count(),
                'completed_clearances' => Customs_Clearance::where('customer_id', $customer->id)->where('status', 'completed')->count(),
                'in_progress_clearances' => Customs_Clearance::where('customer_id', $customer->id)->where('status', 'in_progress')->count(),
            ];

            // Get wallet statistics
            $walletStats = [
                'balance' => $customer->wallet->balance,
                'debit'   => $customer->wallet->debit,
                'credit'  => $customer->wallet->credit
            ];

            return response()->json([
                'status' => 200,
                'data' => [
                    'tasks' => $taskStats,
                    'clearances' => $clearanceStats,
                    'wallet' => $walletStats,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete customer account
     */
    public function deleteAccount(Request $request)
    {
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

            $customer = $request->user();

            // Verify password
            if (!Hash::check($request->password, $customer->password)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Invalid password'
                ]);
            }

            // Check for active tasks or clearances
            $activeTasks = Task::where('customer_id', $customer->id)
                              ->whereNotIn('status', ['completed', 'canceled', 'refund'])
                              ->count();

            $activeClearances = Customs_Clearance::where('customer_id', $customer->id)
                                                 ->whereNotIn('status', ['completed', 'canceled'])
                                                 ->count();

            if ($activeTasks > 0 || $activeClearances > 0) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Cannot delete account with active tasks or clearances'
                ]);
            }



            // Revoke all tokens
            $customer->tokens()->delete();

            // Soft delete or anonymize customer data
            $customer->update([
                'status' => 'blocked',
            ]);

            $customer->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Account deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ]);
        }
    }
}
