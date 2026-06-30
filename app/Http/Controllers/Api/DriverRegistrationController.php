<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Teams;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Settings;
use App\Models\Form_Field;
use App\Helpers\FileHelper;
use Illuminate\Support\Str;
use App\Models\Vehicle_Size;
use App\Models\Vehicle_Type;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use Illuminate\Support\Facades\DB;
use App\Models\Email_Verifications;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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
          $driverFields = Form_Field::where('form_template_id', $driverTemplate->id)->where('driver_can', 'write')
            ->orderBy('order', 'ASC')
            ->get();
        }
      }

      // Get public teams
      $publicTeams = Teams::public()->orderBy('name')->get();
      $freeDriver = new Teams();
      $freeDriver->id = '';
      $freeDriver->name = 'سائق حر';
      $freeDriver->address = '';

      $publicTeams->prepend($freeDriver);

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
      // Log::info('Register driver (API) attempt', [
      //     'payload' => $request->all(),
      //     'files' => $request->allFiles(),
      //     'has_additional_fields' => $request->has('additional_fields')
      // ]);
      // Log::alert($request);
      // dd('stop');
      // قواعد أساسية (قريبة من دالة الموقع)
      $teamId = $request->input('team_id');

      $request->merge([
        'team_id' => ($teamId === '' || $teamId === '0' || $teamId === 0)
          ? null
          : $teamId,
      ]);
      $existingGuest = Driver::where('phone', $request->phone)
                            ->where('phone_code', $request->phone_code)
                            ->where('is_guest', true)
                            ->first();

      $driverIdToIgnore = $existingGuest ? $existingGuest->id : null;

      $baseRules = [
        'name' => 'required|string|max:255',
        'username' => 'required|string|max:255|unique:drivers,username' . ($driverIdToIgnore ? ',' . $driverIdToIgnore : ''),
        'email' => 'required|email|max:255|unique:drivers,email' . ($driverIdToIgnore ? ',' . $driverIdToIgnore : ''),
        'phone' => 'nullable|string|max:20|unique:drivers,phone' . ($driverIdToIgnore ? ',' . $driverIdToIgnore : ''),
        'phone_code' => 'nullable|string|max:10',
        'password' => 'nullable|string|min:8|confirmed',
        'password_confirmation' => 'nullable|string|min:8',
        'address' => 'required|string|max:500',
        'vehicle_size_id' => 'nullable|exists:vehicle_sizes,id',
        'team_id' => 'nullable|exists:teams,id',
        'phone_is_whatsapp' => 'nullable|in:true,false,0,1',
        'whatsapp_country_code' => 'nullable|string|max:10',
        'whatsapp_number' => 'nullable|string|max:20',
        // إن كان لديك كابتشا في الـ API أضِفها هنا
        // 'captcha' => 'required|captcha',
      ];

      // دعم template_id أو template
      $templateId = $request->input('template_id') ?? $request->input('template');

      // قواعد الحقول الإضافية - التنسيق الجديد
      $additionalRules = [];
      if (!empty($templateId)) {
        // Add validation for additional_fields as JSON string
        $additionalRules['additional_fields'] = 'nullable|json';

        // We'll validate the content of additional_fields after parsing
        // No need for individual field validation rules here since they're in JSON
      }


      // دمج القواعد والتحقق
      $allRules = array_merge($baseRules, $additionalRules);
      $validator = Validator::make($request->all(), $allRules);

      if ($validator->fails()) {
        Log::warning('Register driver (API) validation failed', ['errors' => $validator->errors()]);
        return response()->json([
          'success' => false,
          'message' => $validator->errors()->first(),
          'errors' => $validator->errors(),
        ], 422);
      }

      // Custom validation for additional_fields content
      if (!empty($templateId) && $request->has('additional_fields')) {
        $additionalFieldsValidation = $this->validateAdditionalFieldsContent($request, $templateId);
        if ($additionalFieldsValidation !== true) {
          return $additionalFieldsValidation;
        }
      }

      DB::beginTransaction();

      // بيانات السائق الأساسية
      $driverData = [
        'name' => $request->name,
        'username' => $request->username,
        'email' => $request->email,
        'phone' => $request->phone,
        'phone_code' => $request->phone_code,
        'address' => $request->address,
        'vehicle_size_id' => $request->vehicle_size_id,
        'team_id' => $request->team_id,
        'phone_is_whatsapp' => $request->has('phone_is_whatsapp') ? (bool) $request->phone_is_whatsapp : false,
      ];

      if ($request->filled('password')) {
        $driverData['password'] = Hash::make($request->password);
      }

      // منطق الواتساب
      if ($request->has('phone_is_whatsapp') && $request->phone_is_whatsapp) {
        $driverData['whatsapp_country_code'] = $request->phone_code;
        $driverData['whatsapp_number'] = $request->phone;
      } else {
        $driverData['whatsapp_country_code'] = $request->whatsapp_country_code;
        $driverData['whatsapp_number'] = $request->whatsapp_number;
      }

      // الحقول الإضافية بنفس منطق دالة الموقع
      if (!empty($templateId)) {
        $driverData['form_template_id'] = $templateId;
        $driverData['additional_data'] = $this->buildStructuredAdditionalFieldsFromApi($request, $templateId);
        // ملاحظة مهمة: لا تستخدم json_encode هنا
      }

      $driverData['is_guest'] = false; // They are no longer a guest!
      $driverData['email_verified_at'] = now(); // Automatically verify since they used OTP

      if ($existingGuest) {
          $existingGuest->update($driverData);
          $driver = $existingGuest;
      } else {
          $driver = Driver::create($driverData);
      }
      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Driver profile completed successfully.',
        'data' => [
          'driver_id' => $driver->id,
          'email' => $driver->email,
          'verification_required' => false,
        ],
      ], 201);



    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Register driver (API) error', ['error' => $e->getMessage()]);
      return response()->json([
        'success' => false,
        'message' => 'Registration failed',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * يبني additional_data بنفس منطق دالة الموقع (label, value, type ...)
   * ويدعم الأنواع الخاصة ورفع الملفات عبر FileHelper.
   */
  private function buildStructuredAdditionalFieldsFromApi(Request $req, $templateId): array
  {
    $structured = [];

    $template = Form_Template::with('fields')->find($templateId);
    if (!$template) {
      Log::warning('Template not found', ['template_id' => $templateId]);
      return $structured;
    }

    // Check if additional_fields is sent as JSON string
    $additionalFieldsData = [];
    if ($req->has('additional_fields')) {
      $additionalFieldsJson = $req->input('additional_fields');
      if (is_string($additionalFieldsJson)) {
        $additionalFieldsData = json_decode($additionalFieldsJson, true) ?? [];
      } elseif (is_array($additionalFieldsJson)) {
        $additionalFieldsData = $additionalFieldsJson;
      }
    }

    Log::info('Processing additional fields', [
      'template_id' => $templateId,
      'fields_count' => $template->fields->count(),
      'additional_fields_data' => $additionalFieldsData,
      'request_keys' => array_keys($req->all())
    ]);

    foreach ($template->fields as $field) {
      $name = $field->name;
      $type = $field->type;

      Log::info('Processing field', [
        'field_name' => $name,
        'field_type' => $type,
        'field_required' => $field->required
      ]);

      switch ($type) {
        case 'file_expiration_date': {
          $fileKey1 = "additional_fields.{$name}_file";
          $fileKey2 = "{$name}_file";
          $fileKey3 = "additional_fields_{$name}_file";
          $expKey = "{$name}_expiration";
          
          $hasFile = $req->hasFile($fileKey1) || $req->hasFile($fileKey2) || $req->hasFile($fileKey3);
          $fileKeyToUse = $req->hasFile($fileKey1) ? $fileKey1 : ($req->hasFile($fileKey2) ? $fileKey2 : $fileKey3);
          
          $expiration = $additionalFieldsData[$expKey] ?? $req->input($expKey);

          if ($hasFile) {
            $path = FileHelper::uploadFile($req->file($fileKeyToUse), 'drivers/files');
            $structured[$name] = [
              'label' => $field->label,
              'value' => $path,
              'expiration' => $expiration,
              'type' => $type,
            ];
          } elseif (!empty($expiration)) {
            $structured[$name] = [
              'label' => $field->label,
              'value' => null,
              'expiration' => $expiration,
              'type' => $type,
            ];
          }
          break;
        }

        case 'file_with_text': {
          $fileKey1 = "additional_fields.{$name}_file";
          $fileKey2 = "{$name}_file";
          $fileKey3 = "additional_fields_{$name}_file";
          $textKey = "{$name}_text";

          $valuePath = null;
          $hasFile = $req->hasFile($fileKey1) || $req->hasFile($fileKey2) || $req->hasFile($fileKey3);
          $fileKeyToUse = $req->hasFile($fileKey1) ? $fileKey1 : ($req->hasFile($fileKey2) ? $fileKey2 : $fileKey3);
          
          $text = $additionalFieldsData[$textKey] ?? $req->input($textKey);

          if ($hasFile) {
            $valuePath = FileHelper::uploadFile($req->file($fileKeyToUse), 'drivers/files');
          }

          if ($hasFile || !empty($text)) {
            $structured[$name] = [
              'label' => $field->label,
              'value' => $valuePath,
              'text' => $text,
              'type' => $type,
            ];
          }
          break;
        }

        case 'file':
        case 'image': {
          $fileKey1 = "additional_fields.{$name}";
          $fileKey2 = $name;
          $fileKey3 = "additional_fields_{$name}";
          
          $hasFile = $req->hasFile($fileKey1) || $req->hasFile($fileKey2) || $req->hasFile($fileKey3);
          $fileKeyToUse = $req->hasFile($fileKey1) ? $fileKey1 : ($req->hasFile($fileKey2) ? $fileKey2 : $fileKey3);

          if ($hasFile) {
            $path = FileHelper::uploadFile($req->file($fileKeyToUse), 'drivers/files');
            $structured[$name] = [
              'label' => $field->label,
              'value' => $path,
              'type' => $type,
            ];
          }
          break;
        }

        default: {
          $fieldValue = $additionalFieldsData[$name] ?? $req->input($name);
          if ($fieldValue !== null && $fieldValue !== '') {
            $structured[$name] = [
              'label' => $field->label,
              'value' => $fieldValue,
              'type' => $type,
            ];
          }
          break;
        }
      }
    }

    Log::info('Additional fields processing completed', [
      'structured_fields_count' => count($structured),
      'structured_fields' => array_keys($structured)
    ]);

    return $structured;
  }

  /**
   * Validate additional_fields content after JSON parsing
   */
  private function validateAdditionalFieldsContent(Request $request, $templateId)
  {
    $additionalFieldsJson = $request->input('additional_fields');
    $additionalFieldsData = [];

    if (is_string($additionalFieldsJson)) {
      $additionalFieldsData = json_decode($additionalFieldsJson, true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        return response()->json([
          'success' => false,
          'message' => 'Invalid JSON format for additional_fields',
          'errors' => ['additional_fields' => ['Invalid JSON format']]
        ], 422);
      }
    } elseif (is_array($additionalFieldsJson)) {
      $additionalFieldsData = $additionalFieldsJson;
    }

    $template = Form_Template::with('fields')->find($templateId);
    if (!$template) {
      return true; // Skip validation if template not found
    }

    $errors = [];

    foreach ($template->fields as $field) {
      $fieldName = $field->name;

      switch ($field->type) {
        case 'file_expiration_date':
          $fileKey1 = "additional_fields.{$fieldName}_file";
          $fileKey2 = "{$fieldName}_file";
          $fileKey3 = "additional_fields_{$fieldName}_file";
          $expKey = "{$fieldName}_expiration";

          $hasFile = $request->hasFile($fileKey1) || $request->hasFile($fileKey2) || $request->hasFile($fileKey3);
          $expiration = $additionalFieldsData[$expKey] ?? $request->input($expKey);

          if ($field->required) {
            if (!$hasFile) {
              $errors["additional_fields.{$fieldName}_file"] = [__('The :label file is required.', ['label' => $field->label])];
            }
            if (empty($expiration)) {
              $errors["additional_fields.{$expKey}"] = [__('The expiration date for :label is required.', ['label' => $field->label])];
            }
          } else {
            if ($hasFile && empty($expiration)) {
              $errors["additional_fields.{$expKey}"] = [__('The expiration date for :label is required.', ['label' => $field->label])];
            }
          }

          if (!empty($expiration) && !strtotime($expiration)) {
            $errors["additional_fields.{$expKey}"] = [__('The expiration date for :label must be a valid date.', ['label' => $field->label])];
          }
          break;

        case 'file_with_text':
          $fileKey1 = "additional_fields.{$fieldName}_file";
          $fileKey2 = "{$fieldName}_file";
          $fileKey3 = "additional_fields_{$fieldName}_file";
          $textKey = "{$fieldName}_text";

          $hasFile = $request->hasFile($fileKey1) || $request->hasFile($fileKey2) || $request->hasFile($fileKey3);
          $text = $additionalFieldsData[$textKey] ?? $request->input($textKey);

          if ($field->required) {
            if (!$hasFile) {
              $errors["additional_fields.{$fieldName}_file"] = [__('The :label file is required.', ['label' => $field->label])];
            }
            if (empty($text)) {
              $errors["additional_fields.{$textKey}"] = [__('The text field for :label is required.', ['label' => $field->label])];
            }
          } else {
            if ($hasFile && empty($text)) {
              $errors["additional_fields.{$textKey}"] = [__('The text field for :label is required.', ['label' => $field->label])];
            }
          }
          break;

        case 'file':
        case 'image':
          $fileKey1 = "additional_fields.{$fieldName}";
          $fileKey2 = $fieldName;
          $fileKey3 = "additional_fields_{$fieldName}";
          
          if ($field->required && !$request->hasFile($fileKey1) && !$request->hasFile($fileKey2) && !$request->hasFile($fileKey3)) {
            $errors["additional_fields.{$fieldName}"] = [__('The :label file is required.', ['label' => $field->label])];
          }
          break;

        default:
          $fieldValue = $additionalFieldsData[$fieldName] ?? $request->input($fieldName);

          if ($field->required && (is_null($fieldValue) || $fieldValue === '')) {
            $errors["additional_fields.{$fieldName}"] = [__('The :label field is required.', ['label' => $field->label])];
            continue 2;
          }

          if (is_null($fieldValue) || $fieldValue === '') {
            continue 2;
          }

          if ($field->type === 'number' && !is_numeric($fieldValue)) {
            $errors["additional_fields.{$fieldName}"] = [__('The :label must be a number.', ['label' => $field->label])];
          } elseif ($field->type === 'url' && !filter_var($fieldValue, FILTER_VALIDATE_URL)) {
            $errors["additional_fields.{$fieldName}"] = [__('The :label must be a valid URL.', ['label' => $field->label])];
          } elseif ($field->type === 'date' && !strtotime($fieldValue)) {
            $errors["additional_fields.{$fieldName}"] = [__('The :label must be a valid date.', ['label' => $field->label])];
          }
          break;
      }
    }

    if (!empty($errors)) {
      Log::warning('Additional fields validation failed', ['errors' => $errors]);
      
      // Get the first error message to display in the app
      $firstError = reset($errors)[0] ?? 'Validation failed for additional fields';
      
      return response()->json([
        'success' => false,
        'message' => $firstError,
        'errors' => $errors
      ], 422);
    }

    return true;
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

        if ($request->hasFile($fileFieldName)) {
          $path = FileHelper::uploadFile($request->file($fileFieldName), 'drivers/files');

          $structuredFields[$fieldName] = [
            'label' => $field->label,
            'value' => $path,
            'expiration' => $request->input($expirationFieldName),
            'type' => $field->type,
          ];
        }
      } elseif ($field->type === 'file_with_text') {
        $fileFieldName = $fieldName . '_file';
        $textFieldName = $fieldName . '_text';

        if ($request->hasFile($fileFieldName)) {
          $path = FileHelper::uploadFile($request->file($fileFieldName), 'drivers/files');

          $structuredFields[$fieldName] = [
            'label' => $field->label,
            'value' => $path,
            'text' => $request->input($textFieldName),
            'type' => $field->type,
          ];
        }
      } elseif (in_array($fieldType, ['file', 'image'])) {
        if ($request->hasFile($fieldName)) {
          $path = FileHelper::uploadFile($request->file($fieldName), 'drivers/files');
          $structuredFields[$fieldName] = [
            'label' => $field->label,
            'value' => $path,
            'type' => $field->type,
          ];
        }
      } else {
        // Regular fields (text, number, date, etc.)
        $value = $request->input($fieldName);
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
