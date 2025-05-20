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
  public function validateRequest($request)
  {
    $rules = $this->buildValidationRules($request);
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return ['status' => false, 'errors' => $validator->errors()];
    }

    $sizeCheck = $this->validateVehicleSizes($request->input('vehicles'));

    if ($sizeCheck !== true) {
      return ['status' => false, 'errors' => ['vehicles' => $sizeCheck]];
    }

    return ['status' => true];
  }

  protected function buildValidationRules($request)
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

        // إذا لم تكن العملية تعديل أو الحقل مطلوب فعليًا
        if (!$request->filled('id') && $field->required) {
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

          default:
            $rules[$fieldKey][] = 'string';
            break;
        }
      }
    }

    return $rules;
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
      if ($method->type !== 'points') {
        $totalPrice += $this->calculateFieldsPricing($pricingTemplate, $request, $data, $totalPrice);
        $totalPrice += $this->calculateGeofencePricing($pricingTemplate, $request, $data);
        $totalPrice += $totalPrice * ($pricingTemplate->vat_commission / 100);
        $totalPrice += $totalPrice * ($pricingTemplate->service_tax_commission / 100);
        $totalPrice -= $totalPrice * ($pricingTemplate->discount_percentage / 100);

        $data['vat_commission'] = $pricingTemplate->vat_commission;
        $data['service_tax_commission'] = $pricingTemplate->service_tax_commission;
        $data['discount_percentage'] = $pricingTemplate->discount_percentage;
      }


      $data['total_price'] = $totalPrice;
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

        if (is_null($userValue)) continue;

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
          $price = $increment;

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

      if (empty($matchedIds)) return 0;

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
