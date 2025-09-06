<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Pricing;
use App\Models\Form_Field;
use App\Models\Point;
use App\Models\Pricing_Method;
use App\Models\Pricing_Geofence;
use App\Models\Pricing_Parametar;
use App\Models\Pricing_Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\MapboxService;
use Illuminate\Validation\Rule;

class TaskPricingService
{
    protected $mapbox;

    public function __construct(MapboxService $mapbox)
    {
        $this->mapbox = $mapbox;
    }

    // check the inputs
    public function validateRequest($request, $type = '')
    {
        $rules = $this->buildValidationRules($request, $type);
        $customMessages = $this->buildCustomMessages($request);
        $validator = Validator::make($request->all(), $rules, $customMessages);

        if ($validator->fails()) {
            return ['status' => false, 'errors' => $validator->errors()];
        }

        $sizeCheck = $this->validateVehicleSizes($request->input('vehicles'));

        if ($sizeCheck !== true) {
            return ['status' => false, 'errors' => ['vehicles' => $sizeCheck]];
        }

        return ['status' => true];
    }

    protected function buildValidationRules($request, $type)
    {
        $rules = [
          'owner' => 'required|in:admin,customer',
          'customer' => 'required_if:owner,customer',
          'template' => 'required|exists:form_templates,id',
          'vehicles.*.vehicle' => 'required|exists:vehicles,id',
          'vehicles.*.vehicle_type' => 'required|exists:vehicle_types,id',
          'vehicles.*.vehicle_size' => 'required|exists:vehicle_sizes,id',
          'vehicles.*.quantity' => 'required|integer|min:1',
          'pricing_method' => [
            'required',
            function ($attribute, $value, $fail) {
                if ($value != 0 && ! DB::table('pricing_methods')->where('id', $value)->exists()) {
                    $fail(__('The selected pricing method not available'));
                }
            }
          ],


          'pickup_name' => 'required|string|max:200',
          'pickup_phone' => 'required|string|max:200',
          'pickup_email' => 'required|email',
          'pickup_before' => 'required|date',
          'pickup_longitude' => 'required|string',
          'pickup_latitude' => 'required|string',
          'pickup_note' => 'nullable|string|max:500',
          'pickup_image' => 'nullable|file',
          'delivery_name' => 'required|string|max:200',
          'delivery_phone' => 'required|string|max:200',
          'delivery_email' => 'required|email',
          'delivery_before' => 'required|date',
          'delivery_longitude' => 'required|string',
          'delivery_latitude' => 'required|string',
          'delivery_note' => 'nullable|string|max:500',
          'delivery_image' => 'nullable|file',
        ];

        if ($request->filled('params_select')) {
            $rules['params_select'] = 'required|exists:pricing_parametars,id';
        }

        if ($request->filled('template')) {
            $fields = Form_Field::where('form_template_id', $request->template)->get();
            foreach ($fields as $field) {
                $fieldKey = 'additional_fields.' . $field->name;
                $rules[$fieldKey] = [];
                // لا نضع required للحقول المركبة هنا
                if (!$request->filled('id') && $field->required && !in_array($field->type, ['file_expiration_date', 'file_with_text'])) {
                    $rules[$fieldKey][] = 'required';
                }

                // إضافة قواعد بناءً على نوع الحقل
                switch ($field->type) {
                    case 'text':
                        $rules[$fieldKey][] = 'string';
                        break;

                    case 'number':
                        $rules[$fieldKey][] = 'numeric';
                        break;
                    case 'url':
                        $rules[$fieldKey][] = 'url';
                        break;
                    case 'date':
                        $rules[$fieldKey][] = 'date';
                        break;

                    case 'file':
                        $rules[$fieldKey][] = 'file';
                        $rules[$fieldKey][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif'; // أنواع موثوقة
                        $rules[$fieldKey][] = 'max:10240'; // 10MB
                        break;

                    case 'image':
                        $rules[$fieldKey][] = 'image';
                        $rules[$fieldKey][] = 'mimes:jpeg,png,jpg,webp,gif';
                        $rules[$fieldKey][] = 'max:5120'; // 5MB
                        break;

                    case 'file_expiration_date':
                        // إزالة القاعدة العامة للحقل الأساسي
                        unset($rules[$fieldKey]);

                        // قواعد الملف
                        $rules[$fieldKey . '_file'] = [];
                        $rules[$fieldKey . '_file'][] = 'file';
                        $rules[$fieldKey . '_file'][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif';
                        $rules[$fieldKey . '_file'][] = 'max:10240';

                        // قواعد تاريخ الانتهاء
                        $rules[$fieldKey . '_expiration'] = [];
                        $rules[$fieldKey . '_expiration'][] = 'nullable';
                        $rules[$fieldKey . '_expiration'][] = 'date';
                        $rules[$fieldKey . '_expiration'][] = 'after_or_equal:today';

                        // إذا الحقل مطلوب
                        if ($field->required) {
                            if (!$request->filled('id')) {
                                // عند الإنشاء: الملف مطلوب
                                $rules[$fieldKey . '_file'][] = 'required';
                                $rules[$fieldKey . '_expiration'][] = 'required';
                            } else {
                                // عند التحديث: إذا تم رفع ملف جديد، تاريخ الانتهاء مطلوب
                                if ($request->hasFile("additional_fields.{$field->name}_file")) {
                                    $rules[$fieldKey . '_expiration'][] = 'required';
                                }
                            }
                        }

                        // قاعدة مهمة: إذا تم رفع ملف، التاريخ مطلوب (حتى لو الحقل غير مطلوب)
                        if ($request->hasFile("additional_fields.{$field->name}_file")) {
                            $rules[$fieldKey . '_expiration'][] = 'required';
                        }

                        break;

                    case 'file_with_text':
                        // إزالة القاعدة العامة للحقل الأساسي
                        unset($rules[$fieldKey]);

                        // قواعد الملف
                        $rules[$fieldKey . '_file'] = [];
                        $rules[$fieldKey . '_file'][] = 'file';
                        $rules[$fieldKey . '_file'][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif';
                        $rules[$fieldKey . '_file'][] = 'max:10240';

                        // قواعد النص/الرقم
                        $rules[$fieldKey . '_text'] = [];
                        $rules[$fieldKey . '_text'][] = 'nullable';
                        $rules[$fieldKey . '_text'][] = 'string';
                        $rules[$fieldKey . '_text'][] = 'max:255';

                        // إذا الحقل مطلوب
                        if ($field->required) {
                            if (!$request->filled('id')) {
                                // عند الإنشاء: الملف مطلوب
                                $rules[$fieldKey . '_file'][] = 'required';
                                $rules[$fieldKey . '_text'][] = 'required';
                            } else {
                                // عند التحديث: إذا تم رفع ملف جديد، النص مطلوب
                                if ($request->hasFile("additional_fields.{$field->name}_file")) {
                                    $rules[$fieldKey . '_text'][] = 'required';
                                }
                            }
                        }

                        // قاعدة مهمة: إذا تم رفع ملف، النص مطلوب (حتى لو الحقل غير مطلوب)
                        if ($request->hasFile("additional_fields.{$field->name}_file")) {
                            $rules[$fieldKey . '_text'][] = 'required';
                        }

                        break;

                    default:
                        if (!$field->required) {
                            $rules[$fieldKey][] = 'nullable';
                        }
                        $rules[$fieldKey][] = 'string';
                        break;
                }
            }
        }
        if ($request->filled('manual_total_pricing')) {
            $rules['manual_total_pricing'] = 'required|numeric|min:0';
        } else {
            $rules['manual_total_pricing'] = 'nullable|numeric|min:0';
        }

        if ($request->filled('manual_commission')) {
            $rules['manual_commission'] = 'required|numeric|min:0';
        } else {
            $rules['manual_commission'] = 'nullable|numeric|min:0';
        }


        if ($request->filled('pricing_details')) {
            $rules['pricing_details'] = 'nullable|array';
            $rules['pricing_details.*.label'] = 'required_with:pricing_details.*.amount|string';
            $rules['pricing_details.*.amount'] = 'required_with:pricing_details.*.label|numeric';
        }

        return $rules;
    }

    protected function buildCustomMessages($request)
    {
        $customMessages = [];

        if ($request->filled('template')) {
            $fields = Form_Field::where('form_template_id', $request->template)->get();

            foreach ($fields as $field) {
                if ($field->type === 'file_expiration_date') {
                    $fieldKey = 'additional_fields.' . $field->name;
                    $customMessages = array_merge($customMessages, [
                      $fieldKey . '_file.required' => __('The :attribute file is required.', ['attribute' => $field->label]),
                      $fieldKey . '_file.file' => __('The :attribute must be a valid file.', ['attribute' => $field->label]),
                      $fieldKey . '_file.mimes' => __('The :attribute must be a file of type: pdf, doc, docx, xls, xlsx, txt, csv, jpeg, png, jpg, webp, gif.', ['attribute' => $field->label]),
                      $fieldKey . '_file.max' => __('The :attribute file size must not exceed 10MB.', ['attribute' => $field->label]),
                      $fieldKey . '_expiration.required' => __('The expiration date for :attribute is required.', ['attribute' => $field->label]),
                      $fieldKey . '_expiration.date' => __('The expiration date for :attribute must be a valid date.', ['attribute' => $field->label]),
                      $fieldKey . '_expiration.after_or_equal' => __('The expiration date for :attribute must be today or a future date.', ['attribute' => $field->label]),
                    ]);
                }
            }
        }

        return $customMessages;
    }

    protected function validateVehicleSizes($vehicles)
    {
        $sizes = collect($vehicles)->pluck('vehicle_size')->unique()->filter()->values();
        if ($sizes->count() > 1) {
            return __('You cannot select more than one truck size in the same order');
        }
        return true;
    }
    // end check the inputs


    public function calculatePricing($request)
    {

        $sizes = collect($request->input('vehicles'))->pluck('vehicle_size')->unique()->filter()->values();
        $pricingTemplate = Pricing_Template::availableForCustomer(
            $request->template,
            $request->customer ?? null,
            $sizes
        )->first();

        if (!$pricingTemplate) {
            return ['status' => false, 'errors' => __('There is no Pricing Role match with your selections')];
        }

        $pricing = "";
        if ($request->pricing_method != 0) {
            $method = Pricing_Method::whereIn(
                'id',
                Pricing::where('pricing_template_id', $pricingTemplate->id)->where('status', true)->pluck('pricing_method_id')
            )->where('id', $request->pricing_method)->first();

            if (!$method) {
                return ['status' => false, 'errors' => __('Error to find Pricing Method')];
            }

            $pricing = Pricing::where('pricing_template_id', $pricingTemplate->id)
              ->where('pricing_method_id', $method->id)
              ->first();
        }



        $data = [
          'pricing_role' => $pricingTemplate->name,
          'pricing_method' => $method->name ?? 'Place your offer',
          'pricing_method_id' => $method->id ?? 0,
        ];

        $taskData = [
          'pricing' => $pricingTemplate->id,
          'vehicles' => $vehicles = array_column($request->vehicles, 'vehicle_size'),
          'method' => $method->id ?? 0,
        ];



        $totalPrice = 0;
        if ($request->pricing_method != 0 && $method->type === 'distance') {
            $totalPrice = $pricingTemplate->base_fare;
        }

        $totalPrice += $this->calculateDistancePricing($pricing, $method->type ?? 'manual', $request, $data);
        if ($request->pricing_method != 0) {
            $totalPrice = $this->calculateFieldsPricing($pricingTemplate, $request, $data, $totalPrice);

            $totalPrice += $this->calculateGeofencePricing($pricingTemplate, $request, $data);


            if ($pricingTemplate->service_commission_status) {
                if ($pricingTemplate->service_commission_type === 'fixed') {
                    $totalPrice += $pricingTemplate->service_tax_commission;
                    $serviceCommission = $pricingTemplate->service_tax_commission;
                } elseif ($pricingTemplate->service_commission_type === 'percentage') {
                    $totalPrice += $totalPrice * ($pricingTemplate->service_tax_commission / 100);
                    $serviceCommission = $totalPrice * ($pricingTemplate->service_tax_commission / 100);
                }
                $data['service_commission_type'] = $pricingTemplate->service_commission_type;
                $data['service_tax_commission'] = $pricingTemplate->service_tax_commission;
                $data['service_commission'] =  $serviceCommission;
            }




            $totalPrice -= $totalPrice * ($pricingTemplate->discount_percentage / 100);
            $totalPrice += $totalPrice * ($pricingTemplate->vat_commission / 100);


            $data['vat_commission'] = $pricingTemplate->vat_commission;

            $data['discount_percentage'] = $pricingTemplate->discount_percentage;

            $data['total_price'] = $totalPrice;
        } else {
            if ($pricingTemplate->service_commission_status) {
                if ($pricingTemplate->service_commission_type === 'fixed') {
                    $totalPrice += $pricingTemplate->service_tax_commission;
                } elseif ($pricingTemplate->service_commission_type === 'percentage') {
                    $totalPrice += $totalPrice * ($pricingTemplate->service_tax_commission / 100);
                }
                $data['service_commission_type'] = $pricingTemplate->service_commission_type;
                $data['service_tax_commission'] = $pricingTemplate->service_tax_commission;
            }
            $data['vat_commission'] = $pricingTemplate->vat_commission;
        }


        $vehicles = array_column($request->vehicles, 'quantity');
        $totalVehicles = array_sum($vehicles);

        if ($totalVehicles > 1) {
            $data['vehicles'] = "You want {$totalVehicles} vehicles, so we will create {$totalVehicles} tasks with the same information.";
        }

        $taskData['vehicles_quantity'] = $totalVehicles;

        $drivers = Driver::select('id', 'name')->whereIn('vehicle_size_id', $sizes)->get();
        $data['drivers'] = $drivers;

        return ['status' => true, 'data' => $data, 'task' => $taskData];
    }

    protected function calculateDistancePricing($pricing, $method, $request, &$data)
    {
        $price = 0;


        if ($method === 'distance') {
            $pickup = [$request->pickup_longitude, $request->pickup_latitude];
            $delivery = [$request->delivery_longitude, $request->delivery_latitude];
            $route = $this->mapbox->calculateRoute($pickup, $delivery);

            if (isset($route['error'])) {
                throw new \Exception($route['error']);
            }

            $row = $pricing->parametars()
              ->whereRaw('CAST(from_val AS DECIMAL(10, 2)) <= ?', $route['distance_km'])
              ->whereRaw('CAST(to_val AS DECIMAL(10, 2)) >= ?', $route['distance_km'])
              ->first();

            $price = $route['distance_km'] * $row->price;
            $data['distance'] = $route['distance_km'];
            $data['distance_price_kilo'] = $row->price;
            $data['distance_price'] = $price;
        } elseif ($method === 'points') {
            $param = Pricing_Parametar::findOrFail($request->params_select);
            $price = $param->price;
            $pointFrom = Point::find($param->from_val);
            $pointTo = Point::find($param->to_val);
            $data['point_id'] = $param->id;
            $data['points'] = 'From: ' . $pointFrom->name . ' To: ' . $pointTo->name;
        } elseif ($method === 'manual') {
            $data['manual'] = true;
        }
        return $price;
    }

    protected function calculateFieldsPricing($pricingTemplate, $request, &$data, $totalPrice)
    {
        $price = $totalPrice;
        $data['fields'] = [];
        if ($pricingTemplate->fields->count() > 0) {
            $inputs = $request->input('additional_fields', []);
            foreach ($pricingTemplate->fields as $pricingField) {
                $fieldName = $pricingField->form_field->name;
                $userValue = $inputs[$fieldName] ?? null;

                if (is_null($userValue)) {
                    continue;
                }

                $shouldApply = match ($pricingField->option) {
                    'equal' => $userValue === $pricingField->value,
                    'not_equal' => $userValue !== $pricingField->value,
                    'greater' => $userValue > $pricingField->value,
                    'less' => $userValue < $pricingField->value,
                    'greater_equal' => $userValue >= $pricingField->value,
                    'less_equal' => $userValue <= $pricingField->value,
                    default => false
                };

                if ($shouldApply) {
                    $increment = $pricingField->type === 'fixed' ? $pricingField->amount : ($price * ($pricingField->amount / 100));
                    $price += $increment;

                    $data['fields'] = [
                      'name' => $pricingField->form_field->label,
                      'value' => $userValue,
                      'type' => $pricingField->type,
                      'amount' => $pricingField->amount,
                      'increase' => $increment,
                    ];
                }
            }
        }
        return $price;
    }

    protected function calculateGeofencePricing($pricingTemplate, $request, &$data)
    {
        $price = 0;
        $data['geo_fence'] = [];
        if ($pricingTemplate->geoFences->count() > 0) {
            $pickupWKT = "POINT({$request->pickup_longitude} {$request->pickup_latitude})";
            $deliveryWKT = "POINT({$request->delivery_longitude} {$request->delivery_latitude})";

            $matchedPickup = DB::table('geofences')
              ->whereRaw("ST_Contains(coordinates, ST_GeomFromText(?, 4326))", [$pickupWKT])
              ->pluck('id')
              ->toArray();

            $matchedDelivery = DB::table('geofences')
              ->whereRaw("ST_Contains(coordinates, ST_GeomFromText(?, 4326))", [$deliveryWKT])
              ->pluck('id')
              ->toArray();

            $matchedIds = array_unique(array_merge($matchedPickup, $matchedDelivery));

            if (empty($matchedIds)) {
                return 0;
            }

            $pricingGeofences = Pricing_Geofence::where('pricing_template_id', $pricingTemplate->id)
              ->whereIn('geofence_id', $matchedIds)
              ->get();

            foreach ($pricingGeofences as $pg) {
                $increment = $pg->type === 'fixed' ? $pg->amount : ($price * ($pg->amount / 100));
                $price += $increment;

                $data['geo_fence'] = [
                  'name' => $pg->geofence->name,
                  'type' => $pg->type,
                  'amount' => $pg->amount,
                  'increase' => $increment,
                ];
            }
        }
        return $price;
    }
}
