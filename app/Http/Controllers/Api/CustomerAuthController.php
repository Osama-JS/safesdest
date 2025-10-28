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
use App\Models\Settings;

class CustomerAuthController extends Controller
{
    public function getTemplate()
    {
        $customerSetting = Settings::where('key', 'customer_template')->first();
        $template = Form_Template::find($customerSetting->value);
        if (!$template) {
            return response()->json([
                'template_exists' => false,
                'customer_template' => [],
            ]);
        }
        $customerTemplate = Form_Field::where('form_template_id', $customerSetting->value)
            ->where('customer_can', 'write')
            ->get([
                'id',
                'name',
                'label',
                'type',
                'value',
                'required',
                'order',
            ]);

        return response()->json([
            'template_exists' => true,
            'customer_template' => $customerTemplate,
        ]);
    }

    public function createVerificationToken($user)
    {
        $token = Str::random(64);
        Email_Verifications::insert([
          'verifiable_id' => $user->id,
          'verifiable_type' => get_class($user),
          'token' => $token,
          'created_at' => now(),
        ]);
        return $token;
    }

    public function sendVerificationEmail($user, $type)
    {
        $code = rand(1000, 9999);
        $done = $user->update([
            'confirmation_code' => $code,
            'confirmation_code_expires_at' => now()->addMinutes(10),
        ]);
        Log::alert("message: ".$done);

        Mail::send('emails.verify-account-code', ['user' => $user, 'code' => $code], function ($message) use ($user) {
            $message->to($user->email)->subject('Email Verification Code');
        });
    }


    public function resendVerification(Request $req)
    {
        $req->validate(['email' => 'required|email']);

        $customer = Customer::where('email', $req->email)->first();


        if (!$customer) {
            return response()->json([
                'status' => 404,
                'message' => 'Customer not found'
            ]);
        }

        if ($customer->status !== 'verified') {
            return response()->json([
                 'status' => 200,
                 'message' => 'Your email is already verified.'
             ]);
        }

        $ip = $req->ip();
        $email = $req->email;

        $resend = Email_Verification_Resends::where('email', $email)->first();

        // تحقق من عدد المحاولات اليومية
        if ($resend && $resend->resend_count >= 3 && Carbon::parse($resend->last_sent_at)->isToday()) {
            return response()->json([
                   'status' => 400,
                   'message' => 'You have reached the maximum resend attempts for today. Try again tomorrow.'
               ]);
        }

        // تحقق من وجود مهلة زمنية (Cooldown) بين المحاولات
        if ($resend && Carbon::parse($resend->last_sent_at)->diffInMinutes(now()) < 2) {
            return response()->json([
                    'status' => 400,
                    'message' => 'Please wait a few minutes before resending verification email.'
                ]);
        }

        // أرسل رسالة التحقق
        $this->sendVerificationEmail($customer, 'customer');

        // تحديث السجل أو إنشاؤه
        if ($resend) {
            Email_Verification_Resends::where('email', $email)->update([
              'resend_count' => Carbon::parse($resend->last_sent_at)->isToday() ? $resend->resend_count + 1 : 1,
              'last_sent_at' => now(),
              'ip_address' => $ip,
              'updated_at' => now(),
            ]);
        } else {
            Email_Verification_Resends::insert([
              'email' => $email,
              'resend_count' => 1,
              'last_sent_at' => now(),
              'ip_address' => $ip,
              'created_at' => now(),
              'updated_at' => now(),
            ]);
        }

        $remaining = 3;

        if ($resend) {
            $remaining = Carbon::parse($resend->last_sent_at)->isToday()
              ? max(0, 3 - $resend->resend_count)
              : 3;
        }

        return response()->json([
            'status' => 200,
            'message' => 'Verification email resent successfully.',
            'remaining_attempts' => $remaining
        ]);

    }


    public function verifyEmailCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:4',
        ]);

        Log::alert("Req Code: ". $request->code);
        $user = Customer::where('email', $request->email)->first();
        Log::alert("Req Code: ". $user->confirmation_code);

        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }

        if ($user->confirmation_code !== $request->code) {
            return response()->json(['status' => 400, 'message' => 'Invalid code']);
        }

        if ($user->confirmation_code_expires_at < now()) {
            return response()->json(['status' => 400, 'message' => 'Code expired']);
        }


        $token = $user->createToken($request->device_name ?? 'customer-device', ['customer'])->plainTextToken;

        // Update customer login info
        $user->update([
            'email_verified_at' => now(),
            'confirmation_code' => null,
            'confirmation_code_expires_at' => null,
            'status' => 'active',
            'last_login_at' => now(),
            'fcm_token' => $request->fcm_token,
            'device_id' => $request->device_id,
            'app_version' => $request->app_version,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Login successful',
            'data' => [
                'customer' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'phone_code' => $user->phone_code,
                    'image' => $user->image ? url($user->image) : null,
                    'company_name' => $user->company_name,
                    'company_address' => $user->company_address,
                    'status' => $user->status,
                    'is_customs_clearance_agent' => $user->is_customs_clearance_agent,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);

        return response()->json(['status' => 200, 'message' => 'Email verified successfully']);
    }


    // public function register(Request $req)
    // {
    //     // 🔹 القواعد الأساسية
    //     $baseRules = [
    //         'name'           => 'required|string|max:255',
    //         'email'          => 'required|email|unique:customers,email',
    //         'phone'          => 'required|unique:customers,phone',
    //         'phone_code'     => 'required|string',
    //         'password'       => 'required|same:confirm-password',
    //         'c_name'         => 'nullable|string|max:255',
    //         'c_address'      => 'nullable|string|max:255',
    //         'device_id' => 'nullable|string|max:255',
    //             'fcm_token' => 'nullable|string',
    //             'app_version' => 'nullable|string|max:50',
    //     ];

    //     $additionalRules = [];

    //     // 🔹 جلب إعداد القالب الحالي
    //     $customerSetting = Settings::where('key', 'customer_template')->first();
    //     $template = $customerSetting ? Form_Template::find($customerSetting->value) : null;

    //     if ($template) {
    //         // 🔹 جلب الحقول المسموح بها فقط للعميل
    //         $fields = Form_Field::where('form_template_id', $template->id)
    //             ->where('customer_can', 'write')
    //             ->get();

    //         foreach ($fields as $field) {
    //             $fieldKey = 'additional_fields.' . $field->name;
    //             $fieldRules = [];

    //             if ($field->required && !$req->filled('id')) {
    //                 $fieldRules[] = 'required';
    //             }

    //             switch ($field->type) {
    //                 case 'text':
    //                     $fieldRules[] = 'string';
    //                     break;

    //                 case 'number':
    //                     $fieldRules[] = 'numeric';
    //                     break;

    //                 case 'url':
    //                     $fieldRules[] = 'url';
    //                     break;

    //                 case 'date':
    //                     $fieldRules[] = 'date';
    //                     break;

    //                 case 'file':
    //                     $fieldRules = ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif', 'max:10240'];
    //                     if ($field->required) {
    //                         $fieldRules[] = 'required';
    //                     }
    //                     break;

    //                 case 'image':
    //                     $fieldRules = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'];
    //                     if ($field->required) {
    //                         $fieldRules[] = 'required';
    //                     }
    //                     break;

    //                 case 'file_expiration_date':
    //                     $fileKey = $fieldKey . '_file';
    //                     $expKey  = $fieldKey . '_expiration';
    //                     $additionalRules[$fileKey] = ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif', 'max:10240'];
    //                     $additionalRules[$expKey]  = ['nullable', 'date'];
    //                     if ($field->required) {
    //                         $additionalRules[$fileKey][] = 'required_with:' . $expKey;
    //                         $additionalRules[$expKey][]  = 'required_with:' . $fileKey;
    //                     }
    //                     continue 2;

    //                 case 'file_with_text':
    //                     $fileKey = $fieldKey . '_file';
    //                     $textKey = $fieldKey . '_text';
    //                     $additionalRules[$fileKey] = ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif', 'max:10240'];
    //                     $additionalRules[$textKey] = ['nullable', 'string', 'max:255'];
    //                     if ($field->required) {
    //                         $additionalRules[$fileKey][] = 'required';
    //                         $additionalRules[$textKey][] = 'required';
    //                     }
    //                     continue 2;

    //                 default:
    //                     $fieldRules[] = $field->required ? 'string' : 'nullable|string';
    //                     break;
    //             }

    //             $additionalRules[$fieldKey] = $fieldRules;
    //         }
    //     }

    //     // 🔹 دمج جميع القواعد
    //     $allRules = array_merge($baseRules, $additionalRules);

    //     // 🔹 تنفيذ التحقق
    //     $validator = Validator::make($req->all(), $allRules);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 422,
    //             'errors'  => $validator->errors(),
    //         ]);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // 🔹 إعداد بيانات العميل
    //         $data = [
    //             'name'            => $req->name,
    //             'email'           => $req->email,
    //             'phone'           => $req->phone,
    //             'phone_code'      => $req->phone_code,
    //             'password'        => Hash::make($req->password),
    //             'company_name'    => $req->c_name,
    //             'company_address' => $req->c_address,
    //             'device_id' => $req->device_id,
    //             'fcm_token' => $req->fcm_token,
    //             'app_version' => $req->app_version,
    //         ];

    //         $structuredFields = [];

    //         if ($template) {
    //             $data['form_template_id'] = $template->id;
    //             $fields = Form_Field::where('form_template_id', $template->id)
    //                 ->where('customer_can', 'write')
    //                 ->get();

    //             foreach ($fields as $field) {
    //                 $name = $field->name;
    //                 $type = $field->type;

    //                 switch ($type) {
    //                     case 'file_expiration_date':
    //                         $fileKey = "additional_fields.{$name}_file";
    //                         $expKey  = "additional_fields.{$name}_expiration";

    //                         $filePath = null;
    //                         if ($req->hasFile($fileKey)) {
    //                             $filePath = FileHelper::uploadFile($req->file($fileKey), 'customers/files');
    //                         }

    //                         if ($filePath || $req->filled($expKey)) {
    //                             $structuredFields[$name] = [
    //                                 'label'      => $field->label,
    //                                 'value'      => $filePath,
    //                                 'expiration' => $req->input($expKey),
    //                                 'type'       => $type,
    //                             ];
    //                         }
    //                         break;

    //                     case 'file_with_text':
    //                         $fileKey = "additional_fields.{$name}_file";
    //                         $textKey = "additional_fields.{$name}_text";
    //                         $filePath = null;

    //                         if ($req->hasFile($fileKey)) {
    //                             $filePath = FileHelper::uploadFile($req->file($fileKey), 'customers/files');
    //                         }

    //                         if ($filePath || $req->filled($textKey)) {
    //                             $structuredFields[$name] = [
    //                                 'label' => $field->label,
    //                                 'value' => $filePath,
    //                                 'text'  => $req->input($textKey),
    //                                 'type'  => $type,
    //                             ];
    //                         }
    //                         break;

    //                     case 'file':
    //                     case 'image':
    //                         if ($req->hasFile("additional_fields.$name")) {
    //                             $path = FileHelper::uploadFile($req->file("additional_fields.$name"), 'customers/files');
    //                             $structuredFields[$name] = [
    //                                 'label' => $field->label,
    //                                 'value' => $path,
    //                                 'type'  => $type,
    //                             ];
    //                         }
    //                         break;

    //                     default:
    //                         if ($req->filled("additional_fields.$name")) {
    //                             $structuredFields[$name] = [
    //                                 'label' => $field->label,
    //                                 'value' => $req->input("additional_fields.$name"),
    //                                 'type'  => $type,
    //                             ];
    //                         }
    //                         break;
    //                 }
    //             }

    //             $data['additional_data'] = $structuredFields;
    //         }

    //         // 🔹 إنشاء العميل
    //         $customer = Customer::create($data);

    //         // 🔹 إرسال بريد التحقق
    //         $this->sendVerificationEmail($customer, 'customer');

    //         DB::commit();

    //         return response()->json([
    //             'status'  => 200,
    //             'message' => __('Your Account Created successfully'),
    //         ]);
    //     } catch (Exception $ex) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status' => 500,
    //             'message'  => $ex->getMessage(),
    //         ]);
    //     }
    // }

    public function register(Request $req)
    {
        Log::alert("start regester");
        // 🔹 القواعد الأساسية
        $baseRules = [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email',
            'phone'          => 'required|string',
            'phone_code'     => 'required|string',
            'password'       => 'required|same:confirm-password',
            'c_name'         => 'nullable|string|max:255',
            'c_address'      => 'nullable|string|max:255',
            'device_id'      => 'nullable|string|max:255',
            'fcm_token'      => 'nullable|string',
            'app_version'    => 'nullable|string|max:50',
        ];

        $additionalRules = [];

        // 🔹 جلب إعداد القالب الحالي
        $customerSetting = Settings::where('key', 'customer_template')->first();
        $template = $customerSetting ? Form_Template::find($customerSetting->value) : null;

        if ($template) {
            // 🔹 جلب الحقول المسموح بها فقط للعميل
            $fields = Form_Field::where('form_template_id', $template->id)
                ->where('customer_can', 'write')
                ->get();

            foreach ($fields as $field) {
                $fieldKey = 'additional_fields.' . $field->name;
                $fieldRules = [];

                if ($field->required && !$req->filled('id')) {
                    $fieldRules[] = 'required';
                }

                switch ($field->type) {
                    case 'text':
                        $fieldRules[] = 'string';
                        break;

                    case 'number':
                        $fieldRules[] = 'numeric';
                        break;

                    case 'url':
                        $fieldRules[] = 'url';
                        break;

                    case 'date':
                        $fieldRules[] = 'date';
                        break;

                    case 'file':
                        $fieldRules = ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif', 'max:10240'];
                        if ($field->required) {
                            $fieldRules[] = 'required';
                        }
                        break;

                    case 'image':
                        $fieldRules = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'];
                        if ($field->required) {
                            $fieldRules[] = 'required';
                        }
                        break;

                    case 'file_expiration_date':
                        $fileKey = $fieldKey . '_file';
                        $expKey  = $fieldKey . '_expiration';
                        $additionalRules[$fileKey] = ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif', 'max:10240'];
                        $additionalRules[$expKey]  = ['nullable', 'date'];
                        if ($field->required) {
                            $additionalRules[$fileKey][] = 'required_with:' . $expKey;
                            $additionalRules[$expKey][]  = 'required_with:' . $fileKey;
                        }
                        continue 2;

                    case 'file_with_text':
                        $fileKey = $fieldKey . '_file';
                        $textKey = $fieldKey . '_text';
                        $additionalRules[$fileKey] = ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif', 'max:10240'];
                        $additionalRules[$textKey] = ['nullable', 'string', 'max:255'];
                        if ($field->required) {
                            $additionalRules[$fileKey][] = 'required';
                            $additionalRules[$textKey][] = 'required';
                        }
                        continue 2;

                    default:
                        $fieldRules = $field->required
                            ? ['string']
                            : ['nullable', 'string'];
                        break;

                }

                $additionalRules[$fieldKey] = $fieldRules;
            }
        }

        // 🔹 دمج جميع القواعد
        $allRules = array_merge($baseRules, $additionalRules);

        // 🔹 تنفيذ التحقق
        $validator = Validator::make($req->all(), $allRules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        DB::beginTransaction();

        try {
            // 🔹 تحقق إن كان العميل موجود بالفعل
            $existingCustomer = Customer::where('email', $req->email)->first();

            if ($existingCustomer) {
                // إذا الإيميل موجود → أرسل كود التحقق فقط
                $this->sendVerificationEmail($existingCustomer, 'customer');

                return response()->json([
                    'status'  => 200,
                    'message' => __('Verification code sent again to your email'),
                ]);
            }

            // 🔹 إعداد بيانات العميل الجديد
            $data = [
                'name'            => $req->name,
                'email'           => $req->email,
                'phone'           => $req->phone,
                'phone_code'      => $req->phone_code,
                'password'        => Hash::make($req->password),
                'company_name'    => $req->c_name,
                'company_address' => $req->c_address,
                'device_id'       => $req->device_id,
                'fcm_token'       => $req->fcm_token,
                'app_version'     => $req->app_version,
            ];

            $structuredFields = [];

            // 🔹 التعامل مع القالب الإضافي
            if ($template) {
                $data['form_template_id'] = $template->id;
                $fields = Form_Field::where('form_template_id', $template->id)
                    ->where('customer_can', 'write')
                    ->get();

                foreach ($fields as $field) {
                    $name = $field->name;
                    $type = $field->type;

                    switch ($type) {
                        case 'file_expiration_date':
                            $fileKey = "additional_fields.{$name}_file";
                            $expKey  = "additional_fields.{$name}_expiration";

                            $filePath = null;
                            if ($req->hasFile($fileKey)) {
                                $filePath = FileHelper::uploadFile($req->file($fileKey), 'customers/files');
                            }

                            if ($filePath || $req->filled($expKey)) {
                                $structuredFields[$name] = [
                                    'label'      => $field->label,
                                    'value'      => $filePath,
                                    'expiration' => $req->input($expKey),
                                    'type'       => $type,
                                ];
                            }
                            break;

                        case 'file_with_text':
                            $fileKey = "additional_fields.{$name}_file";
                            $textKey = "additional_fields.{$name}_text";
                            $filePath = null;

                            if ($req->hasFile($fileKey)) {
                                $filePath = FileHelper::uploadFile($req->file($fileKey), 'customers/files');
                            }

                            if ($filePath || $req->filled($textKey)) {
                                $structuredFields[$name] = [
                                    'label' => $field->label,
                                    'value' => $filePath,
                                    'text'  => $req->input($textKey),
                                    'type'  => $type,
                                ];
                            }
                            break;

                        case 'file':
                        case 'image':
                            if ($req->hasFile("additional_fields.$name")) {
                                $path = FileHelper::uploadFile($req->file("additional_fields.$name"), 'customers/files');
                                $structuredFields[$name] = [
                                    'label' => $field->label,
                                    'value' => $path,
                                    'type'  => $type,
                                ];
                            }
                            break;

                        default:
                            if ($req->filled("additional_fields.$name")) {
                                $structuredFields[$name] = [
                                    'label' => $field->label,
                                    'value' => $req->input("additional_fields.$name"),
                                    'type'  => $type,
                                ];
                            }
                            break;
                    }
                }

                $data['additional_data'] = $structuredFields;
            }

            // 🔹 إنشاء العميل الجديد
            $customer = Customer::create($data);

            // 🔹 إرسال كود التحقق
            $this->sendVerificationEmail($customer, 'customer');

            DB::commit();

            return response()->json([
                'status'  => 200,
                'message' => __('Your account has been created successfully and a verification code has been sent.'),
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => $ex->getMessage(),
            ]);
        }
    }



    /**
     * Customer login with Sanctum token
     */
    public function login(Request $request)
    {
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
                    'status' => 422,
                    'message' => 'valdation faild',
                    'errors' => $validator->errors()
                ]);
            }

            // Find customer by email or phone
            $email = $request->email;
            $customer = Customer::where('email', $email)->first();

            // Check if customer exists and password is correct
            if (!$customer || !Hash::check($request->password, $customer->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid credentials'
                ]);
            }

            // Check if customer is active
            if ($customer->status !== 'active') {
                return response()->json([
                    'status' => 403,
                    'message' => 'Customer account is not active'
                ]);
            }

            // Check if email is verified
            if ($customer->email_verified_at) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Email not verified',

                ]);
            }

            // Revoke existing tokens for this device (optional - for single device login)
            if ($request->device_name) {
                $customer->tokens()->where('name', $request->device_name)->delete();
            }

            // Create new token
            $token = $customer->createToken($request->device_name ?? 'customer-device', ['customer'])->plainTextToken;

            // Update customer login info
            $customer->update([
                'last_login_at' => now(),
                'fcm_token' => $request->fcm_token,
                'device_id' => $request->device_id,
                'app_version' => $request->app_version,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Login successful',
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
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ]);

        } catch (Exception $e) {
            Log::alert($e);

            return response()->json([
                'status' => 500,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ]);
        }
    }


    public function checkToken(Request $request)
    {
        try {
            // محاولة الحصول على المستخدم الحالي من خلال الـ token
            $customer = $request->user();

            Log::alert("Starat check token");
            // التحقق من وجود المستخدم (يعني أن التوكن صالح)
            if (!$customer) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid or expired token'
                ]);
            }

            // إعادة نفس بيانات العميل مثل دالة login
            return response()->json([
                'status' => 200,
                'message' => 'Token is valid',
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
                    'token_type' => 'Bearer',
                    'token_type' => 'Bearer'
                ]
            ]);

        } catch (Exception $e) {
            Log::alert($e);
            return response()->json([
                'status' => 500,
                'message' => 'Token verification failed',
                'error' => $e->getMessage()
            ]);
        }
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
                'success' => 200,
                'message' => 'Logout successful'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => 500,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ]);
        }
    }


    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = Customer::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'No account found with this email.'
            ]);
        }

        // إنشاء كود تحقق (OTP) مكوّن من 5 أرقام
        $code = rand(1000, 9999);

        // حفظ الكود في جدول password_resets (أو يمكنك تخزينه في جدول المستخدمين)
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email, 'type' => 'customer'],
            [
                'token' => hash('sha256', $code), // نخزن الكود مشفر
                'created_at' => now()
            ]
        );

        // إرسال الكود عبر البريد الإلكتروني
        Mail::send('emails.password-reset-code', [
            'code' => $code,
            'name' => $user->name ?? $user->email
        ], function ($message) use ($user) {
            $message->to($user->email)->subject('Your Password Reset Code');
        });

        return response()->json([
            'status' => 200,
            'message' => 'Password reset code sent successfully.'
        ]);
    }

    public function checkResetCode(Request $request)
    {


        $validator = Validator::make($request->all(), [
        'email' => 'required|email',
            'code' => 'required|digits:4',
            ]);

        if ($validator->fails()) {
            Log::alert($validator->errors());

            return response()->json([
                'status' => 422,
                'message' => 'valdation faild',
                'errors' => $validator->errors()
            ]);
        }

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('type', 'customer')
            ->first();

        if (!$record) {
            return response()->json(['status' => 404, 'message' => 'No reset request found.']);
        }

        // التحقق من الكود
        if (hash('sha256', $request->code) !== $record->token) {
            return response()->json(['status' => 400, 'message' => 'Invalid code.']);
        }

        return response()->json(['status' => 200, 'message' => 'Code is valid.']);
    }

    public function verifyResetCode(Request $request)
    {


        $validator = Validator::make($request->all(), [
                 'email' => 'required|email',
            'code' => 'required|digits:4',
            'password' => 'required|min:6|confirmed',
            'device_id' => 'nullable|string|max:255',
            'fcm_token' => 'nullable|string',
            'app_version' => 'nullable|string|max:50',
            ]);

        if ($validator->fails()) {
            Log::alert($validator->errors());

            return response()->json([
                'status' => 422,
                'message' => 'valdation faild',
                'errors' => $validator->errors()
            ]);
        }

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('type', 'customer')
            ->first();

        if (!$record) {
            return response()->json(['status' => 404, 'message' => 'No reset request found.']);
        }

        // التحقق من الكود
        if (hash('sha256', $request->code) !== $record->token) {
            return response()->json(['status' => 400, 'message' => 'Invalid code.']);
        }

        // التحقق من صلاحية الكود (10 دقائق مثلاً)
        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            return response()->json(['status' => 400, 'message' => 'Code expired.']);
        }

        // تحديث كلمة المرور
        $user = Customer::where('email', $request->email)->first();
        $user->update([
          'password' => Hash::make($request->password),
          'device_id' => $request->device_id,
          'fcm_token' => $request->fcm_token,
          'app_version' => $request->app_version,
        ]);

        // حذف السجل من password_resets
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['status' => 200, 'message' => 'Password reset successfully.']);
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
                    'status' => 422,
                    'message' => 'valdation faild',
                    'errors' => $validator->errors()
                ]);
            }

            $customer = $request->user();

            if (!Hash::check($request->current_password, $customer->password)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Current password is incorrect'
                ]);
            }

            $customer->update(['password' => Hash::make($request->password)]);

            return response()->json([
                'status' => 200,
                'message' => 'Password changed successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Password change failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}
