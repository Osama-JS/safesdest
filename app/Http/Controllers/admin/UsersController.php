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
use App\Models\Customer;
use App\Models\Form_Field;
use App\Models\mongo\mUsers;
use App\Models\Settings;
use App\Models\Teams;
use App\Models\User_Teams;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Helpers\FileHelper;

class UsersController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:view_admins', ['only' => ['index', 'getData', 'edit']]);
    $this->middleware('permission:save_admins', ['only' => ['store']]);
    $this->middleware('permission:status_admins', ['only' => ['chang_status', 'resetPass']]);
    $this->middleware('permission:delete_admins', ['only' => ['destroy']]);
  }

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
    $customers = Customer::where('status', 'active')->get();
    $user_template = Settings::where('key', 'user_template')->first();

    return view('admin.users.index', [
      'totalUser' => $userCount,
      'active' => $activeCount,
      'inactive' => $inactiveCount,
      'pending' => $pendingCount,
      'roles' => $roles,
      'teams' => $teams,
      'templates' => $templates,
      'user_template' => $user_template,
      'customers' => $customers
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
        'edit_permission' => auth()->user()->can('save_admins'),
        'delete_permission' => auth()->user()->can('delete_admins'),
      ]
    ]);
  }

  public function chang_status(Request $req)
  {

    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:users,id',
      'status' => 'required',
    ], [
      'id.required' => __('The user id is required.'),
      'id.exists' => __('The selected user does not exist.'),
      'status.required' => __('The status field is required.'),
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'type' => 'error', 'message' => $validator->errors()]);
    }

    try {
      $done = User::find($req->id)->update(['status' => $req->status]);

      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => __('Error to Change user Status')]);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => __('User Status changed')]);
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
    $data->customersIds = $data->customers()->pluck('customer_id');
    $fields = Form_Field::where('form_template_id', $data->form_template_id)->get();

    $data->fields =  $fields;

    return response()->json($data);
  }

  public function store(Request $req)
  {
    $rules = [
      'name'        => 'required',
      'email'       => 'required|unique:users,email,' . ($req->id ?? 0),
      'phone'       => 'required|unique:users,phone,' . ($req->id ?? 0),
      'password'    => 'required_without:id|same:confirm-password',
      'role'        => 'required|exists:roles,id',
      'teams'       => 'nullable|array',
      'customers'   => 'nullable|array',
      'template'    => 'nullable|exists:form_templates,id'
    ];

    $messages = [
      'name.required' => __('The name field is required.'),
      'email.required' => __('The email field is required.'),
      'email.unique' => __('The email has already been taken.'),
      'phone.required' => __('The phone field is required.'),
      'phone.unique' => __('The phone has already been taken.'),
      'password.required_without' => __('The password field is required.'),
      'password.same' => __('The password and confirmation must match.'),
      'role.required' => __('The user role is required.'),
      'role.exists' => __('The selected role is invalid.'),
      'teams.array' => __('Teams must be an array.'),
      'customers.array' => __('Customers must be an array.'),
      'template.exists' => __('The selected template is invalid.'),
    ];

    if ($req->filled('template')) {
      $fields = Form_Field::where('form_template_id', $req->template)->get();
      foreach ($fields as $field) {
        $fieldKey = 'additional_fields.' . $field->name;
        $rules[$fieldKey] = [];

        if (!$req->filled('id') && $field->required) {
          $rules[$fieldKey][] = 'required';
          $messages["$fieldKey.required"] = __('The :label field is required.', ['label' => $field->label]);
        }

        switch ($field->type) {
          case 'text':
            $rules[$fieldKey][] = 'string';
            break;
          case 'number':
            $rules[$fieldKey][] = 'numeric';
            break;
          case 'date':
            $rules[$fieldKey][] = 'date';
            break;
          case 'file':
            $rules[$fieldKey][] = 'file';
            $rules[$fieldKey][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif';
            $rules[$fieldKey][] = 'max:10240';
            break;
          case 'image':
            $rules[$fieldKey][] = 'image';
            $rules[$fieldKey][] = 'mimes:jpeg,png,jpg,webp,gif';
            $rules[$fieldKey][] = 'max:5120';
            break;
          default:
            $rules[$fieldKey][] = 'string';
            break;
        }
      }
    }

    $validator = Validator::make($req->all(), $rules, $messages);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()]);
    }

    DB::beginTransaction();
    $filesToDelete = [];

    try {
      $data = [
        'name'       => $req->name,
        'email'      => $req->email,
        'phone'      => $req->phone,
        'phone_code' => $req->phone_code,
        'role_id'    => $req->role,
      ];

      if ($req->filled('password')) {
        $data['password'] = Hash::make($req->password);
      }

      $structuredFields = [];
      $oldAdditionalData = [];

      if ($req->filled('id')) {
        $user = User::findOrFail($req->id);
        if (!$user) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected user')]);
        }

        $oldAdditionalData = $user->additional_data ?? [];

        if ($user->form_template_id && $user->form_template_id != $req->template) {
          foreach ($oldAdditionalData as $field) {
            if (in_array($field['type'], ['file', 'image'])) {
              $filesToDelete[] = $field['value'];
            }
          }
        }
      }

      if ($req->filled('template')) {
        $data['form_template_id'] = $req->template;
        $template = Form_Template::with('fields')->find($req->template);

        foreach ($template->fields as $field) {
          $fieldName = $field->name;
          $fieldType = $field->type;

          if (in_array($fieldType, ['file', 'image'])) {
            if ($req->hasFile("additional_fields.$fieldName")) {
              if (isset($oldAdditionalData[$fieldName]['value'])) {
                $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
              }

              $path = FileHelper::uploadFile($req->file("additional_fields.$fieldName"), 'users/files');

              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $path,
                'type'  => $fieldType,
              ];
            } elseif (isset($oldAdditionalData[$fieldName])) {
              $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
            }
          } else {
            if ($req->has("additional_fields.$fieldName")) {
              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $req->input("additional_fields.$fieldName"),
                'type'  => $fieldType,
              ];
            }
          }
        }

        $data['additional_data'] = $structuredFields;
      }

      if ($req->filled('id')) {
        $user->update($data);
        $user->teams()->delete();
        $user->customers()->sync($req->customers ?? []);
      } else {
        $user = User::create($data);
        $user->customers()->sync($req->customers ?? []);
      }

      $teams = collect($req->teams)->filter()->map(fn($teamId) => ['team_id' => $teamId])->toArray();
      $user->teams()->createMany($teams);

      $role = Role::find($req->role);
      $user->syncRoles([$role->name]);

      DB::commit();

      foreach ($filesToDelete as $file) {
        FileHelper::deleteFileIfExists($file);
      }

      return response()->json(['status' => 1, 'success' => __('User saved successfully')]);
    } catch (\Exception $ex) {
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
        return response()->json(['status' => 2, 'error' => __('Can not find the selected user')]);
      }
      if ($find->teams->count() !== 0) {
        return response()->json(['status' => 2, 'error' => __('The selected User has teams to mange. you can not delete hem right now')]);
      }

      $done = User::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('Error to delete User')]);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('User deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
