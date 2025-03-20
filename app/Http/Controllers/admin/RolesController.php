<?php

namespace App\Http\Controllers\admin;

use Exception;
use Illuminate\Http\Request;
use App\Models\Permissions_Type;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class RolesController extends Controller
{
  public function index()
  {
    return view('admin.roles.index');
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'created_at',
    ];

    $search = [];

    $totalData = Role::count();

    $totalFiltered = $totalData;

    $limit = $request->input('length');
    $start = $request->input('start');
    $order = $columns[$request->input('order.0.column')];
    $dir = $request->input('order.0.dir');

    if (empty($request->input('guard'))) {
      $users = Role::offset($start)
        ->limit($limit)
        ->orderBy($order, $dir)
        ->get();
    } else {
      $search = $request->input('guard');

      $users = Role::where('guard_name', $search)
        ->offset($start)
        ->limit($limit)
        ->orderBy($order, $dir)
        ->get();

      $totalFiltered = Role::where('guard_name', $search)->count();
    }

    $data = [];

    if (!empty($users)) {
      // providing a dummy id instead of database ids
      $ids = $start;

      foreach ($users as $user) {
        $nestedData['id'] = $user->id;
        $nestedData['fake_id'] = ++$ids;
        $nestedData['name'] = $user->name;
        $nestedData['guard'] = $user->guard_name;
        $nestedData['created_at'] = $user->created_at;

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

  public function getPermissions(Request $request, $guard): JsonResponse
  {
    $permissions = Permissions_Type::where('guard_name', $guard)->with('permissions')->get();
    $rolePermissions = [];
    if ($request->has('role_id')) {
      $role = Role::find($request->role_id);
      if ($role) {
        $rolePermissions = $role->permissions()->pluck('id')->toArray();
      }
    }
    return response()->json([
      'permissions' => $permissions,
      'rolePermissions' => $rolePermissions
    ]);
  }

  public function store(Request $req)
  {

    $validator = Validator::make($req->all(), [
      'name' => 'required|unique:roles,name',
      'guard' => 'required|in:web,driver,customer',
      'permissions' => 'required|array',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    DB::beginTransaction();
    try {
      $role = Role::create([
        'name' => $req->name,
        'guard_name' => $req->guard,
      ]);
      if (!$role) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to create Role']);
      }
      $permissions = Permission::whereIn('id', $req->permissions)
        ->where('guard_name', $req->guard)
        ->pluck('name')
        ->toArray();
      $role->syncPermissions($permissions);
      DB::commit();
      return response()->json(['status' => 1, 'success' => 'Role Created']);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function update(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:roles,id|not_in:1',
      'name' => 'required|unique:roles,name,' . $req->id,
      'guard' => 'required|in:web,driver,customer',
      'permissions' => 'required|array',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    DB::beginTransaction();
    try {
      $role = Role::findOrFail($req->id);

      $role->update([
        'name' => $req->name,
        'guard_name' => $req->guard,
      ]);

      $permissions = Permission::whereIn('id', $req->permissions)
        ->where('guard_name', $req->guard)
        ->pluck('name')
        ->toArray();

      $role->syncPermissions($permissions);

      DB::commit();
      return response()->json(['status' => 1, 'success' => 'Role updated ']);
    } catch (\Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    if ($req->id === 1) {
      return response()->json(['status' => 2, 'error' => 'this role can not be deleted']);
    }
    try {
      $users = User::where('role_id', $req->id)->count();
      if ($users !== 0) {
        return response()->json(['status' => 2, 'error' => 'there are users connected with this role']);
      }
      $done = Role::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete role']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('role deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
