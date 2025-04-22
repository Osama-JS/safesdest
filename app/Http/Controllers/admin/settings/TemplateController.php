<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use App\Models\Pricing;
use App\Models\Vehicle;
use App\Models\Form_Field;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Models\Pricing_Method;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Geofence;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\DumpCompletionCommand;

class TemplateController extends Controller
{
  public function index()
  {
    return view('admin.settings.templates.index');
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'description',
      4 => 'created_at',
    ];

    $search = [];

    $totalData = Form_Template::count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'desc');

    // تجهيز الاستعلام الرئيسي
    $query = Form_Template::query();

    if (!empty($request->input('search.value'))) {
      $search = $request->input('search.value');
      $query->where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%")
        ->orWhere('description', 'LIKE', "%{$search}%");
    }

    $totalFiltered = $query->count();

    $methods = $query->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();


    $data = [];
    $fakeId = $start;


    foreach ($methods as $method) {
      $data[] = [
        'id' => $method->id,
        'fake_id' => ++$fakeId,
        'name' => $method->name,
        'description' => $method->description ?? '-',
        'created_at' => $method->created_at->format('Y-m-d H:i'),
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

  public function getFields(Request $req)
  {
    $data = Form_Field::where('form_template_id', $req->id)->get();
    if ($data->count() <= 0) {
      return response()->json(['status' => 2, 'error' => __('no fields in this template')]);
    }
    return response()->json(['status' => 1, 'fields' => $data, 'success' => __('Template Created')]);
  }

  public function getPricing(Request $req)
  {
    $fields = Form_Field::where('form_template_id', $req->id)->get();

    $data = Pricing::where('pricing_template_id', $req->id)->where('status', 1)->get();
    if ($fields->count() <= 0) {
      return response()->json(['status' => 2, 'error' => __('no fields in this template')]);
    }
    return response()->json(['status' => 1, 'fields' => $fields, 'pricing' => $data, 'success' => __('Template Created')]);
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required|unique:form_templates,name,' .  ($req->id ?? 0),
      'description' => 'nullable|string',

    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    try {
      $data = [
        'name'          => $req->name,
        'description'   => $req->description,
      ];
      if ($req->filled('id')) {
        $find = Form_Template::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Template')]);
        }
        $done = $find->update($data);
      } else {
        $done = Form_Template::create($data);
      }

      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Template')]);
      }
      return response()->json(['status' => 1, 'success' => __('Template Created successfully')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }



  public function edit($id)
  {
    $data = Form_Template::with('fields', 'pricing_templates')->find($id);
    $vehicle = Vehicle::all();
    $methods = Pricing_Method::where('status', 1)->get();
    $customers = Customer::all();
    $tags = Tag::whereHas('customers')->get();
    $pricing_methods = Pricing_Method::where('status', 1)->get();
    $geofences = Geofence::all();
    if (!$data) {
      return redirect()->back();
    }
    return view('admin.settings.templates.edit', compact('data', 'vehicle', 'methods', 'tags', 'customers', 'pricing_methods', 'geofences'));
  }



  public function update(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'id' => 'required|exists:form_templates,id',
      'fields' => 'required|array|min:1',
      'fields.*.name' => 'required|string',
      'fields.*.label' => 'required|string',
      'fields.*.type' => 'required|in:string,number,email,date,select,file',
      'fields.*.required' => 'required|boolean',
      'fields.*.value' => 'nullable|string',
      'fields.*.driver_can' => 'required|in:hidden,read,write',
      'fields.*.customer_can' => 'required|in:hidden,read,write',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'message' => 'Validator Error', 'error' => $validator->errors()->toArray()]);
    }


    DB::beginTransaction();
    try {
      // تحديث الحقول المرتبطة بالنموذج
      $existingFieldIds = collect($request->fields)->pluck('id')->filter()->toArray();

      // حذف الحقول غير الموجودة في الطلب
      Form_Field::where('form_template_id', $request->id)->whereNotIn('id', $existingFieldIds)->delete();

      $done = $request->fields;
      // تحديث أو إضافة الحقول الجديدة
      foreach ($request->fields as $field) {
        $data = [
          'name' => $field['name'],
          'label' => $field['label'],
          'type' => $field['type'],
          'required' => $field['required'],
          'value' => $field['value'],
          'driver_can' => $field['driver_can'],
          'customer_can' => $field['customer_can'],
        ];
        if (isset($field['id'])) {
          $done = Form_Field::where('id', $field['id'])->update($data);
        } else {
          $data['form_template_id'] = $request->id;
          $done = Form_Field::create($data);
        }
        if (!$done) {
          DB::rollBack();
          return response()->json(['status' => 2, 'error' => __('Error: can not save the Template fields')]);
        }
      }

      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Template fields updated successfully'), 'data' =>  $done]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
