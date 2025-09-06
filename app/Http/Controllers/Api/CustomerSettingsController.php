<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Geofence;
use App\Models\Form_Template;
use App\Models\Form_Field;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class CustomerSettingsController extends Controller
{
    /**
     * Get application settings
     */
    public function getSettings(Request $request)
    {
        try {
            $customer = $request->user();

            // Get system settings
            $systemSettings = Cache::remember('system_settings', 3600, function () {
                return Setting::pluck('value', 'key')->toArray();
            });

            // Get customer-specific settings
            $customerSettings = [
                'language' => $customer->language ?? 'ar',
                'currency' => $customer->currency ?? 'SAR',
                'timezone' => $customer->timezone ?? 'Asia/Riyadh',
                'date_format' => $customer->date_format ?? 'Y-m-d',
                'time_format' => $customer->time_format ?? 'H:i',
                'distance_unit' => $customer->distance_unit ?? 'km',
                'weight_unit' => $customer->weight_unit ?? 'kg',
            ];

            // Application configuration
            $appConfig = [
                'app_name' => $systemSettings['app_name'] ?? 'SafeDest',
                'app_version' => $systemSettings['app_version'] ?? '1.0.0',
                'min_supported_version' => $systemSettings['min_supported_version'] ?? '1.0.0',
                'force_update' => (bool) ($systemSettings['force_update'] ?? false),
                'maintenance_mode' => (bool) ($systemSettings['maintenance_mode'] ?? false),
                'maintenance_message' => $systemSettings['maintenance_message'] ?? 'System under maintenance',
                'support_phone' => $systemSettings['support_phone'] ?? '+966500000000',
                'support_email' => $systemSettings['support_email'] ?? 'support@safedest.com',
                'support_whatsapp' => $systemSettings['support_whatsapp'] ?? '+966500000000',
                'terms_url' => $systemSettings['terms_url'] ?? '',
                'privacy_url' => $systemSettings['privacy_url'] ?? '',
                'about_url' => $systemSettings['about_url'] ?? '',
            ];

            // Business settings
            $businessSettings = [
                'default_currency' => $systemSettings['default_currency'] ?? 'SAR',
                'tax_rate' => (float) ($systemSettings['tax_rate'] ?? 15),
                'commission_rate' => (float) ($systemSettings['commission_rate'] ?? 10),
                'min_wallet_balance' => (float) ($systemSettings['min_wallet_balance'] ?? 0),
                'max_task_distance' => (int) ($systemSettings['max_task_distance'] ?? 1000),
                'task_expiry_hours' => (int) ($systemSettings['task_expiry_hours'] ?? 24),
                'offer_expiry_hours' => (int) ($systemSettings['offer_expiry_hours'] ?? 6),
                'rating_required' => (bool) ($systemSettings['rating_required'] ?? true),
            ];

            // Feature flags
            $features = [
                'tasks_enabled' => (bool) ($systemSettings['tasks_enabled'] ?? true),
                'customs_clearance_enabled' => (bool) ($systemSettings['customs_clearance_enabled'] ?? true),
                'wallet_enabled' => (bool) ($systemSettings['wallet_enabled'] ?? true),
                'payments_enabled' => (bool) ($systemSettings['payments_enabled'] ?? true),
                'ads_enabled' => (bool) ($systemSettings['ads_enabled'] ?? true),
                'chat_enabled' => (bool) ($systemSettings['chat_enabled'] ?? false),
                'live_tracking_enabled' => (bool) ($systemSettings['live_tracking_enabled'] ?? true),
                'push_notifications_enabled' => (bool) ($systemSettings['push_notifications_enabled'] ?? true),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'customer_settings' => $customerSettings,
                    'app_config' => $appConfig,
                    'business_settings' => $businessSettings,
                    'features' => $features,
                    'server_time' => now()->toISOString(),
                    'timezone' => config('app.timezone'),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update customer settings
     */
    public function updateSettings(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'language' => 'nullable|in:ar,en',
                'currency' => 'nullable|in:SAR,USD,EUR',
                'timezone' => 'nullable|string|max:50',
                'date_format' => 'nullable|in:Y-m-d,d/m/Y,m/d/Y,d-m-Y',
                'time_format' => 'nullable|in:H:i,h:i A',
                'distance_unit' => 'nullable|in:km,mi',
                'weight_unit' => 'nullable|in:kg,lb',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update customer settings
            $customer->update($request->only([
                'language',
                'currency',
                'timezone',
                'date_format',
                'time_format',
                'distance_unit',
                'weight_unit',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available geofences
     */
    public function getGeofences(Request $request)
    {
        try {
            $geofences = Cache::remember('active_geofences', 1800, function () {
                return Geofence::where('is_active', true)
                              ->select(['id', 'name', 'type', 'coordinates', 'radius', 'description'])
                              ->get()
                              ->map(function ($geofence) {
                                  return [
                                      'id' => $geofence->id,
                                      'name' => $geofence->name,
                                      'type' => $geofence->type,
                                      'coordinates' => $geofence->coordinates,
                                      'radius' => $geofence->radius,
                                      'description' => $geofence->description,
                                  ];
                              });
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'geofences' => $geofences,
                    'total_count' => $geofences->count(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get geofences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get app version info
     */
    public function getAppVersion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_version' => 'required|string',
                'platform' => 'required|in:android,ios',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $currentVersion = $request->current_version;
            $platform = $request->platform;

            // Get version settings from database
            $latestVersion = Setting::where('key', "latest_version_{$platform}")->value('value') ?? '1.0.0';
            $minVersion = Setting::where('key', "min_version_{$platform}")->value('value') ?? '1.0.0';
            $forceUpdate = (bool) Setting::where('key', 'force_update')->value('value');

            // Compare versions
            $needsUpdate = version_compare($currentVersion, $latestVersion, '<');
            $isSupported = version_compare($currentVersion, $minVersion, '>=');

            $updateInfo = [
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'min_supported_version' => $minVersion,
                'needs_update' => $needsUpdate,
                'force_update' => $forceUpdate && !$isSupported,
                'is_supported' => $isSupported,
                'update_available' => $needsUpdate,
            ];

            if ($needsUpdate) {
                $updateInfo['update_url'] = $platform === 'android'
                    ? 'https://play.google.com/store/apps/details?id=com.safedest.customer'
                    : 'https://apps.apple.com/app/safedest-customer/id123456789';

                $updateInfo['release_notes'] = Setting::where('key', "release_notes_{$platform}")
                                                     ->value('value') ?? 'Bug fixes and improvements';
            }

            return response()->json([
                'success' => true,
                'data' => $updateInfo
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get app version info',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate dynamic form fields
     */
    public function validateFormFields(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required|exists:form_templates,id',
                'fields' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $template = Form_Template::with('fields')->find($request->template_id);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Form template not found'
                ], 404);
            }

            $submittedFields = $request->fields;
            $validationErrors = [];
            $validatedData = [];

            foreach ($template->fields as $field) {
                $fieldName = $field->name;
                $fieldValue = $submittedFields[$fieldName] ?? null;

                // Check if required field is missing
                if ($field->is_required && (is_null($fieldValue) || $fieldValue === '')) {
                    $validationErrors[$fieldName] = ['The ' . $field->label . ' field is required.'];
                    continue;
                }

                // Skip validation if field is not required and empty
                if (!$field->is_required && (is_null($fieldValue) || $fieldValue === '')) {
                    continue;
                }

                // Validate based on field type
                $fieldValidation = $this->validateFieldByType($field, $fieldValue);

                if ($fieldValidation['valid']) {
                    $validatedData[$fieldName] = $fieldValidation['value'];
                } else {
                    $validationErrors[$fieldName] = $fieldValidation['errors'];
                }
            }

            $isValid = empty($validationErrors);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => $isValid,
                    'validated_data' => $validatedData,
                    'errors' => $validationErrors,
                    'template_info' => [
                        'id' => $template->id,
                        'name' => $template->name,
                        'total_fields' => $template->fields->count(),
                        'required_fields' => $template->fields->where('is_required', true)->count(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate form fields',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate individual field by type
     */
    private function validateFieldByType($field, $value)
    {
        $errors = [];
        $validatedValue = $value;

        switch ($field->type) {
            case 'string':
                if (!is_string($value)) {
                    $errors[] = 'The ' . $field->label . ' must be a string.';
                } elseif ($field->validation_rules) {
                    $rules = json_decode($field->validation_rules, true);
                    if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                        $errors[] = 'The ' . $field->label . ' may not be greater than ' . $rules['max_length'] . ' characters.';
                    }
                    if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                        $errors[] = 'The ' . $field->label . ' must be at least ' . $rules['min_length'] . ' characters.';
                    }
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    $errors[] = 'The ' . $field->label . ' must be a number.';
                } else {
                    $validatedValue = (float) $value;
                    if ($field->validation_rules) {
                        $rules = json_decode($field->validation_rules, true);
                        if (isset($rules['min']) && $validatedValue < $rules['min']) {
                            $errors[] = 'The ' . $field->label . ' must be at least ' . $rules['min'] . '.';
                        }
                        if (isset($rules['max']) && $validatedValue > $rules['max']) {
                            $errors[] = 'The ' . $field->label . ' may not be greater than ' . $rules['max'] . '.';
                        }
                    }
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'The ' . $field->label . ' must be a valid email address.';
                }
                break;

            case 'date':
                try {
                    $date = Carbon::createFromFormat('Y-m-d', $value);
                    $validatedValue = $date->format('Y-m-d');

                    if ($field->validation_rules) {
                        $rules = json_decode($field->validation_rules, true);
                        if (isset($rules['min_date'])) {
                            $minDate = Carbon::createFromFormat('Y-m-d', $rules['min_date']);
                            if ($date->lt($minDate)) {
                                $errors[] = 'The ' . $field->label . ' must be after ' . $rules['min_date'] . '.';
                            }
                        }
                        if (isset($rules['max_date'])) {
                            $maxDate = Carbon::createFromFormat('Y-m-d', $rules['max_date']);
                            if ($date->gt($maxDate)) {
                                $errors[] = 'The ' . $field->label . ' must be before ' . $rules['max_date'] . '.';
                            }
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = 'The ' . $field->label . ' must be a valid date in Y-m-d format.';
                }
                break;

            case 'select':
                if ($field->options) {
                    $options = json_decode($field->options, true);
                    $validOptions = array_column($options, 'value');
                    if (!in_array($value, $validOptions)) {
                        $errors[] = 'The selected ' . $field->label . ' is invalid.';
                    }
                }
                break;

            case 'file':
            case 'image':
                // File validation would be handled during upload
                // Here we just check if it's a valid file path or upload reference
                if (!is_string($value)) {
                    $errors[] = 'The ' . $field->label . ' must be a valid file reference.';
                }
                break;

            case 'file_with_text':
                if (!is_array($value) || !isset($value['file']) || !isset($value['text'])) {
                    $errors[] = 'The ' . $field->label . ' must contain both file and text.';
                }
                break;

            case 'file_expiration_date':
                if (!is_array($value) || !isset($value['file']) || !isset($value['expiration_date'])) {
                    $errors[] = 'The ' . $field->label . ' must contain both file and expiration date.';
                } else {
                    try {
                        $expDate = Carbon::createFromFormat('Y-m-d', $value['expiration_date']);
                        if ($expDate->isPast()) {
                            $errors[] = 'The expiration date for ' . $field->label . ' must be in the future.';
                        }
                    } catch (Exception $e) {
                        $errors[] = 'The expiration date for ' . $field->label . ' must be a valid date.';
                    }
                }
                break;

            default:
                // For unknown field types, just accept the value as is
                break;
        }

        return [
            'valid' => empty($errors),
            'value' => $validatedValue,
            'errors' => $errors,
        ];
    }

    /**
     * Get form templates
     */
    public function getFormTemplates(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'nullable|string|in:customer_registration,task_creation,customs_clearance',
                'active_only' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Form_Template::with('fields');

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->boolean('active_only', true)) {
                $query->where('is_active', true);
            }

            $templates = $query->get()->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'type' => $template->type,
                    'description' => $template->description,
                    'is_active' => $template->is_active,
                    'fields' => $template->fields->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'name' => $field->name,
                            'label' => $field->label,
                            'type' => $field->type,
                            'is_required' => $field->is_required,
                            'placeholder' => $field->placeholder,
                            'help_text' => $field->help_text,
                            'options' => $field->options ? json_decode($field->options, true) : null,
                            'validation_rules' => $field->validation_rules ? json_decode($field->validation_rules, true) : null,
                            'order' => $field->order,
                        ];
                    })->sortBy('order')->values(),
                    'created_at' => $template->created_at,
                    'updated_at' => $template->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'templates' => $templates,
                    'total_count' => $templates->count(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get form templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system constants and enums
     */
    public function getConstants(Request $request)
    {
        try {
            $constants = [
                'task_statuses' => [
                    'advertised' => 'Advertised',
                    'in_progress' => 'In Progress',
                    'assign' => 'Assigned',
                    'started' => 'Started',
                    'in_pickup_point' => 'At Pickup Point',
                    'loading' => 'Loading',
                    'in_the_way' => 'In Transit',
                    'in_delivery_point' => 'At Delivery Point',
                    'unloading' => 'Unloading',
                    'completed' => 'Completed',
                    'canceled' => 'Canceled',
                    'refund' => 'Refunded',
                    'invoiced' => 'Invoiced',
                ],
                'task_types' => [
                    'normal' => 'Normal Task',
                    'task_from' => 'Pickup Task',
                    'task_to' => 'Delivery Task',
                ],
                'vehicle_types' => [
                    'small_car' => 'Small Car',
                    'large_car' => 'Large Car',
                    'small_truck' => 'Small Truck',
                    'large_truck' => 'Large Truck',
                    'motorcycle' => 'Motorcycle',
                ],
                'clearance_types' => [
                    'import' => 'Import',
                    'export' => 'Export',
                    'transit' => 'Transit',
                ],
                'clearance_statuses' => [
                    'advertised' => 'Advertised',
                    'assigned' => 'Assigned',
                    'in_progress' => 'In Progress',
                    'documents_review' => 'Documents Under Review',
                    'customs_processing' => 'Customs Processing',
                    'payment_required' => 'Payment Required',
                    'completed' => 'Completed',
                    'canceled' => 'Canceled',
                ],
                'payment_methods' => [
                    'hyperpay_visa' => 'Visa Card',
                    'hyperpay_mastercard' => 'Mastercard',
                    'hyperpay_mada' => 'Mada',
                    'wallet' => 'Wallet Balance',
                    'bank_transfer' => 'Bank Transfer',
                ],
                'payment_statuses' => [
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'canceled' => 'Canceled',
                ],
                'notification_types' => [
                    'task' => 'Task Notifications',
                    'payment' => 'Payment Notifications',
                    'clearance' => 'Clearance Notifications',
                    'offer' => 'Offer Notifications',
                    'system' => 'System Notifications',
                ],
                'currencies' => [
                    'SAR' => 'Saudi Riyal',
                    'USD' => 'US Dollar',
                    'EUR' => 'Euro',
                ],
                'languages' => [
                    'ar' => 'العربية',
                    'en' => 'English',
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $constants
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get constants',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
