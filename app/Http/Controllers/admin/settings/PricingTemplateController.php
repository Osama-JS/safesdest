<?php

namespace App\Http\Controllers\admin\settings;

use Illuminate\Http\Request;
use App\Models\Pricing_Method;
use App\Models\Pricing_Template;
use App\Http\Controllers\Controller;
use App\Models\Form_Template;
use App\Models\Pricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PricingTemplateController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:templates_settings', ['only' => ['getData', 'edit',  'store', 'change_state', 'destroy']]);
  }

  public function getData(Request $request)
  {
    $find = Form_Template::findOrFail($request->id);
    if (!$find) {
      return response()->json([
        'status' => 0,
        'message' => 'Form Template  not found',
      ]);
    }
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'created_at',
    ];

    $search = [];

    $totalData = Pricing_Template::where('form_template_id', $request->id)->count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'asc');

    // تجهيز الاستعلام الرئيسي
    $query = Pricing_Template::query();
    $query->where('form_template_id', $request->id);

    if (!empty($request->input('search.value'))) {
      $search = $request->input('search.value');
      $query->where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%");
    }

    $totalFiltered = $query->count(); // ✅ حساب العدد بعد البحث

    // تنفيذ جلب البيانات مع الفلترة والتقسيم
    $templates = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    $fakeId = $start;

    foreach ($templates as $method) {
      $data[] = [
        'id' => $method->id,
        'fake_id' => ++$fakeId,
        'name' => $method->name,
        'created_at' => $method->created_at->format('Y-m-d H:i:s'),
      ];
    }

    return response()->json([
      'draw' => intval($request->input('draw')),
      'recordsTotal' => intval($totalData),
      'recordsFiltered' => intval($totalFiltered),
      'code' => 200,
      'data' => $data,
    ]);
  }

  public function getPricingMethod()
  {
    $data = Pricing_Method::where('status', 1)->get();
    return response()->json($data);
  }


  public function edit($id)
  {
    $data = Pricing_Template::with(['tags', 'customers', 'pricing_methods', 'sizes', 'fields', 'geoFences'])
      ->findOrFail($id);

    return response()->json([
      'id' => $data->id,
      'rule_name' => $data->name,
      'decimal_places' => $data->decimal_places,
      'base_fare' => $data->base_fare,
      'base_distance' => $data->base_distance,
      'base_waiting' => $data->base_waiting_time,
      'distance_fare' => $data->distance_fare,
      'waiting_fare' => $data->waiting_fare,
      'vat_commission' => $data->vat_commission,
      'service_commission' => $data->service_tax_commission,
      'discount' => $data->discount_percentage,
      'all_customers' =>  (bool) $data->all_customer,
      'use_tags' => (bool) $data->tags->count() > 0 ? true : false,
      'use_customers' =>  (bool) $data->customers->count() > 0 ? true : false,
      'tags' => $data->tags->pluck('id'),
      'customers' =>  $data->customers->pluck('id'),
      'methods' => $data->pricing_methods->pluck('pricing_method_id'),
      'method_status' => $data->pricing_methods->map(function ($method) {
        return [
          'id' => $method->id,
          'method_id' => $method->pricing_method_id,
          'status' => $method->status,
          'type' => $method->method->type
        ];
      }),
      'params' =>   $data->pricing_methods->map(fn($method) => $method->parametars->map(fn($param) => [
        'method_id' => $method->pricing_method_id,
        'from_val' => $param->from_val,
        'to_val' => $param->to_val,
        'price' => $param->price,
      ])),
      'sizes' => $data->sizes->pluck('id'),
      'field_pricing' => $data->fields->map(fn($item) => [
        'value' => $item->value,
        'option' => $item->option,
        'type' => $item->type,
        'amount' => $item->amount,
        'field_id' => $item->field_id,
        'field_name' => $item->form_field->name,
      ]),
      'geofence_pricing' => $data->geoFences->map(fn($item) => [
        'geofence' => $item->geofence_id,
        'amount' => $item->amount,
        'type' => $item->type,
      ]),
    ]);
  }

  public function store(Request $req)
  {
    $rules = [
      'rule_name' => 'required|string|max:255|unique:pricing_templates,name,' . ($req->id ?? 0),
      'decimal_places' => 'required|integer|min:0|max:10',
      'form_id' => 'required|integer|exists:form_templates,id',
      'all_customers' => 'nullable|in:true',
      'use_customers' => 'nullable|in:true',
      'customers' => 'required_if:use_customers,true|array|min:1',
      'customers.*' => 'required_if:use_customers,true|integer|exists:customers,id',
      'use_tags' => 'nullable|in:true',
      'tags' => 'required_if:use_tags,true|array|min:1',
      'tags.*' => 'required_if:use_tags,true|integer|exists:tags,id',
      'sizes' => 'required|array|min:1',
      'sizes.*' => 'integer|exists:vehicle_sizes,id',
      'base_fare' => 'required|numeric|min:0',
      'base_distance' => 'required|numeric|min:0',
      'base_waiting' => 'required|numeric|min:0',
      'distance_fare' => 'required|numeric|min:0',
      'waiting_fare' => 'required|numeric|min:0',
      'methods' => 'array',
      'methods.*' => 'integer|exists:pricing_methods,id',
      'vat_commission' => 'required|numeric|min:0|max:100',
      'service_commission' => 'required|numeric|min:0|max:100',
      'discount' => 'nullable|numeric|min:0|max:100',
    ];

    $messages = [
      'rule_name.required' => __('The rule name is required.'),
      'rule_name.string' => __('The rule name must be a string.'),
      'rule_name.max' => __('The rule name may not be greater than 255 characters.'),
      'rule_name.unique' => __('The rule name has already been taken.'),
      'decimal_places.required' => __('The decimal places field is required.'),
      'decimal_places.integer' => __('The decimal places must be an integer.'),
      'decimal_places.min' => __('The decimal places must be at least 0.'),
      'decimal_places.max' => __('The decimal places may not be greater than 10.'),
      'form_id.required' => __('The form template is required.'),
      'form_id.integer' => __('The form template id must be an integer.'),
      'form_id.exists' => __('The selected form template is invalid.'),
      'customers.required_if' => __('At least one customer must be selected.'),
      'customers.array' => __('Customers must be an array.'),
      'customers.*.required_if' => __('Each customer is required.'),
      'customers.*.integer' => __('Each customer id must be an integer.'),
      'customers.*.exists' => __('The selected customer is invalid.'),
      'tags.required_if' => __('At least one tag must be selected.'),
      'tags.array' => __('Tags must be an array.'),
      'tags.*.required_if' => __('Each tag is required.'),
      'tags.*.integer' => __('Each tag id must be an integer.'),
      'tags.*.exists' => __('The selected tag is invalid.'),
      'sizes.required' => __('At least one vehicle size must be selected.'),
      'sizes.array' => __('Sizes must be an array.'),
      'sizes.*.integer' => __('Each size id must be an integer.'),
      'sizes.*.exists' => __('The selected vehicle size is invalid.'),
      'base_fare.required' => __('The base fare field is required.'),
      'base_fare.numeric' => __('The base fare must be a number.'),
      'base_fare.min' => __('The base fare must be at least 0.'),
      'base_distance.required' => __('The base distance field is required.'),
      'base_distance.numeric' => __('The base distance must be a number.'),
      'base_distance.min' => __('The base distance must be at least 0.'),
      'base_waiting.required' => __('The base waiting field is required.'),
      'base_waiting.numeric' => __('The base waiting must be a number.'),
      'base_waiting.min' => __('The base waiting must be at least 0.'),
      'distance_fare.required' => __('The distance fare field is required.'),
      'distance_fare.numeric' => __('The distance fare must be a number.'),
      'distance_fare.min' => __('The distance fare must be at least 0.'),
      'waiting_fare.required' => __('The waiting fare field is required.'),
      'waiting_fare.numeric' => __('The waiting fare must be a number.'),
      'waiting_fare.min' => __('The waiting fare must be at least 0.'),
      'vat_commission.required' => __('The VAT commission field is required.'),
      'vat_commission.numeric' => __('The VAT commission must be a number.'),
      'vat_commission.min' => __('The VAT commission must be at least 0.'),
      'vat_commission.max' => __('The VAT commission may not be greater than 100.'),
      'service_commission.required' => __('The service commission field is required.'),
      'service_commission.numeric' => __('The service commission must be a number.'),
      'service_commission.min' => __('The service commission must be at least 0.'),
      'service_commission.max' => __('The service commission may not be greater than 100.'),
      'discount.numeric' => __('The discount must be a number.'),
      'discount.min' => __('The discount must be at least 0.'),
      'discount.max' => __('The discount may not be greater than 100.'),
    ];

    // Pricing Method Parameters
    if ($req->has('methods')) {
      foreach ($req->methods as $method_id) {
        $params = $req->input("params.$method_id", []);
        foreach ($params as $index => $param) {
          $rules["params.$method_id.$index.method_id"] = 'required|integer|exists:pricing_methods,id';
          $rules["params.$method_id.$index.from_val"] = 'required|string';
          $rules["params.$method_id.$index.to_val"] = 'required|string';
          $rules["params.$method_id.$index.price"] = 'required|numeric|min:0';
        }
      }
    }

    // Field Pricing (Dynamic Based on Fields)
    if ($req->has('field_pricing')) {
      foreach ($req->field_pricing as $index => $field) {
        $rules["field_pricing.$index.field_id"] = 'required|integer|exists:form_fields,id';
        $rules["field_pricing.$index.value"] = 'required|string|max:255';
        $rules["field_pricing.$index.option"] = 'required|in:equal,greater,less,not_equal,greater_equal,less_equal';
        $rules["field_pricing.$index.type"] = 'required|in:fixed,percentage';
        $rules["field_pricing.$index.amount"] = 'required|numeric|min:0';
      }
    }


    if ($req->has('geofence_pricing')) {
      $geoIds = [];
      foreach ($req->geofence_pricing as $index => $geo) {
        $rules["geofence_pricing.$index.geofence_id"] = [
          'required',
          'integer',
          'exists:geofences,id',
          function ($attribute, $value, $fail) use (&$geoIds) {
            if (in_array($value, $geoIds)) {
              $fail('Duplicate geofence selected.');
            }
            $geoIds[] = $value;
          }
        ];
        $rules["geofence_pricing.$index.type"] = 'required|in:fixed,percentage';
        $rules["geofence_pricing.$index.amount"] = 'required|numeric|min:0';
      }
    }

    $validator = Validator::make($req->all(), $rules, $messages);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error' => $validator->errors()
      ]);
    }

    DB::beginTransaction();

    try {
      $isUpdate = $req->filled('id');
      $pricing = $isUpdate ? Pricing_Template::findOrFail($req->id) : new Pricing_Template;

      $data = [
        'name'                   => $req->rule_name,
        'decimal_places'         => $req->decimal_places,
        'base_fare'              => $req->base_fare,
        'base_waiting_time'      => $req->base_waiting,
        'waiting_fare'           => $req->waiting_fare,
        'base_distance'          => $req->base_distance,
        'distance_fare'          => $req->distance_fare,
        'discount_percentage'    => $req->discount,
        'vat_commission'         => $req->vat_commission,
        'service_tax_commission' => $req->service_commission,
        'form_template_id'       => $req->form_id,
        'all_customer'          => $req->filled('all_customers') ? $req->all_customers : false,
      ];

      $pricing->fill($data)->save();

      // --------------------
      // 🔄 Sync علاقات Many-to-Many
      // --------------------
      $pricing->sizes()->sync($req->sizes ?? []);

      if ($req->filled('use_tags')) {
        $pricing->tags()->sync($req->tags);
      } else {
        $pricing->tags()->detach();
      }

      if ($req->filled('use_customers')) {
        $pricing->customers()->sync($req->customers);
      } else {
        $pricing->customers()->detach();
      }

      // --------------------
      // 🔁 علاقات HasMany (fields, methods, geofence)
      // --------------------
      if ($isUpdate) {
        // حذف البيانات القديمة
        $pricing->fields()->delete();
        $pricing->pricing_methods()->each(function ($method) {
          $method->parametars()->delete();
          $method->delete();
        });
        $pricing->geoFences()->delete();
      }

      // 🔹 Pricing Fields
      if ($req->filled('field_pricing')) {
        foreach ($req->field_pricing as $field) {
          $pricing->fields()->create([
            'field_id' => $field['field_id'],
            'value'    => $field['value'],
            'option'   => $field['option'],
            'type'     => $field['type'],
            'amount'   => $field['amount'],
          ]);
        }
      }

      // 🔹 Pricing Methods + Parameters
      if ($req->filled('methods')) {
        foreach ($req->methods as $methodId) {
          $method = $pricing->pricing_methods()->create([
            'pricing_method_id' => $methodId,
          ]);

          if (isset($req->params[$methodId])) {
            foreach ($req->params[$methodId] as $param) {
              $method->parametars()->create([
                'from_val' => $param['from_val'],
                'to_val'   => $param['to_val'],
                'price'    => $param['price'],
              ]);
            }
          }
        }
      }

      // 🔹 Geofence Pricing
      if ($req->filled('geofence_pricing')) {
        foreach ($req->geofence_pricing as $geo) {
          $pricing->geoFences()->create([
            'geofence_id' => $geo['geofence_id'],
            'type'        => $geo['type'],
            'amount'      => $geo['amount'],
          ]);
        }
      }

      DB::commit();

      return response()->json([
        'status' => 1,
        'success' => $isUpdate ? 'Pricing rule updated successfully.' : 'Pricing rule created successfully.',
      ]);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error' => 'Error: ' . $e->getMessage(),
      ]);
    }
  }

  public function change_state(Request $req)
  {
    $find = Pricing::find($req->id);
    if (!$find) {
      return response()->json(['status' => 2, 'error' => __('Pricing Method not found')]);
    }
    $status = $find->status == 1 ? 0 : 1;
    $done = $find->update([
      'status' => $status,
    ]);
    if (!$done) {
      return response()->json(['status' => 2, 'error' => __('Error to change Pricing Method status')]);
    }
    return response()->json(['status' => 1, 'success' => $status]);
  }

  public function destroy($id)
  {
    $pricing = Pricing_Template::findOrFail($id);
    $pricing->delete();

    return response()->json([
      'status' => 1,
      'success' => 'Pricing rule deleted successfully.',
    ]);
  }
}
