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

  public function __construct()
  {
    $this->middleware('permission:view_roles', ['only' => ['index', 'getData']]);
    $this->middleware('permission:save_roles', ['only' => ['store']]);
    $this->middleware('permission:delete_roles', ['only' => ['destroy']]);
  }

  public function index()
  {
    return view('admin.roles.index');
  }

  public function getData(Request $request)
  {
    $columns = ['id', 'name', 'created_at'];

    $totalData = Role::count();
    $start = $request->input('start');
    $limit = $request->input('length');
    $order = $columns[$request->input('order.0.column', 0)] ?? 'id';
    $dir = $request->input('order.0.dir', 'asc');

    $type = $request->input('type');

    $query = Role::query();

    if (!empty($type)) {

      $query->where('guard_name', $type);
    }



    $totalFiltered = $query->count();

    $users = $query->offset($start)->limit($limit)->orderBy($order, $dir)->get();

    $data = $users->map(function ($user, $index) use ($start) {
      return [
        'id' => $user->id,
        'fake_id' => $start + $index + 1,
        'name' => $user->name,
        'guard' => $user->guard_name,
        'users' => User::where('role_id', $user->id)->count(),
        'created_at' => $user->created_at,
      ];
    });

    return response()->json([
      'draw' => intval($request->input('draw')),
      'recordsTotal' => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code' => 200,
      'data' => $data,
    ]);
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
      'name'         => 'required|unique:roles,name,' .  ($req->id ?? 0),
      'guard'        => 'required|in:web,driver,customer',
      'permissions'  => 'required|array',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }

    DB::beginTransaction();
    try {

      $data = [
        'name' => $req->name,
        'guard_name' => $req->guard,
      ];

      if ($req->filled('id')) {
        $role = Role::findOrFail($req->id);

        $role->update([$data]);
      } else {
        $role = Role::create($data);
      }
      if (!$role) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to Save Role']);
      }
      $permissions = Permission::whereIn('id', $req->permissions)
        ->where('guard_name', $req->guard)
        ->pluck('name')
        ->toArray();
      $role->syncPermissions($permissions);
      DB::commit();
      return response()->json(['status' => 1, 'success' => 'Role Saved']);
    } catch (Exception $ex) {
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
