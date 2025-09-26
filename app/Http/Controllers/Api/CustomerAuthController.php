<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Form_Field;
use App\Helpers\FileHelper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use Illuminate\Support\Facades\DB;
use App\Models\Email_Verifications;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\Email_Verification_Resends;
use App\Http\Controllers\admin\WalletsController;

class CustomerAuthController extends Controller
{
    /**
     * Customer login with Sanctum token
     */
    public function login(Request $request)
    {
        Log::alert("start Login");

        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|string',
                'password' => 'required|string|min:6',
                'device_name' => 'nullable|string|max:255',
                'device_id' => 'nullable|string|max:255',
                'fcm_token' => 'nullable|string',
                'app_version' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                Log::alert($validator->errors());

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find customer by email or phone
            $email = $request->email;
            $customer = Customer::where('email', $email)
                              ->orWhere('phone', $email)
                              ->first();

            // Check if customer exists and password is correct
            if (!$customer || !Hash::check($request->password, $customer->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if customer is active
            if ($customer->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer account is not active'
                ], 403);
            }

            // Check if email is verified
            if ($customer->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not verified',
                    'requires_verification' => true,
                    'email' => $customer->email
                ], 403);
            }

            // Revoke existing tokens for this device (optional - for single device login)
            if ($request->device_name) {
                $customer->tokens()->where('name', $request->device_name)->delete();
            }

            // Create new token
            $token = $customer->createToken($request->device_name, ['customer'])->plainTextToken;

            // Update customer login info
            $customer->update([
                'last_login_at' => now(),
                'fcm_token' => $request->fcm_token,
                'device_id' => $request->device_id,
                'app_version' => $request->app_version,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
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
                        'created_at' => $customer->created_at,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ]);

        } catch (Exception $e) {
            Log::alert($e);

            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Customer registration
     */
    public function register(Request $request)
    {
        try {
            // Base validation rules
            $baseRules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:customers,email',
                'phone' => 'required|unique:customers,phone',
                'phone_code' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
                'company_name' => 'nullable|string|max:255',
                'company_address' => 'nullable|string|max:255',
                'device_name' => 'required|string|max:255',
                'fcm_token' => 'nullable|string',
                'captcha' => 'required|captcha',
            ];

            // Get form template for additional fields
            $additionalRules = [];
            $template = null;

            if ($request->filled('form_template_id')) {
                $template = Form_Template::with('fields')->find($request->form_template_id);
                if ($template) {
                    foreach ($template->fields as $field) {
                        if ($field->customer_can === 'write') {
                            $fieldKey = 'additional_fields.' . $field->name;
                            $rules = [];

                            if ($field->required) {
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

            DB::beginTransaction();

            // Prepare customer data
            $customerData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'phone_code' => $request->phone_code,
                'password' => Hash::make($request->password),
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'status' => 'active',
                'form_template_id' => $request->form_template_id,
            ];

            // Handle additional fields
            $structuredFields = [];
            if ($template) {
                foreach ($template->fields as $field) {
                    if ($field->customer_can === 'write') {
                        $fieldName = $field->name;
                        $fieldKey = 'additional_fields.' . $fieldName;

                        if ($request->has($fieldKey)) {
                            $value = $request->input($fieldKey);

                            // Handle file uploads
                            if (in_array($field->type, ['file', 'image']) && $request->hasFile($fieldKey)) {
                                $file = $request->file($fieldKey);
                                $path = FileHelper::uploadFile($file, 'customers/additional_fields');
                                $value = $path;
                            }

                            $structuredFields[$fieldName] = [
                                'label' => $field->label,
                                'value' => $value,
                                'type' => $field->type,
                            ];
                        }
                    }
                }
                $customerData['additional_data'] = $structuredFields;
            }

            // Create customer
            $customer = Customer::create($customerData);

            // Create wallet for customer
            (new WalletsController())->store('customer', $customer->id, true);

            // Send verification email
            $this->sendVerificationEmail($customer);

            // Create token
            $token = $customer->createToken($request->device_name, ['customer'])->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Please verify your email.',
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'phone_code' => $customer->phone_code,
                        'company_name' => $customer->company_name,
                        'company_address' => $customer->company_address,
                        'status' => $customer->status,
                        'email_verified_at' => $customer->email_verified_at,
                        'created_at' => $customer->created_at,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'requires_verification' => true
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail($customer)
    {
        $token = Str::random(64);

        Email_Verifications::create([
            'email' => $customer->email,
            'token' => $token,
            'user_type' => 'customer',
            'user_id' => $customer->id,
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        // Send email (implement your email template)
        // Mail::send('emails.verify', ['token' => $token], function($message) use ($customer) {
        //     $message->to($customer->email);
        //     $message->subject('Verify Your Email Address');
        // });
    }

    /**
     * Customer logout
     */
    public function logout(Request $request)
    {
        try {
            $customer = $request->user();

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Clear FCM token
            $customer->update(['fcm_token' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify email address
     */
    public function verifyEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $verification = Email_Verifications::where('token', $request->token)
                                              ->where('email', $request->email)
                                              ->where('user_type', 'customer')
                                              ->where('expires_at', '>', Carbon::now())
                                              ->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification token'
                ], 400);
            }

            $customer = Customer::find($verification->user_id);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            // Mark email as verified
            $customer->update(['email_verified_at' => Carbon::now()]);

            // Delete verification record
            $verification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerification(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:customers,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = Customer::where('email', $request->email)->first();

            if ($customer->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified'
                ], 400);
            }

            // Check resend attempts
            $today = Carbon::today();
            $resendCount = Email_Verification_Resends::where('email', $request->email)
                                                    ->whereDate('created_at', $today)
                                                    ->count();

            if ($resendCount >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum resend attempts reached for today'
                ], 429);
            }

            // Delete old verification tokens
            Email_Verifications::where('email', $request->email)
                              ->where('user_type', 'customer')
                              ->delete();

            // Send new verification email
            $this->sendVerificationEmail($customer);

            // Record resend attempt
            Email_Verification_Resends::create([
                'email' => $request->email,
                'user_type' => 'customer',
                'user_id' => $customer->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forgot password
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:customers,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = Customer::where('email', $request->email)->first();

            // Generate reset token
            $token = Str::random(64);

            // Store reset token (you might want to create a password_resets table)
            DB::table('password_resets')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => Carbon::now()
                ]
            );

            // Send reset email (implement your email template)
            // Mail::send('emails.password-reset', ['token' => $token], function($message) use ($customer) {
            //     $message->to($customer->email);
            //     $message->subject('Reset Your Password');
            // });

            return response()->json([
                'success' => true,
                'message' => 'Password reset email sent successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:customers,email',
                'token' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reset = DB::table('password_resets')
                      ->where('email', $request->email)
                      ->first();

            if (!$reset || !Hash::check($request->token, $reset->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reset token'
                ], 400);
            }

            // Check if token is not expired (24 hours)
            if (Carbon::parse($reset->created_at)->addHours(24)->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired'
                ], 400);
            }

            $customer = Customer::where('email', $request->email)->first();
            $customer->update(['password' => Hash::make($request->password)]);

            // Delete reset token
            DB::table('password_resets')->where('email', $request->email)->delete();

            // Revoke all tokens
            $customer->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change password (for authenticated users)
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = $request->user();

            if (!Hash::check($request->current_password, $customer->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            $customer->update(['password' => Hash::make($request->password)]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password change failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
