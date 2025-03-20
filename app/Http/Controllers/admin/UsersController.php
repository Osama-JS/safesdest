<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
  public function index()
  {
    $users = User::where('status', '!=', 'deleted')->get();
    $userCount = $users->count();
    $activeCount = $users->where('status', 'active')->count();
    $inactiveCount =  $users->where('status', 'inactive')->count();
    $pendingCount =  $users->where('status', 'pending')->count();

    $roles = Role::where('guard_name', 'web')->get();
    return view('admin.users.index', [
      'totalUser' => $userCount,
      'active' => $activeCount,
      'inactive' => $inactiveCount,
      'pending' => $pendingCount,
      'roles' => $roles
    ]);
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'email',
      4 => 'phone',
      5 => 'role',
      6 => 'status',
    ];

    $search = [];

    $totalData = User::count();

    $totalFiltered = $totalData;

    $limit = $request->input('length');
    $start = $request->input('start');
    $order = $columns[$request->input('order.0.column')];
    $dir = $request->input('order.0.dir');

    if (empty($request->input('search.value'))) {
      $users = User::offset($start)
        ->limit($limit)
        ->orderBy($order, $dir)
        ->get();
    } else {
      $search = $request->input('search.value');

      $users = User::where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%")
        ->orWhere('email', 'LIKE', "%{$search}%")
        ->offset($start)
        ->limit($limit)
        ->orderBy($order, $dir)
        ->get();

      $totalFiltered = User::where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%")
        ->orWhere('email', 'LIKE', "%{$search}%")
        ->count();
    }

    $data = [];

    if (!empty($users)) {
      // providing a dummy id instead of database ids
      $ids = $start;

      foreach ($users as $user) {
        $nestedData['id'] = $user->id;
        $nestedData['fake_id'] = ++$ids;
        $nestedData['name'] = $user->name;
        $nestedData['email'] = $user->email;
        $nestedData['phone'] = $user->phone_code . $user->phone;
        $nestedData['role'] = $user->role->name;
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
      'email' => 'required|unique:users,email',
      'phone' => 'required|unique:users,phone',
      'password' => 'required|same:confirm-password',
      'role' => 'required|exists:roles,id'
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    DB::beginTransaction();
    try {
      $password = Hash::make($req->password);
      $user = User::create([
        'name' => $req->name,
        'email' => $req->email,
        'phone' =>   $req->phone,
        'phone_code' =>  $req->phone_code,
        'password' => $password,
        'role_id' =>  $req->role
      ]);
      if (!$user) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'error to create user']);
      }
      $roles = Role::find($req->role);
      $user->assignRole($roles->name);

      DB::commit();
      return response()->json(['status' => 1, 'success' => 'user created']);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
