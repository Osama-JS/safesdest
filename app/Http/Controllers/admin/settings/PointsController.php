<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use App\Models\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Pricing;
use App\Models\Pricing_Method;
use App\Models\Pricing_Parametar;
use Illuminate\Support\Facades\Validator;

class PointsController extends Controller
{
  public function index()
  {
    return view('admin.settings.points');
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'address',
      6 => 'status',
    ];


    $totalData = Point::count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'asc');

    $search = $request->input('search');

    // تجهيز الاستعلام الرئيسي
    $query = Point::query();

    if (!empty($search)) {
      $query->where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%")
        ->orWhere('address', 'LIKE', "%{$search}%");
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
        $nestedData['address'] = $method->address;
        $nestedData['customer'] = $method->customer ? $method->customer->name : "";
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

  public function getPoints(Request $request)
  {
    $customerIds = $request->input('customer_ids', []);

    // النقاط العامة (بدون customer_id)
    $generalPoints = Point::whereNull('customer_id')->get(['id', 'name']);

    $response = [
      'general' => $generalPoints,
    ];

    if (!empty($customerIds)) {
      foreach ($customerIds as $customerId) {
        $customer = Customer::find($customerId);
        if (!$customer) continue;

        $points = Point::where('customer_id', $customerId)->get(['id', 'name']);

        $response['customer_' . $customerId] = [
          'label' => 'نقاط العميل: ' . $customer->name,
          'points' => $points,
        ];
      }
    }

    return response()->json($response);
  }



  public function change_state(Request $req)
  {
    $find = Point::where('id', $req->id)->first();
    if (!$find) {
      return response()->json(['status' => 2, 'error' => __('Point not found')]);
    }
    $status = $find->status == 1 ? 0 : 1;
    $done = Point::where('id', $req->id)->update([
      'status' => $status,
    ]);
    if (!$done) {
      return response()->json(['status' => 2, 'error' => __('Error to change Point status')]);
    }
    return response()->json(['status' => 1, 'success' => $status]);
  }

  public function edit($id)
  {
    try {
      $data = Point::with(['customer:id,name'])->findOrFail($id);
      return response()->json($data);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required|string',
      'contact_name' => 'nullable|string|max:400',
      'contact_phone' => 'nullable|string|max:50',
      'address' => 'required|string|max:500',
      'latitude' => 'required|numeric',
      'longitude' => 'required|numeric',
      'customer' => 'nullable|exists:customers,id'
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }


    try {
      $data = [
        'name' => $req->name,
        'contact_name' => $req->contact_name,
        'contact_phone' => $req->contact_phone,
        'address' => $req->address,
        'latitude' => $req->latitude,
        'longitude' => $req->longitude,
        'customer_id' => $req->customer ?? null,
      ];

      if ($req->filled('id')) {
        $find = Point::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Point')]);
        }
        $done = $find->update($data);
      } else {
        $done = Point::create($data);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Point')]);
      }
      return response()->json(['status' => 1, 'success' => __('Point saved successfully')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }



  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {
      $find = Point::findOrFail($req->id);
      $methods = Pricing_Method::where('type', 'points')->first();
      $pricing = Pricing::where('pricing_method_id', $methods->id)->pluck('id');
      $parametars = Pricing_Parametar::whereIn('pricing_id', $pricing)->where('from_val', $find->id)->orWhere('to_val', $find->id)->count();
      if ($parametars > 0) {
        return response()->json(['status' => 2, 'error' => 'Error to delete Point. its connect with pricing mater']);
      }
      $done =  $find->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Point']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Point deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
