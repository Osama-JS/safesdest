<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Clearance_Pricing_Template;
use Illuminate\Support\Facades\DB;

class ClearancePricingTemplateController extends Controller
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

    $totalData = Clearance_Pricing_Template::where('form_template_id', $request->id)->count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'asc');

    // تجهيز الاستعلام الرئيسي
    $query = Clearance_Pricing_Template::query();
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

  public function edit($id)
  {
    try {
      $data = Clearance_Pricing_Template::with(['tags', 'customers'])
        ->findOrFail($id);
      return response()->json([
        'id' => $data->id,
        'rule_name' => $data->name,
        'decimal_places' => $data->decimal_places,
        'vat_commission' => $data->vat_commission,
        'service_commission' => $data->service_commission,
        'service_commission_status' => (bool) $data->service_commission_status,
        'service_commission_type' => $data->service_commission_type,
        'all_customers' =>  (bool) $data->all_customer,
        'use_tags' => (bool) $data->tags->count() > 0 ? true : false,
        'use_customers' =>  (bool) $data->customers->count() > 0 ? true : false,
        'tags' => $data->tags->pluck('id'),
        'customers' =>  $data->customers->pluck('id'),
      ]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function store(Request $req)
  {
    $rules = [
      'rule_name' => 'required|string|max:255|unique:clearance_pricing_template,name,' . ($req->id ?? 0),
      'decimal_places' => 'required|integer|min:0|max:10',
      'form_id' => 'required|integer|exists:form_templates,id',
      'all_customers' => 'nullable|in:true',
      'use_customers' => 'nullable|in:true',
      'customers' => 'required_if:use_customers,true|array|min:1',
      'customers.*' => 'required_if:use_customers,true|integer|exists:customers,id',
      'use_tags' => 'nullable|in:true',
      'tags' => 'required_if:use_tags,true|array|min:1',
      'tags.*' => 'required_if:use_tags,true|integer|exists:tags,id',
      'vat_commission' => 'required|numeric|min:0|max:100',
      'service_commission' => [
        'required_if:service_commission_status,true',
        'numeric',
        'min:0',
        function ($attribute, $value, $fail) use ($req) {
          if ($req->filled('service_commission_status') && $req->service_commission_status) {
            if ($req->service_commission_type === 'percentage' && $value > 100) {
              $fail('Service commission percentage cannot exceed 100%.');
            }
          }
        }
      ],
      'service_commission_status' => 'nullable|boolean',
      'service_commission_type' => 'required_if:service_commission_status,true|in:fixed,percentage',
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
      'vat_commission.required' => __('The VAT commission field is required.'),
      'vat_commission.numeric' => __('The VAT commission must be a number.'),
      'vat_commission.min' => __('The VAT commission must be at least 0.'),
      'vat_commission.max' => __('The VAT commission may not be greater than 100.'),
      'service_commission.required' => __('The service commission field is required.'),
      'service_commission.numeric' => __('The service commission must be a number.'),
      'service_commission.min' => __('The service commission must be at least 0.'),
      'service_commission.max' => __('The service commission may not be greater than 100.'),

    ];
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
      $pricing = $isUpdate ? Clearance_Pricing_Template::findOrFail($req->id) : new Clearance_Pricing_Template;

      $data = [
        'name'                   => $req->rule_name,
        'decimal_places'         => $req->decimal_places,
        'vat_commission'         => $req->vat_commission,
        'service_commission' => $req->filled('service_commission_status') && $req->service_commission_status ? $req->service_commission : 0,
        'service_commission_status' => $req->filled('service_commission_status') ? (bool)$req->service_commission_status : false,
        'service_commission_type' => $req->filled('service_commission_status') && $req->service_commission_status ? $req->service_commission_type : 'percentage',
        'form_template_id'       => $req->form_id,
        'all_customer'          => $req->filled('all_customers') ? $req->all_customers : false,
      ];

      $pricing->fill($data)->save();

      // --------------------
      // 🔄 Sync علاقات Many-to-Many
      // --------------------

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


      DB::commit();

      return response()->json([
        'status' => 1,
        'success' => $isUpdate ? __('Pricing rule updated successfully.') : __('Pricing rule created successfully.'),
      ]);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error' => 'Error: ' . $e->getMessage(),
      ]);
    }
  }

  public function destroy($id)
  {
    $pricing = Clearance_Pricing_Template::findOrFail($id);
    $pricing->delete();

    return response()->json([
      'status' => 1,
      'success' => 'Pricing rule deleted successfully.',
    ]);
  }
}
