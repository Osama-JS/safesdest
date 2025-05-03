<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use App\Models\Blockage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BlockagesController extends Controller
{
  public function index()
  {
    return view('admin.settings.blockages');
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'type',
      3 => 'description',
      3 => 'coordinates',
      6 => 'status',
    ];


    $totalData = Blockage::count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'asc');

    $search = $request->input('search');

    // تجهيز الاستعلام الرئيسي
    $query = Blockage::query();

    if (!empty($search)) {
      $query->where('id', 'LIKE', "%{$search}%")
        ->orWhere('id', 'LIKE', "%{$search}%")
        ->orWhere('type', 'LIKE', "%{$search}%")
        ->orWhere('description', 'LIKE', "%{$search}%");
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
        $nestedData['type'] = $method->type . '-' . $method->id;
        $nestedData['description'] = $method->description ?? '';
        $nestedData['coordinates'] = $method->coordinates;
        $nestedData['status'] = $method->status;
        $nestedData['created_at'] = $method->created_at->format('Y-m-d H:i');
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

  public function change_state(Request $req)
  {
    $find = Blockage::findOrFail($req->id);

    $status = $find->status == 1 ? 0 : 1;

    $find->status =  $status;
    $done =  $find->save();
    if (!$done) {
      return response()->json(['status' => 2, 'error' => __('Error to change Blockage status')]);
    }
    return response()->json(['status' => 1, 'success' => $status]);
  }


  public function edit($id)
  {
    try {
      $data = Blockage::findOrFail($id);
      return response()->json($data);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }


  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'type' => 'required|in:point,line',
      'description' => 'nullable|string|max:400',
      'coordinates' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }


    try {
      $data = [
        'type'            => $req->type,
        'description'     => $req->description,
        'coordinates'     => $req->coordinates,
      ];

      if ($req->filled('id')) {
        $find = Blockage::findOrFail($req->id);

        $done = $find->update($data);
      } else {
        $done = Blockage::create($data);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Blockage')]);
      }
      return response()->json(['status' => 1, 'success' => __('Blockage saved successfully')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {
      $find = Blockage::findOrFail($req->id);
      $done =  $find->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Blockage']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Blockage deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
