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

      // Base Pricing
      'base_fare' => 'required|numeric|min:0',
      'base_distance' => 'required|numeric|min:0',
      'base_waiting' => 'required|numeric|min:0',
      'distance_fare' => 'required|numeric|min:0',
      'waiting_fare' => 'required|numeric|min:0',

      // Pricing Methods (optional if selected)
      'methods' => 'array',
      'methods.*' => 'integer|exists:pricing_methods,id',

      // Commission & Discount
      'vat_commission' => 'required|numeric|min:0|max:100',
      'service_commission' => 'required|numeric|min:0|max:100',
      'discount' => 'nullable|numeric|min:0|max:100',
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

    $validator = Validator::make($req->all(), $rules);

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
