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
use App\Helpers\FileHelper;
use App\Models\Task;

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
      'name'       => 'required|string|max:255',
      'email'      => 'required|email|unique:customers,email,' . ($req->id ?? 0),
      'phone'      => 'required|unique:customers,phone,' . ($req->id ?? 0),
      'phone_code' => 'required|string',
      'password'   => 'nullable|same:confirm-password',
      'role'       => 'nullable|exists:roles,id',
      'image'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
      'c_name'     => 'nullable|string|max:255',
      'c_address'  => 'nullable|string|max:255',
      'tags'       => 'nullable|array',
    ];

    if ($req->filled('template')) {
      $fields = Form_Field::where('form_template_id', $req->template)->get();

      foreach ($fields as $field) {
        $fieldKey = 'additional_fields.' . $field->name;
        $rules[$fieldKey] = [];

        // إذا لم تكن العملية تعديل أو الحقل مطلوب فعليًا
        if (!$req->filled('id') && $field->required) {
          $rules[$fieldKey][] = 'required';
        }

        // إضافة قواعد بناءً على نوع الحقل
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
            $rules[$fieldKey][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif'; // أنواع موثوقة
            $rules[$fieldKey][] = 'max:10240'; // 10MB
            break;

          case 'image':
            $rules[$fieldKey][] = 'image';
            $rules[$fieldKey][] = 'mimes:jpeg,png,jpg,webp,gif';
            $rules[$fieldKey][] = 'max:5120'; // 5MB
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
    $filesToDelete = []; // ❗ قائمة بالملفات التي ستحذف بعد نجاح المعاملة

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
      $oldAdditionalData = [];

      if ($req->filled('id')) {
        $existing = Customer::find($req->id);
        if ($existing) {
          $oldAdditionalData = $existing->additional_data ?? [];

          // حذف ملفات النموذج السابق إن تغيّر النموذج
          if ($existing->form_template_id && $existing->form_template_id != $req->template) {
            foreach ($oldAdditionalData as $field) {
              if (in_array($field['type'], ['file', 'image'])) {
                $filesToDelete[] = $field['value']; // حذف لاحق بعد commit
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
                $filesToDelete[] = $oldAdditionalData[$fieldName]['value']; // حذف لاحقًا
              }

              $path = FileHelper::uploadFile($req->file("additional_fields.$fieldName"), 'customers/files');

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
          $tags = collect($req->tags)->filter()->map(fn($id) => ['tag_id' => $id])->toArray();
          $find->tags()->createMany($tags);
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

        $tags = collect($req->tags)->filter()->map(fn($tagId) => ['tag_id' => $tagId])->toArray();
        $done->tags()->createMany($tags);

        (new WalletsController)->store('customer', $done->id, true);
      }

      if (!$done) {
        DB::rollBack();
        if ($req->hasFile('image')) {
          unlink($data['image']);
        }
        return response()->json(['status' => 2, 'error' => 'Error: can not save the Customer']);
      }

      DB::commit();

      // 🧹 حذف الملفات بعد نجاح التخزين
      foreach ($filesToDelete as $file) {
        FileHelper::deleteFileIfExists($file);
      }

      if ($oldImage && $req->hasFile('image')) {
        unlink($oldImage);
      }

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


  public function show(Request $req)
  {
    $data = Customer::findOrFail($req->id);
    return view('admin.customers.show', compact('data'));
  }

  public function getCustomerTasks(Request $request)
  {
    $columns = [
      2 => 'task_id',
      3 => 'status',
      4 => 'price',
      8 => 'created_at'
    ];

    $totalData = Task::where('customer_id', $request->customer)->count();
    $totalFiltered = $totalData;

    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';

    $search = $request->input('search');
    $statusFilter = $request->input('status');

    $query = Task::where('customer_id', $request->customer);

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
