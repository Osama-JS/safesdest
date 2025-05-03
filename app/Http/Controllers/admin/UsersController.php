<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\Form_Field;
use App\Models\mongo\mUsers;
use App\Models\Settings;
use App\Models\Teams;
use App\Models\User_Teams;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;


class UsersController extends Controller
{
  public function index()
  {
    $users = User::where('status', '!=', 'deleted')->get();
    $userCount = $users->count();
    $activeCount = $users->where('status', 'active')->count();
    $inactiveCount =  $users->where('status', 'inactive')->count();
    $pendingCount =  $users->where('status', 'pending')->count();

    $templates = Form_Template::all();
    $roles = Role::where('guard_name', 'web')->get();
    $teams_ids = User_Teams::select('team_id')->get();
    $teams = Teams::all();
    $user_template = Settings::where('key', 'user_template')->first();


    return view('admin.users.index', [
      'totalUser' => $userCount,
      'active' => $activeCount,
      'inactive' => $inactiveCount,
      'pending' => $pendingCount,
      'roles' => $roles,
      'teams' => $teams,
      'templates' => $templates,
      'user_template' => $user_template
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
      6 => 'reset',
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
        ->orWhere('phone', 'LIKE', "%{$search}%")
        ->offset($start)
        ->limit($limit)
        ->orderBy($order, $dir)
        ->get();

      $totalFiltered = User::where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%")
        ->orWhere('email', 'LIKE', "%{$search}%")
        ->orWhere('phone', 'LIKE', "%{$search}%")
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
        $nestedData['reset_password'] = $user->reset_password;


        $data[] = $nestedData;
      }
    }


    return response()->json([
      'draw' => intval($request->input('draw')),
      'recordsTotal' => intval($totalData),
      'recordsFiltered' => intval($totalFiltered),
      'code' => 200,
      'data' => $data,
      'summary' => [
        'total' => User::count(),
        'total_active' => User::where('status', 'active')->count(),
        'total_inactive' => User::where('status', 'inactive')->count(),
        'total_pending' => User::where('status', 'pending')->count(),
      ]
    ]);
  }

  public function chang_status(Request $req)
  {

    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:users,id',
      'status' => 'required',

    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'type' => 'error', 'message' => $req->id]);
    }

    try {
      $done = User::find($req->id)->update(['status' => $req->status]);

      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'error to Change user Status']);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => 'user Status changed']);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
    }
  }


  public function resetPass(Request $req)
  {
    $find = User::findOrFail($req->id);
    if (!$find) {
      return response()->json(['status' => 2, 'error' => __('User not found')]);
    }
    $status = $find->reset_password == 1 ? 0 : 1;
    $done = User::find($req->id)->update([
      'reset_password' => $status
    ]);
    if (!$done) {
      return response()->json(['status' => 2, 'error' => __('Error to change reset password  status')]);
    }
    return response()->json(['status' => 1, 'success' => $status]);
  }

  public function edit($id): JsonResponse
  {
    $data = User::findOrFail($id);
    $data->teamsIds = $data->teams()->pluck('team_id');
    $fields = Form_Field::where('form_template_id', $data->form_template_id)->get();

    $data->fields =  $fields;

    return response()->json($data);
  }


  public function store(Request $req)
  {

    $rules = [
      'name'        => 'required',
      'email'       => 'required|unique:users,email,' .  ($req->id ?? 0),
      'phone'       => 'required|unique:users,phone,' .  ($req->id ?? 0),
      'password'    => 'required_without:id|same:confirm-password',
      'role'        => 'required|exists:roles,id',
      'teams'       => 'nullable|array',
      'template'    => 'nullable|exists:form_templates,id'
    ];

    if ($req->filled('template')) {
      $fields = Form_Field::where('form_template_id', $req->template)->get();
      foreach ($fields as $key) {
        if ($key->required) {
          $rules['additional_fields.' . $key->name] = 'required';
        }
      }
    }


    $validator = Validator::make($req->all(), $rules);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }



    DB::beginTransaction();
    try {
      $data = [
        'name'                => $req->name,
        'email'               => $req->email,
        'phone'               => $req->phone,
        'phone_code'          => $req->phone_code,
        'role_id'             => $req->role,
        'form_template_id'    => $req->template ?? null,
        'additional_data_id'  => $additionalData->_id ?? null
      ];

      if ($req->filled('password')) {
        $data['password'] = Hash::make($req->password);
      }



      $structuredFields = [];

      if ($req->filled('template')) {
        $data['form_template_id'] = $req->template;

        $template = Form_Template::with('fields')->find($req->input('template'));

        foreach ($template->fields as $field) {
          $fieldName = $field->name;
          if ($req->has("additional_fields.$fieldName")) {
            $structuredFields[$fieldName] = [
              'label' => $field->label,
              'value' => $req->input("additional_fields.$fieldName"),
              'type'  => $field->type,
            ];
          }
        }
        $data['additional_data'] = $structuredFields;
      }


      if ($req->filled('id')) {
        $user = User::findOrFail($req->id);
        if (!$user) {
          return response()->json(['status' => 2, 'error' => 'Can not find the selected user']);
        }

        $done =  $user->update($data);

        $user->teams()->delete();
        $teams = collect($req->teams)->filter()->map(function ($teamId) {
          return ['team_id' => $teamId];
        })->toArray();
        $done = $user->teams()->createMany($teams);
      } else {
        $user = User::create($data);
        $teams = collect($req->teams)->filter()->map(function ($teamId) {
          return ['team_id' => $teamId];
        })->toArray();

        $done = $user->teams()->createMany($teams);
      }

      if (!$user) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error creating user']);
      }

      if (!$done) {
        DB::rollback();
        return response()->json(['status' => 2, 'error' => 'Error Save user']);
      }

      $role = Role::find($req->role);
      $user->assignRole($role->name);

      DB::commit();
      return response()->json(['status' => 1, 'success' => 'User saved']);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    if ($req->id === 1) {
      return response()->json(['status' => 2, 'error' => 'this User can not be deleted']);
    }
    try {
      $find = User::findOrFail($req->id);
      if (!$find) {
        return response()->json(['status' => 2, 'error' => 'Can not find the selected user']);
      }
      if ($find->teams->count() !== 0) {
        return response()->json(['status' => 2, 'error' => 'The selected User has teams to mange. you can not delete hem right now']);
      }

      $done = User::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete User']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('User deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
