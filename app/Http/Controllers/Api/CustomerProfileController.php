<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Form_Template;
use App\Models\Form_Field;
use App\Models\Task;
use App\Models\Customs_Clearance;
use App\Models\Wallet_Transaction;
use App\Http\Controllers\Controller;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Exception;

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
                'success' => true,
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'phone_code' => $customer->phone_code,
                        'image' => $customer->image ? asset('storage/' . $customer->image) : null,
                        'company_name' => $customer->company_name,
                        'company_address' => $customer->company_address,
                        'status' => $customer->status,
                        'is_customs_clearance_agent' => $customer->is_customs_clearance_agent,
                        'email_verified_at' => $customer->email_verified_at,
                        'last_login_at' => $customer->last_login_at,
                        'created_at' => $customer->created_at,
                        'updated_at' => $customer->updated_at,
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
                'success' => false,
                'message' => 'Failed to get profile',
                'error' => $e->getMessage()
            ], 500);
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
                'email' => 'sometimes|required|email|unique:customers,email,' . $customer->id,
                'phone' => 'sometimes|required|unique:customers,phone,' . $customer->id,
                'phone_code' => 'sometimes|required|string',
                'company_name' => 'nullable|string|max:255',
                'company_address' => 'nullable|string|max:255',
            ];

            // Get form template for additional fields validation
            $additionalRules = [];
            $template = null;
            
            if ($customer->form_template_id) {
                $template = Form_Template::with('fields')->find($customer->form_template_id);
                if ($template) {
                    foreach ($template->fields as $field) {
                        if ($field->customer_can === 'write') {
                            $fieldKey = 'additional_fields.' . $field->name;
                            $rules = [];
                            
                            if ($field->required && $request->has($fieldKey)) {
                                $rules[] = 'required';
                            }
                            
                            // Add type-specific validation
                            switch ($field->type) {
                                case 'email':
                                    $rules[] = 'email';
                                    break;
                                case 'number':
                                    $rules[] = 'numeric';
                                    break;
                                case 'date':
                                    $rules[] = 'date';
                                    break;
                                case 'file':
                                case 'image':
                                    $rules[] = 'file';
                                    if ($field->type === 'image') {
                                        $rules[] = 'image|mimes:jpeg,png,jpg,webp,gif|max:2048';
                                    } else {
                                        $rules[] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv|max:5120';
                                    }
                                    break;
                            }
                            
                            if (!empty($rules)) {
                                $additionalRules[$fieldKey] = $rules;
                            }
                        }
                    }
                }
            }

            // Merge validation rules
            $allRules = array_merge($baseRules, $additionalRules);
            
            $validator = Validator::make($request->all(), $allRules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Prepare update data
            $updateData = $request->only(['name', 'email', 'phone', 'phone_code', 'company_name', 'company_address']);
            
            // Handle additional fields
            if ($template && $request->has('additional_fields')) {
                $currentAdditionalData = $customer->additional_data ?? [];
                
                foreach ($template->fields as $field) {
                    if ($field->customer_can === 'write') {
                        $fieldName = $field->name;
                        $fieldKey = 'additional_fields.' . $fieldName;
                        
                        if ($request->has($fieldKey)) {
                            $value = $request->input($fieldKey);
                            
                            // Handle file uploads
                            if (in_array($field->type, ['file', 'image']) && $request->hasFile($fieldKey)) {
                                // Delete old file if exists
                                if (isset($currentAdditionalData[$fieldName]['value'])) {
                                    Storage::delete('public/' . $currentAdditionalData[$fieldName]['value']);
                                }
                                
                                $file = $request->file($fieldKey);
                                $path = FileHelper::uploadFile($file, 'customers/additional_fields');
                                $value = $path;
                            }
                            
                            $currentAdditionalData[$fieldName] = [
                                'label' => $field->label,
                                'value' => $value,
                                'type' => $field->type,
                            ];
                        }
                    }
                }
                
                $updateData['additional_data'] = $currentAdditionalData;
            }

            // Check if email changed - require re-verification
            if (isset($updateData['email']) && $updateData['email'] !== $customer->email) {
                $updateData['email_verified_at'] = null;
            }

            // Update customer
            $customer->update($updateData);

            // If email changed, send verification email
            if (isset($updateData['email']) && $updateData['email'] !== $customer->getOriginal('email')) {
                // Send verification email logic here
                // $this->sendVerificationEmail($customer);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'phone_code' => $customer->phone_code,
                        'image' => $customer->image ? asset('storage/' . $customer->image) : null,
                        'company_name' => $customer->company_name,
                        'company_address' => $customer->company_address,
                        'status' => $customer->status,
                        'email_verified_at' => $customer->email_verified_at,
                        'updated_at' => $customer->updated_at,
                    ],
                    'requires_verification' => isset($updateData['email']) && $updateData['email'] !== $customer->getOriginal('email')
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
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
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = $request->user();

            // Delete old avatar if exists
            if ($customer->image) {
                Storage::delete('public/' . $customer->image);
            }

            // Upload new avatar
            $file = $request->file('avatar');
            $path = FileHelper::uploadFile($file, 'customers/avatars');

            // Update customer
            $customer->update(['image' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'image_url' => asset('storage/' . $path)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ], 500);
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
                'total_transactions' => Wallet_Transaction::whereHas('wallet', function($query) use ($customer) {
                    $query->where('user_type', 'customer')->where('user_id', $customer->id);
                })->count(),
                'total_deposits' => Wallet_Transaction::whereHas('wallet', function($query) use ($customer) {
                    $query->where('user_type', 'customer')->where('user_id', $customer->id);
                })->where('transaction_type', 'credit')->sum('amount'),
                'total_withdrawals' => Wallet_Transaction::whereHas('wallet', function($query) use ($customer) {
                    $query->where('user_type', 'customer')->where('user_id', $customer->id);
                })->where('transaction_type', 'debit')->sum('amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $taskStats,
                    'clearances' => $clearanceStats,
                    'wallet' => $walletStats,
                    'account' => [
                        'member_since' => $customer->created_at->format('Y-m-d'),
                        'last_login' => $customer->last_login_at ? $customer->last_login_at->format('Y-m-d H:i:s') : null,
                        'email_verified' => !is_null($customer->email_verified_at),
                        'is_customs_agent' => $customer->is_customs_clearance_agent,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
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
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = $request->user();

            // Verify password
            if (!Hash::check($request->password, $customer->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 400);
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
                    'success' => false,
                    'message' => 'Cannot delete account with active tasks or clearances'
                ], 400);
            }

            // Delete avatar if exists
            if ($customer->image) {
                Storage::delete('public/' . $customer->image);
            }

            // Revoke all tokens
            $customer->tokens()->delete();

            // Soft delete or anonymize customer data
            $customer->update([
                'name' => 'Deleted User',
                'email' => 'deleted_' . $customer->id . '@deleted.com',
                'phone' => null,
                'image' => null,
                'status' => 'deleted',
                'additional_data' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
