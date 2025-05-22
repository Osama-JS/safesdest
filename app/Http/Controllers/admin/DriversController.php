<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\Teams;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Form_Field;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FunctionsController;
use App\Models\Settings;
use App\Helpers\FileHelper;
use App\Models\Task;

class DriversController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:view_drivers', ['only' => ['index', 'getData', 'edit']]);
    $this->middleware('permission:save_drivers', ['only' => ['store']]);
    $this->middleware('permission:status_drivers', ['only' => ['chang_status']]);
    $this->middleware('permission:delete_drivers', ['only' => ['destroy']]);
    $this->middleware('permission:profile_drivers', ['only' => ['show', 'getCustomerTasks']]);
    $this->middleware('permission:wallet_drivers', ['only' => ['']]);
    $this->middleware('permission:manage_wallet_drivers', ['only' => ['']]);
  }


  public function index()
  {
    $templates = Form_Template::all();
    $teams = Teams::all();
    $roles = Role::where('guard_name', 'driver')->get();
    $vehicles = Vehicle::all();
    $driver_template = Settings::where('key', 'driver_template')->first();

    return view('admin.drivers.index', compact('templates', 'teams', 'roles', 'vehicles', 'driver_template'));
  }

  public function getDrivers(Request $request)
  {
    $search = $request->input('q');

    $drivers = Driver::query();
    // if ($search) {
    //   $drivers->where('team_id', $search);
    // }

    $drivers->select('id', 'name')
      ->limit(20)
      ->get();

    return response()->json(['results' => $drivers]);
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
        'tags'       => $val->tags->pluck('tag.name')->implode(', '),
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
        'total_pending' => Driver::where('status', 'pending')->count(),
        'total_blocked' => Driver::where('status', 'blocked')->count(),
      ]
    ]);
  }

  public function chang_status(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:drivers,id',
      'status' => 'required',
    ], [
      'id.required' => __('The driver id is required.'),
      'id.exists' => __('The selected driver does not exist.'),
      'status.required' => __('The status field is required.'),
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'type' => 'error', 'message' => $validator->errors()]);
    }

    try {
      $user = auth()->user();
      if (!$user || !$user->checkDriver($req->id)) {
        return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
      }
      $done = Driver::find($req->id)->update(['status' => $req->status]);

      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => __('Error to Change Driver Status')]);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => __('Driver Status changed')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
    }
  }

  public function edit($id)
  {
    $data = Driver::findOrFail($id);
    $data->img = $data->image ? url($data->image) : null;
    $data->vehicle_type = $data->vehicle_size->vehicle_type_id;
    $data->vehicle = $data->vehicle_size->type->vehicle_id;
    $fields = Form_Field::where('form_template_id', $data->form_template_id)->get();

    $data->fields =  $fields;

    return response()->json($data);
  }

  public function store(Request $req)
  {
    $rules = [
      'name'            => 'required|string|max:255',
      'email'           => 'required|email|unique:drivers,email,' . ($req->id ?? 0),
      'phone'           => 'required|unique:drivers,phone,' . ($req->id ?? 0),
      'phone_code'      => 'required|string',
      'username'        => 'required|unique:drivers,username,' . ($req->id ?? 0),
      'password'        => 'nullable|same:confirm-password',
      'address'         => 'required|string|max:255',
      'vehicle'         => 'required|exists:vehicle_sizes,id',
      'role'            => 'nullable|exists:roles,id',
      'team'            => 'nullable|exists:teams,id',
      'commission_type' => 'nullable|in:fixed,rate,subscription',
      'commission'      => 'required_with:commission_type|min:0',
      'image'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ];

    if ($req->filled('template')) {
      $fields = Form_Field::where('form_template_id', $req->template)->get();

      foreach ($fields as $field) {
        $fieldKey = 'additional_fields.' . $field->name;
        $rules[$fieldKey] = [];

        if (!$req->filled('id') && $field->required) {
          $rules[$fieldKey][] = 'required';
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

    $validator = Validator::make($req->all(), $rules);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }

    DB::beginTransaction();
    $filesToDelete = [];

    try {
      $data = [
        'name'            => $req->name,
        'email'           => $req->email,
        'phone'           => $req->phone,
        'phone_code'      => $req->phone_code,
        'username'        => $req->username,
        'address'         => $req->address,
        'vehicle_size_id' => $req->vehicle,
        'role_id'         => $req->role ?? null,
        'team_id'         => $req->team ?? null,
        'commission_type' => $req->commission_type,
        'commission'      => $req->commission,
      ];

      if ($req->filled('password')) {
        $data['password'] = Hash::make($req->password);
      }

      $structuredFields = [];
      $oldAdditionalData = [];

      if ($req->filled('id')) {
        $existing = Driver::find($req->id);
        if ($existing) {
          $oldAdditionalData = $existing->additional_data ?? [];

          if ($existing->form_template_id && $existing->form_template_id != $req->template) {
            foreach ($oldAdditionalData as $field) {
              if (in_array($field['type'], ['file', 'image'])) {
                $filesToDelete[] = $field['value'];
              }
            }
          }
        }
      }

      if ($req->filled('template')) {
        $data['form_template_id'] = $req->template;
        $template = Form_Template::with('fields')->find($req->input('template'));

        foreach ($template->fields as $field) {
          $fieldName = $field->name;
          $fieldType = $field->type;

          if (in_array($fieldType, ['file', 'image'])) {
            if ($req->hasFile("additional_fields.$fieldName")) {
              if (isset($oldAdditionalData[$fieldName]['value'])) {
                $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
              }

              $path = FileHelper::uploadFile($req->file("additional_fields.$fieldName"), 'drivers/files');

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

      $oldImage = null;

      if ($req->filled('id')) {
        $find = Driver::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Driver')]);
        }
        $user = auth()->user();
        if (!$user || !$user->checkDriver($find->id)) {
          return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
        }

        $oldImage = $find->image;

        if ($req->hasFile('image')) {
          $data['image'] = (new FunctionsController)->convert($req->image, 'drivers');
        }

        $done = $find->update($data);

        if ($req->role) {
          $find->syncRoles($req->role);
        }
      } else {
        if ($req->hasFile('image')) {
          $data['image'] = (new FunctionsController)->convert($req->image, 'drivers');
        }

        $done = Driver::create($data);

        if ($req->role) {
          $role = Role::find($req->role);
          if ($role) {
            $done->assignRole($role->name);
          }
        }

        (new WalletsController)->store('driver', $done->id, true);
      }

      if (!$done) {
        DB::rollBack();
        if ($req->hasFile('image')) {
          unlink($data['image']);
        }
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Driver')]);
      }

      DB::commit();

      foreach ($filesToDelete as $file) {
        FileHelper::deleteFileIfExists($file);
      }

      if ($oldImage && $req->hasFile('image')) {
        unlink($oldImage);
      }

      return response()->json([
        'status'  => 1,
        'success' => __('Driver saved successfully'),
      ]);
    } catch (\Exception $ex) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error'  => $ex->getMessage()
      ]);
    }
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {
      $find = Driver::findOrFail($req->id);
      if (!$find) {
        return response()->json(['status' => 2, 'error' => __('Can not find the selected Driver')]);
      }
      $user = auth()->user();
      if (!$user || !$user->checkDriver($find->id)) {
        return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
      }

      $done = Driver::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('Error to delete Driver')]);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Driver deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function show(Request $req)
  {
    $data = Driver::findOrFail($req->id);
    return view('admin.drivers.show', compact('data'));
  }

  public function getCustomerTasks(Request $request)
  {
    $columns = [
      2 => 'task_id',
      3 => 'status',
      4 => 'price',
      8 => 'created_at'
    ];

    $totalData = Task::where('driver_id', $request->driver)->count();
    $totalFiltered = $totalData;

    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';

    $search = $request->input('search');
    $statusFilter = $request->input('status');

    $query = Task::where('driver_id', $request->driver);

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%")
          ->orWhere('id', 'LIKE', "%{$search}%");
      });
    }
    if (!empty($statusFilter)) {
      $query->where('status', $statusFilter);
    }

    $totalFiltered = $query->count();


    $items = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    foreach ($items as $item) {
      $data[] = [
        'task_id'    => $item->id,
        'status'     => $item->status,
        'price'       => $item->name,
        'created_at' => $item->created_at->format('Y-m-d H:i'),
      ];
    }

    return response()->json([
      'draw'            => intval($request->input('draw')),
      'recordsTotal'    => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code'            => 200,
      'data'            => $data,
    ]);
  }
}
