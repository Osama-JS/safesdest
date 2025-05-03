<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use App\Models\Form_Field;
use App\Models\Settings;
use App\Models\Tag;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomersController extends Controller
{
  public function index()
  {
    $templates = Form_Template::all();
    $tags = Tag::all();
    $roles = Role::where('guard_name', 'customer')->get();
    $customer_template = Settings::where('key', 'customer_template')->first();

    return view('admin.customers.index', compact('templates', 'roles', 'tags', 'customer_template'));
  }


  public function getCustomers()
  {
    $data = Customer::select('id', 'name')->get();
    return response()->json($data);
  }


  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'email',
      4 => 'phone',
      5 => 'role',
      6 => 'tags',       // تم الاحتفاظ بـ 'tags' فقط هنا
      7 => 'status',
      8 => 'created_at'
    ];

    $totalData = Customer::count();
    $totalFiltered = $totalData;

    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';

    $search = $request->input('search');
    $statusFilter = $request->input('status');

    $query = Customer::query();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%")
          ->orWhere('name', 'LIKE', "%{$search}%")
          ->orWhere('email', 'LIKE', "%{$search}%")
          ->orWhere('phone', 'LIKE', "%{$search}%");
      });
    }
    if (!empty($statusFilter)) {
      $query->where('status', $statusFilter);
    }

    $totalFiltered = $query->count();


    $customers = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    $fakeId = $start;

    foreach ($customers as $customer) {
      $data[] = [
        'id'         => $customer->id,
        'fake_id'    => ++$fakeId,
        'name'       => $customer->name,
        'email'      => $customer->email,
        'phone'      => $customer->phone_code . $customer->phone,
        'image'      => $customer->image ? url($customer->image) : null,
        'tags'       => $customer->tags->pluck('tag.name')->implode(', '),
        'role'       => $customer->role->name ?? "",
        'created_at' => $customer->created_at->format('Y-m-d H:i'),
        'status'     => $customer->status,
      ];
    }

    return response()->json([
      'draw'            => intval($request->input('draw')),
      'recordsTotal'    => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code'            => 200,
      'data'            => $data,
      'summary' => [
        'total' => Customer::count(),
        'total_active' => Customer::where('status', 'active')->count(),
        'total_verified' => Customer::where('status', 'verified')->count(),
        'total_blocked' => Customer::where('status', 'blocked')->count(),
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
      $done = Customer::find($req->id)->update(['status' => $req->status]);

      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'error to Change Customer Status']);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => 'Customer Status changed']);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
    }
  }


  public function edit($id)
  {
    $data = Customer::findOrFail($id);
    $data->img = $data->image ? url($data->image) : null;
    $data->tagsIds = $data->tags()->pluck('tag_id');
    $fields = Form_Field::where('form_template_id', $data->form_template_id)->get();

    $data->fields =  $fields;

    return response()->json($data);
  }

  public function store(Request $req)
  {
    $rules = [
      'name'           => 'required|string|max:255',
      'email'          => 'required|email|unique:customers,email,' . ($req->id ?? 0),
      'phone'          => 'required|unique:customers,phone,' . ($req->id ?? 0),
      'phone_code'     => 'required|string',
      'password'       => 'nullable|same:confirm-password',
      'role'           => 'nullable|exists:roles,id',
      'image'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
      'c_name'         => 'nullable|string|max:255',
      'c_address'      => 'nullable|string|max:255',
      'tags'           => 'nullable|array',
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
        'name'            => $req->name,
        'email'           => $req->email,
        'phone'           => $req->phone,
        'phone_code'      => $req->phone_code,
        'role_id'         => $req->role ?? null,
        'company_name'    => $req->c_name,
        'company_address' => $req->c_address,
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

      $oldImage = null;

      if ($req->filled('id')) {
        $find = Customer::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => 'Can not find the selected Customer']);
        }
        $oldImage = $find->image;

        if ($req->hasFile('image')) {
          $data['image'] = (new FunctionsController)->convert($req->image, 'customers');
        }

        $done = $find->update($data);

        if ($req->role) {
          $find->syncRoles($req->role);
        }

        if ($req->filled('tags')) {
          $find->tags()->delete();
          $tags = [];
          for ($i = 0; $i < count($req->tags); $i++) {
            if (isset($req->tags[$i])) {
              $tags[$i]['tag_id'] = $req->tags[$i];
            }
          }
          $done = $find->tags()->createMany($tags);
        }
      } else {

        if ($req->hasFile('image')) {
          $data['image'] = (new FunctionsController)->convert($req->image, 'customers');
        }

        $done = Customer::create($data);

        if ($req->role) {
          $role = Role::find($req->role);
          if ($role) {
            $done->assignRole($role->name);
          }
        }
        $tags = collect($req->tags)->filter()->map(function ($tagId) {
          return ['tag_id' => $tagId];
        })->toArray();
        $done->tags()->createMany($tags);

        $done = (new WalletsController)->store('customer', $done->id, true);
      }

      if (!$done) {
        DB::rollBack();
        if ($req->hasFile('image')) {
          unlink($data['image']);
        }
        return response()->json(['status' => 2, 'error' => 'Error: can not save the Customer']);
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
      $find = Customer::findOrFail($req->id);
      if (!$find) {
        return response()->json(['status' => 2, 'error' => 'Can not find the selected Customer']);
      }

      $done = Customer::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Customer']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Customer deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
