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
      5 => 'tags',
      6 => 'status',
      7 => 'created_at'
    ];

    $search = [];

    $totalData = Driver::count();

    $totalFiltered = $totalData;

    $limit = $request->input('length');
    $start = $request->input('start');
    $order = $columns[$request->input('order.0.column')];
    $dir = $request->input('order.0.dir');

    if (empty($request->input('search.value'))) {
      $drivers = Driver::offset($start)
        ->limit($limit)
        ->orderBy($order, $dir)
        ->get();
    } else {
      $search = $request->input('search.value');

      $drivers = Driver::where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%")
        ->orWhere('email', 'LIKE', "%{$search}%")
        ->orWhere('phone', 'LIKE', "%{$search}%")
        ->orWhere('username', 'LIKE', "%{$search}%")
        ->offset($start)
        ->limit($limit)
        ->orderBy($order, $dir)
        ->get();

      $totalFiltered = Driver::where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%")
        ->orWhere('email', 'LIKE', "%{$search}%")
        ->orWhere('phone', 'LIKE', "%{$search}%")
        ->orWhere('username', 'LIKE', "%{$search}%")
        ->count();
    }

    $data = [];

    if (!empty($drivers)) {
      $ids = $start;

      foreach ($drivers as $user) {
        $nestedData['id'] = $user->id;
        $nestedData['fake_id'] = ++$ids;
        $nestedData['name'] = $user->name;
        $nestedData['username'] = $user->username;
        $nestedData['email'] = $user->email;
        $nestedData['phone'] = $user->phone_code . $user->phone;
        $nestedData['tags'] = $user->phone_code . $user->phone;
        $nestedData['created_at'] = $user->created_at;
        $nestedData['status'] = $user->status;

        $data[] = $nestedData;
      }
    }

    if ($data) {
      return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => intval($totalData),
        'recordsFiltered' => intval($totalFiltered),
        'code' => 200,
        'data' => $data,
      ]);
    } else {
      return response()->json([
        'message' => 'Internal Server Error',
        'code' => 500,
        'data' => [],
      ]);
    }
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required',
      'username' => 'required|unique:drivers,username',
      'email' => 'required|unique:drivers,email',
      'phone' => 'required|unique:drivers,phone',
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
      $password = Hash::make($req->password);




      $user = Driver::create([
        'name' => $req->name,
        'username' => $req->username,
        'status' => 'pending',
        'email' => $req->email,
        'phone' => $req->phone,
        'phone_code' => $req->phone_code,
        'password' => $password,
        'role_id' => 1,
        'team_id' => $req->team,
        'vehicle_size_id' => $req->vehicle,
        'address' => $req->address,
        'commission_type' =>   $req->commission_type,
        'commission' =>  $req->commission,

      ]);

      if (!$user) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error creating driver']);
      }

      $role = Role::find($req->role);
      if ($role) {
        $user->assignRole($role->name);
      }

      DB::commit();
      return response()->json(['status' => 1, 'success' => 'driver created']);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
