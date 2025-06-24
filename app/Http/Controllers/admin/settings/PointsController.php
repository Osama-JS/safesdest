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

  public function __construct()
  {
    $this->middleware('permission:geo_fence_settings', ['only' => ['index', 'getData', 'change_state', 'edit', 'store', 'destroy']]);
  }

  public function index()
  {
    return view('admin.settings.points');
  }

  public function getData(Request $request)
  {
    $search = $request->input('search');

    // جلب جميع النقاط مع العملاء
    $query = Point::with('customer');

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('name', 'LIKE', "%{$search}%")
          ->orWhere('address', 'LIKE', "%{$search}%")
          ->orWhereHas('customer', function ($customerQuery) use ($search) {
            $customerQuery->where('name', 'LIKE', "%{$search}%");
          });
      });
    }

    $points = $query->orderBy('customer_id', 'asc')
      ->orderBy('name', 'asc')
      ->get();

    // تجميع النقاط حسب العملاء
    $generalPoints = [];
    $customerGroups = [];

    foreach ($points as $point) {
      if ($point->customer_id) {
        if (!isset($customerGroups[$point->customer_id])) {
          $customerGroups[$point->customer_id] = [
            'customer' => $point->customer,
            'points' => []
          ];
        }
        $customerGroups[$point->customer_id]['points'][] = $point;
      } else {
        $generalPoints[] = $point;
      }
    }

    $data = [];
    $fakeId = 0;

    // إضافة مجموعات العملاء
    foreach ($customerGroups as $customerId => $group) {
      $customer = $group['customer'];
      $customerPoints = $group['points'];

      // إضافة الصف الرئيسي للعميل
      $data[] = [
        'id' => 'customer-' . $customerId,
        'fake_id' => ++$fakeId,
        'name' => $customer->name,
        'address' => '',
        'customer' => '',
        'status' => '',
        'is_parent' => true,
        'parent_type' => 'customer',
        'points_count' => count($customerPoints),
        'customer_id' => $customerId,
        'customer_name' => $customer->name
      ];

      // إضافة النقاط التابعة للعميل
      foreach ($customerPoints as $point) {
        $data[] = [
          'id' => $point->id,
          'fake_id' => ++$fakeId,
          'name' => $point->name,
          'address' => $point->address,
          'customer' => $customer->name,
          'status' => $point->status,
          'is_parent' => false,
          'parent_id' => 'customer-' . $customerId,
          'contact_name' => $point->contact_name,
          'contact_phone' => $point->contact_phone,
          'latitude' => $point->latitude,
          'longitude' => $point->longitude
        ];
      }
    }

    // إضافة النقاط العامة
    if (!empty($generalPoints)) {
      // إضافة الصف الرئيسي للنقاط العامة
      $data[] = [
        'id' => 'general',
        'fake_id' => ++$fakeId,
        'name' => __('General Points'),
        'address' => '',
        'customer' => '',
        'status' => '',
        'is_parent' => true,
        'parent_type' => 'general',
        'points_count' => count($generalPoints)
      ];

      // إضافة النقاط العامة
      foreach ($generalPoints as $point) {
        $data[] = [
          'id' => $point->id,
          'fake_id' => ++$fakeId,
          'name' => $point->name,
          'address' => $point->address,
          'customer' => __('General'),
          'status' => $point->status,
          'is_parent' => false,
          'parent_id' => 'general',
          'contact_name' => $point->contact_name,
          'contact_phone' => $point->contact_phone,
          'latitude' => $point->latitude,
          'longitude' => $point->longitude
        ];
      }
    }

    $totalData = Point::count();
    $totalFiltered = $points->count();

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
    ], [
      'name.required' => __('The point name is required.'),
      'name.string' => __('The point name must be a string.'),
      'contact_name.string' => __('The contact name must be a string.'),
      'contact_name.max' => __('The contact name may not be greater than 400 characters.'),
      'contact_phone.string' => __('The contact phone must be a string.'),
      'contact_phone.max' => __('The contact phone may not be greater than 50 characters.'),
      'address.required' => __('The address field is required.'),
      'address.string' => __('The address must be a string.'),
      'address.max' => __('The address may not be greater than 500 characters.'),
      'latitude.required' => __('The latitude field is required.'),
      'latitude.numeric' => __('The latitude must be a number.'),
      'longitude.required' => __('The longitude field is required.'),
      'longitude.numeric' => __('The longitude must be a number.'),
      'customer.exists' => __('The selected customer is invalid.'),
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
        return response()->json(['status' => 2, 'error' => __('Error to delete Point. its connect with pricing mater')]);
      }
      $done =  $find->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('Error to delete Point')]);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Point deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
