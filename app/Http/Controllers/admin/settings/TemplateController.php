<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Http\Controllers\Controller;
use App\Models\Form_Field;
use App\Models\Pricing;
use App\Models\Pricing_Method;
use App\Models\Vehicle;
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
      5 => 'created_at',
    ];

    $search = [];

    $totalData = Form_Template::count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'asc');

    // تجهيز الاستعلام الرئيسي
    $query = Form_Template::query();

    if (!empty($request->input('search.value'))) {
      $search = $request->input('search.value');
      $query->where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%");
    }

    $totalFiltered = $query->count();

    $methods = $query->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];

    if (!empty($methods)) {
      $ids = $start;

      foreach ($methods as $method) {
        $nestedData['id'] = $method->id;
        $nestedData['fake_id'] = ++$ids;
        $nestedData['name'] = $method->name;
        $nestedData['description'] = $method->description ?? '-';
        $nestedData['created_at'] = $method->created_at;

        $data[] = $nestedData;
      }
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

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required|unique:form_templates,name,' .  ($req->id ?? 0)
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    try {
      if (isset($req->id) && !empty($req->id)) {
        $find = Form_Template::where('id', $req->id)->first();
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Template not found')]);
        }
        $done = Form_Template::where('id', $req->id)->update([
          'name' => $req->name,
          'description' => $req->description,
        ]);
      } else {
        $done = Form_Template::create([
          'name' => $req->name,
          'description' => $req->description,
        ]);
      }

      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('error to create Template')]);
      }
      return response()->json(['status' => 1, 'success' => __('Template Created')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }



  public function edit($id)
  {
    $data = Form_Template::with('fields', 'pricing_templates')->find($id);
    $vehicle = Vehicle::all();
    $methods = Pricing_Method::where('status', 1)->get();
    if (!$data) {
      return redirect()->back();
    }
    return view('admin.settings.templates.edit', compact('data', 'vehicle', 'methods'));
  }



  public function update(Request $request)
  {
    $template = Form_Template::find($request->id);
    if (!$template) {
      return response()->json(['status' => 2, 'error' => __('Template not found')]);
    }

    // تحديث الحقول المرتبطة بالنموذج
    $existingFieldIds = collect($request->fields)->pluck('id')->filter()->toArray();

    // حذف الحقول غير الموجودة في الطلب
    Form_Field::where('form_template_id', $request->id)->whereNotIn('id', $existingFieldIds)->delete();

    $done = $request->fields;
    // تحديث أو إضافة الحقول الجديدة
    foreach ($request->fields as $field) {
      if (isset($field['id'])) {
        $done = Form_Field::where('id', $field['id'])->update([
          'name' => $field['name'],
          'label' => $field['label'],
          'type' => $field['type'],
          'required' => $field['required'],
          'value' => $field['value'],
          'driver_can' => $field['driver_can'],
          'customer_can' => $field['customer_can'],
        ]);
      } else {
        $done = Form_Field::create([
          'form_template_id' => $request->id,
          'name' => $field['name'],
          'label' => $field['label'],
          'type' => $field['type'],
          'required' => $field['required'],
          'value' => $field['value'],
          'driver_can' => $field['driver_can'],
          'customer_can' => $field['customer_can'],
        ]);
      }
    }

    return response()->json(['status' => 1, 'success' => __('Template updated successfully'), 'data' =>  $done]);
  }
}
