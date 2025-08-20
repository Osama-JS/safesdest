<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Vehicle_Type;
use App\Models\Vehicle_Size;
use App\Models\Form_Field;
use App\Models\Form_Template;
use App\Models\Teams;
use App\Models\Settings;
use App\Models\Email_Verifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Helpers\FileHelper;

class DriverRegistrationController extends Controller
{
    /**
     * Get registration data (vehicles, templates, teams)
     */
    public function getRegistrationData()
    {
        try {
            // Get vehicles with their types and sizes
            $vehicles = Vehicle::with(['types.sizes'])->get();

            // Get driver template
            $driverTemplateSetting = Settings::where('key', 'driver_template')->first();
            $driverTemplate = null;
            $driverFields = [];

            if ($driverTemplateSetting) {
                $driverTemplate = Form_Template::find($driverTemplateSetting->value);
                if ($driverTemplate) {
                    $driverFields = Form_Field::where('form_template_id', $driverTemplate->id)
                        ->orderBy('order', 'ASC')
                        ->get();
                }
            }

            // Get public teams
            $publicTeams = Teams::public()->orderBy('name')->get();

            // Get phone codes
            $phoneCodes = [
                ['code' => '+966', 'country' => 'Saudi Arabia', 'flag' => '🇸🇦'],
                ['code' => '+971', 'country' => 'UAE', 'flag' => '🇦🇪'],
                ['code' => '+20', 'country' => 'Egypt', 'flag' => '🇪🇬'],
                ['code' => '+1', 'country' => 'USA', 'flag' => '🇺🇸'],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicles' => $vehicles,
                    'driver_template' => $driverTemplate,
                    'driver_fields' => $driverFields,
                    'public_teams' => $publicTeams,
                    'phone_codes' => $phoneCodes,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get registration data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vehicle types for a specific vehicle
     */
    public function getVehicleTypes($vehicleId)
    {
        try {
            $vehicleTypes = Vehicle_Type::where('vehicle_id', $vehicleId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $vehicleTypes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vehicle types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vehicle sizes for a specific vehicle type
     */
    public function getVehicleSizes($typeId)
    {
        try {
            $vehicleSizes = Vehicle_Size::where('vehicle_type_id', $typeId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $vehicleSizes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vehicle sizes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register new driver
     */
    public function register(Request $request)
    {
        try {
            // Base validation rules
            $baseRules = [
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:drivers,username',
                'email' => 'required|email|max:255|unique:drivers,email',
                'phone' => 'required|string|max:20|unique:drivers,phone',
                'phone_code' => 'required|string|max:10',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
                'address' => 'required|string|max:500',
                'vehicle_size_id' => 'nullable|exists:vehicles,id',
                'team_id' => 'nullable|exists:teams,id',
                'phone_is_whatsapp' => 'nullable|boolean',
                'whatsapp_country_code' => 'nullable|string|max:10',
                'whatsapp_number' => 'nullable|string|max:20',
                'template_id' => 'nullable|exists:form_templates,id',
            ];

            // Additional rules for dynamic fields
            $additionalRules = [];
            if ($request->filled('template_id')) {
                $fields = Form_Field::where('form_template_id', $request->template_id)->get();

                foreach ($fields as $field) {
                    $fieldKey = 'additional_fields.' . $field->name;
                    $fieldRules = [];

                    if ($field->required) {
                        $fieldRules[] = 'required';
                    } else {
                        $fieldRules[] = 'nullable';
                    }

                    switch ($field->type) {
                        case 'text':
                            $fieldRules[] = 'string|max:255';
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
                            $fieldRules[] = 'file|mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif|max:10240';
                            break;
                        case 'image':
                            $fieldRules[] = 'image|mimes:jpeg,png,jpg,webp,gif|max:5120';
                            break;
                        case 'file_expiration_date':
                            $fileKey = $fieldKey . '_file';
                            $expKey = $fieldKey . '_expiration';
                            $additionalRules[$fileKey] = ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif', 'max:10240'];
                            $additionalRules[$expKey] = ['nullable', 'date'];
                            if ($field->required) {
                                $additionalRules[$fileKey][] = 'required_with:' . $expKey;
                                $additionalRules[$expKey][] = 'required_with:' . $fileKey;
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
                            $fieldRules[] = 'string|max:255';
                            break;
                    }

                    $additionalRules[$fieldKey] = $fieldRules;
                }
            }

            // Merge all rules
            $allRules = array_merge($baseRules, $additionalRules);

            // Validate request
            $validator = Validator::make($request->all(), $allRules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Prepare driver data
            $driverData = [
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'phone_code' => $request->phone_code,
                'password' => Hash::make($request->password),
                'address' => $request->address,
                'vehicle_size_id' => $request->vehicle_size_id,
                'team_id' => $request->team_id,
                'phone_is_whatsapp' => $request->has('phone_is_whatsapp') ? (bool)$request->phone_is_whatsapp : false,
                'status' => 'inactive', // Will be activated after email verification
            ];

            // Handle WhatsApp number logic
            if ($request->has('phone_is_whatsapp') && $request->phone_is_whatsapp) {
                $driverData['whatsapp_country_code'] = $request->phone_code;
                $driverData['whatsapp_number'] = $request->phone;
            } else {
                $driverData['whatsapp_country_code'] = $request->whatsapp_country_code;
                $driverData['whatsapp_number'] = $request->whatsapp_number;
            }

            // Handle template and additional fields
            if ($request->filled('template_id')) {
                $driverData['form_template_id'] = $request->template_id;

                // Process additional fields
                $structuredFields = $this->processAdditionalFields($request);
                $driverData['additional_data'] = json_encode($structuredFields);
            }

            // Create driver
            $driver = Driver::create($driverData);

            // Send verification email
            $this->sendVerificationEmail($driver);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Driver registered successfully. Please check your email to verify your account.',
                'data' => [
                    'driver_id' => $driver->id,
                    'email' => $driver->email,
                    'verification_required' => true
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process additional fields from template
     */
    private function processAdditionalFields(Request $request)
    {
        $structuredFields = [];

        if (!$request->filled('template_id')) {
            return $structuredFields;
        }

        $template = Form_Template::with('fields')->find($request->template_id);

        foreach ($template->fields as $field) {
            $fieldName = $field->name;
            $fieldType = $field->type;

            if ($field->type === 'file_expiration_date') {
                $fileFieldName = $fieldName . '_file';
                $expirationFieldName = $fieldName . '_expiration';

                if ($request->hasFile("additional_fields.$fileFieldName")) {
                    $path = FileHelper::uploadFile($request->file("additional_fields.$fileFieldName"), 'drivers/files');

                    $structuredFields[$fieldName] = [
                        'label' => $field->label,
                        'value' => $path,
                        'expiration' => $request->input("additional_fields.$expirationFieldName"),
                        'type' => $field->type,
                    ];
                }
            } elseif ($field->type === 'file_with_text') {
                $fileFieldName = $fieldName . '_file';
                $textFieldName = $fieldName . '_text';

                if ($request->hasFile("additional_fields.$fileFieldName")) {
                    $path = FileHelper::uploadFile($request->file("additional_fields.$fileFieldName"), 'drivers/files');

                    $structuredFields[$fieldName] = [
                        'label' => $field->label,
                        'value' => $path,
                        'text' => $request->input("additional_fields.$textFieldName"),
                        'type' => $field->type,
                    ];
                }
            } elseif (in_array($fieldType, ['file', 'image'])) {
                if ($request->hasFile("additional_fields.$fieldName")) {
                    $path = FileHelper::uploadFile($request->file("additional_fields.$fieldName"), 'drivers/files');
                    $structuredFields[$fieldName] = [
                        'label' => $field->label,
                        'value' => $path,
                        'type' => $field->type,
                    ];
                }
            } else {
                // Regular fields (text, number, date, etc.)
                $value = $request->input("additional_fields.$fieldName");
                if ($value !== null) {
                    $structuredFields[$fieldName] = [
                        'label' => $field->label,
                        'value' => $value,
                        'type' => $field->type,
                    ];
                }
            }
        }

        return $structuredFields;
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail($driver)
    {
        $token = Str::random(64);

        Email_Verifications::create([
            'verifiable_id' => $driver->id,
            'verifiable_type' => Driver::class,
            'token' => $token,
            'created_at' => now(),
        ]);

        $verifyLink = route('verify.email', ['token' => $token]);

        Mail::send("emails.verify-account", [
            'user' => $driver,
            'verifyLink' => $verifyLink
        ], function ($message) use ($driver) {
            $message->to($driver->email)->subject('Verify Your Email - SafeDests');
        });
    }
}
