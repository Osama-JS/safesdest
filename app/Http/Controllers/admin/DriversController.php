<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\Driver;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\Teams;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DriversController extends Controller
{
  public function index()
  {
    $templates = Form_Template::all();
    $teams = Teams::all();
    $roles = Role::where('guard_name', 'driver')->get();

    return view('admin.drivers.index', compact('templates', 'teams', 'roles'));
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'username',
      3 => 'name',
      4 => 'email',
      5 => 'phone',
      6 => 'role',
      7 => 'tags',
      8 => 'status',
      9 => 'created_at'
    ];


    $totalData = Driver::count();
    $totalFiltered = $totalData;

    $limit = $request->input('length');
    $start = $request->input('start');
    $order = $columns[$request->input('order.0.column')];
    $dir = $request->input('order.0.dir') ?? 'desc';

    $search = $request->input('search');
    $statusFilter = $request->input('status');

    $query = Driver::query();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%")
          ->orWhere('name', 'LIKE', "%{$search}%")
          ->orWhere('username', 'LIKE', "%{$search}%")
          ->orWhere('email', 'LIKE', "%{$search}%")
          ->orWhere('phone', 'LIKE', "%{$search}%");
      });
    }
    if (!empty($statusFilter)) {
      $query->where('status', $statusFilter);
    }

    $totalFiltered = $query->count();

    $drivers = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();


    $data = [];
    $fakeId = $start;


    foreach ($drivers as $val) {

      $data[] = [
        'id' => $val->id,
        'fake_id' => ++$fakeId,
        'name' => $val->name,
        'image'      => $val->image ? url($val->image) : null,
        'username' => $val->username,
        'email' => $val->email,
        'phone' => $val->phone,
        'tags'       => "",
        'role'       => $val->role->name ?? "",
        'created_at' => $val->created_at->format('Y-m-d H:i'),
        'status'     => $val->status,
      ];
    }


    return response()->json([
      'draw'            => intval($request->input('draw')),
      'recordsTotal'    => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code'            => 200,
      'data'            => $data,
      'summary' => [
        'total' => Driver::count(),
        'total_active' => Driver::where('status', 'active')->count(),
        'total_verified' => Driver::where('status', 'verified')->count(),
        'total_blocked' => Driver::where('status', 'blocked')->count(),
      ]
    ]);
  }

  public function chang_status(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:customers,id',
      'status' => 'required',

    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'type' => 'error', 'message' => $req->id]);
    }

    try {
      $done = Driver::find($req->id)->update(['status' => $req->status]);

      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'error to Change Driver Status']);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => 'Driver Status changed']);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
    }
  }

  public function edit($id)
  {
    $data = Driver::findOrFail($id);
    $data->img = $data->image ? url($data->image) : null;

    return response()->json($data);
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required',
      'username' => 'required|unique:drivers,username,' . ($req->id ?? 0),
      'email' => 'required|unique:drivers,email,' . ($req->id ?? 0),
      'phone' => 'required|unique:drivers,phone,' . ($req->id ?? 0),
      'phone_code' => 'required',
      'password' => 'required|same:confirm-password',
      'team' => 'nullable|exists:teams,id',
      'role' => 'nullable|exists:roles,id',
      'address' => 'required|string',
      'commission_type' => 'nullable|in:fixed,rate,subscription',
      'commission' => 'required_with:commission_type|min:0',
      'vehicle' => 'required|exists:vehicle_sizes,id'
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    DB::beginTransaction();
    try {
      $data = [
        'name'            => $req->name,
        'email'           => $req->email,
        'username'        => $req->usename,
        'phone'           => $req->phone,
        'phone_code'      => $req->phone_code,
        'role_id'         => $req->role ?? null,
        'team_id'    => $req->team ?? null,
        'vehicle_size_id' => $req->vehicle,
        'address' => $req->address,
        'commission_type' => $req->commission_type,
        'commission' => $req->commission,
      ];

      if ($req->filled('password')) {
        $data['password'] = Hash::make($req->password);
      }

      $oldImage = null;

      if ($req->filled('id')) {
        $find = Driver::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => 'Can not find the selected Driver']);
        }
        $oldImage = $find->image;

        if ($req->hasFile('image')) {
          $data['image'] = (new FunctionsController)->convert($req->image, 'customers');
        }

        $done = $find->update($data);

        if ($req->role) {
          $find->syncRoles($req->role);
        }
      } else {
        if ($req->hasFile('image')) {
          $data['image'] = (new FunctionsController)->convert($req->image, 'customers');
        }
        $done = Driver::create($data);

        if ($req->role) {
          $role = Role::find($req->role);
          if ($role) {
            $done->assignRole($role->name);
          }
        }
        $done = (new WalletsController)->store('driver', $done->id, true);
      }

      if (!$done) {
        DB::rollBack();
        if ($req->hasFile('image')) {
          unlink($data['image']);
        }
        return response()->json(['status' => 2, 'error' => 'Error: can not save the Driver']);
      } else {
        if ($oldImage && $req->hasFile('image')) {
          unlink($oldImage);
        }
      }

      DB::commit();
      return response()->json([
        'status'  => 1,
        'success' => 'Customer saved successfully',
      ]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {
      $find = Driver::findOrFail($req->id);
      if (!$find) {
        return response()->json(['status' => 2, 'error' => 'Can not find the selected Driver']);
      }

      $done = Driver::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Driver']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Driver deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
