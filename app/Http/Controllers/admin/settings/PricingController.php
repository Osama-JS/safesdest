<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use Illuminate\Http\Request;
use App\Models\Pricing_Method;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;


class PricingController extends Controller
{
  public function index()
  {
    return view('admin.settings.pricing');
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'description',
      6 => 'status',
    ];

    $search = [];

    $totalData = Pricing_Method::count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'asc');

    // تجهيز الاستعلام الرئيسي
    $query = Pricing_Method::query();

    if (!empty($request->input('search.value'))) {
      $search = $request->input('search.value');
      $query->where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%");
    }

    $totalFiltered = $query->count(); // ✅ حساب العدد بعد البحث

    // تنفيذ جلب البيانات مع الفلترة والتقسيم
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
        $nestedData['description'] = $method->description;
        $nestedData['status'] = $method->status;

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


  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required|unique:pricing_methods,name,' .  $req->id,
      'description' => 'required|string',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    try {
      $data = [
        'name'                  => $req->name,
        'description'           => $req->description,

      ];

      $find = Pricing_Method::findOrFail($req->id);;
      if (!$find) {
        return response()->json(['status' => 2, 'error' => __('Can not find the selected Pricing Method')]);
      }

      $done = $find->update($data);

      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('error to save Pricing Method')]);
      }
      return response()->json(['status' => 1, 'success' => __('Pricing Method saved successfully')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function edit($id): JsonResponse
  {
    $data = Pricing_Method::findOrFail($id);
    return response()->json($data);
  }




  public function change_state(Request $req)
  {
    $find = Pricing_Method::where('id', $req->id)->first();
    if (!$find) {
      return response()->json(['status' => 2, 'error' => __('Pricing Method not found')]);
    }
    $status = $find->status == 1 ? 0 : 1;
    $done = Pricing_Method::where('id', $req->id)->update([
      'status' => $status,
    ]);
    if (!$done) {
      return response()->json(['status' => 2, 'error' => __('Error to change Pricing Method status')]);
    }
    return response()->json(['status' => 1, 'success' => $status]);
  }
}
