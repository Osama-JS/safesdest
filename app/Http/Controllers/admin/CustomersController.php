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
use App\Models\Wallet;

class CustomersController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:view_customers', ['only' => ['index', 'getData', 'edit']]);
    $this->middleware('permission:save_customers', ['only' => ['store']]);
    $this->middleware('permission:status_customers', ['only' => ['chang_status']]);
    $this->middleware('permission:delete_customers', ['only' => ['destroy']]);
    $this->middleware('permission:profile_customers', ['only' => ['show', 'getCustomerTasks']]);
    $this->middleware('permission:wallet_customers', ['only' => ['']]);
    $this->middleware('permission:mange_wallet_customers', ['only' => ['']]);
    $this->middleware('permission:task_customers', ['only' => ['']]);
  }

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
      6 => 'tags',
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
        'broker'     => $customer->is_customs_clearance_agent,
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
    ], [
      'id.required' => __('The customer id is required.'),
      'id.exists' => __('The selected customer does not exist.'),
      'status.required' => __('The status field is required.'),
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'type' => 'error', 'message' => $validator->errors()]);
    }

    try {
      $user = auth()->user();
      if (!$user || !$user->checkCustomer($req->id)) {
        return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
      }
      $done = Customer::find($req->id)->update(['status' => $req->status]);

      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => __('Error to Change Customer Status')]);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => __('Customer Status changed')]);
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

  public function createWallet(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:customers,id',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'type' => 'error', 'message' => $req->id]);
    }

    try {
      $find = Customer::find($req->id);
      if ($find->status != 'active') {
        return response()->json(['status' => 2, 'type' => 'error', 'message' => 'Customer is not active']);
      }
      if ($find->wallet) {
        return response()->json(['status' => 2, 'type' => 'error', 'message' => 'Wallet already exists']);
      }
      $wallet = Wallet::where('user_type', 'customer')->where('customer_id', $req->id)->first();
      if ($wallet) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'this wallet already exist']);
      }
      $done = (new WalletsController)->store('customer', $req->id, true);



      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'error to create Wallet']);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => 'Wallet created successfully']);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
    }
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

        // لا نضع required للحقول المركبة هنا
        if (!$req->filled('id') && $field->required && !in_array($field->type, ['file_expiration_date', 'file_with_text'])) {
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
          case 'url':
            $rules[$fieldKey][] = 'url';
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

          case 'file_expiration_date':
            // إزالة القاعدة العامة للحقل الأساسي
            unset($rules[$fieldKey]);

            // قواعد الملف
            $rules[$fieldKey . '_file'] = [];
            $rules[$fieldKey . '_file'][] = 'file';
            $rules[$fieldKey . '_file'][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif';
            $rules[$fieldKey . '_file'][] = 'max:10240';

            // قواعد تاريخ الانتهاء
            $rules[$fieldKey . '_expiration'] = [];
            $rules[$fieldKey . '_expiration'][] = 'nullable';
            $rules[$fieldKey . '_expiration'][] = 'date';
            $rules[$fieldKey . '_expiration'][] = 'after_or_equal:today';

            // إذا الحقل مطلوب
            if ($field->required) {
              if (!$req->filled('id')) {
                // عند الإنشاء: الملف مطلوب
                $rules[$fieldKey . '_file'][] = 'required';
                $rules[$fieldKey . '_expiration'][] = 'required';
              } else {
                // عند التحديث: إذا تم رفع ملف جديد، تاريخ الانتهاء مطلوب
                if ($req->hasFile("additional_fields.{$field->name}_file")) {
                  $rules[$fieldKey . '_expiration'][] = 'required';
                }
              }
            }

            // قاعدة مهمة: إذا تم رفع ملف، التاريخ مطلوب (حتى لو الحقل غير مطلوب)
            if ($req->hasFile("additional_fields.{$field->name}_file")) {
              $rules[$fieldKey . '_expiration'][] = 'required';
            }

            break;

          case 'file_with_text':
            // إزالة القاعدة العامة للحقل الأساسي
            unset($rules[$fieldKey]);

            // قواعد الملف
            $rules[$fieldKey . '_file'] = [];
            $rules[$fieldKey . '_file'][] = 'file';
            $rules[$fieldKey . '_file'][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif';
            $rules[$fieldKey . '_file'][] = 'max:10240';

            // قواعد النص/الرقم
            $rules[$fieldKey . '_text'] = [];
            $rules[$fieldKey . '_text'][] = 'nullable';
            $rules[$fieldKey . '_text'][] = 'string';
            $rules[$fieldKey . '_text'][] = 'max:255';

            // إذا الحقل مطلوب
            if ($field->required) {
              if (!$req->filled('id')) {
                // عند الإنشاء: الملف مطلوب
                $rules[$fieldKey . '_file'][] = 'required';
                $rules[$fieldKey . '_text'][] = 'required';
              } else {
                // عند التحديث: إذا تم رفع ملف جديد، النص مطلوب
                if ($req->hasFile("additional_fields.{$field->name}_file")) {
                  $rules[$fieldKey . '_text'][] = 'required';
                }
              }
            }

            // قاعدة مهمة: إذا تم رفع ملف، النص مطلوب (حتى لو الحقل غير مطلوب)
            if ($req->hasFile("additional_fields.{$field->name}_file")) {
              $rules[$fieldKey . '_text'][] = 'required';
            }

            break;

          default:
            if (!$field->required) {
              $rules[$fieldKey][] = 'nullable';
            }
            $rules[$fieldKey][] = 'string';
            break;
        }
      }
    }

    // إنشاء رسائل خطأ مخصصة لحقول file_expiration_date
    $customMessages = [];
    if ($req->filled('template')) {
      $template = Form_Template::with('fields')->find($req->template);
      foreach ($template->fields as $field) {
        if ($field->type === 'file_expiration_date') {
          $fieldKey = 'additional_fields.' . $field->name;
          $customMessages = array_merge($customMessages, [
            $fieldKey . '_file.required' => __('The :attribute file is required.', ['attribute' => $field->label]),
            $fieldKey . '_file.file' => __('The :attribute must be a valid file.', ['attribute' => $field->label]),
            $fieldKey . '_file.mimes' => __('The :attribute must be a file of type: pdf, doc, docx, xls, xlsx, txt, csv, jpeg, png, jpg, webp, gif.', ['attribute' => $field->label]),
            $fieldKey . '_file.max' => __('The :attribute file size must not exceed 10MB.', ['attribute' => $field->label]),
            $fieldKey . '_expiration.required' => __('The expiration date for :attribute is required.', ['attribute' => $field->label]),
            $fieldKey . '_expiration.date' => __('The expiration date for :attribute must be a valid date.', ['attribute' => $field->label]),
            $fieldKey . '_expiration.after_or_equal' => __('The expiration date for :attribute must be today or a future date.', ['attribute' => $field->label]),
          ]);
        }

        if ($field->type === 'file_with_text') {
          $fieldKey = 'additional_fields.' . $field->name;
          $customMessages = array_merge($customMessages, [
            $fieldKey . '_file.required' => __('The :attribute file is required.', ['attribute' => $field->label]),
            $fieldKey . '_file.file' => __('The :attribute must be a valid file.', ['attribute' => $field->label]),
            $fieldKey . '_file.mimes' => __('The :attribute must be a file of type: pdf, doc, docx, xls, xlsx, txt, csv, jpeg, png, jpg, webp, gif.', ['attribute' => $field->label]),
            $fieldKey . '_file.max' => __('The :attribute file size must not exceed 10MB.', ['attribute' => $field->label]),
            $fieldKey . '_text.required' => __('The text field for :attribute is required.', ['attribute' => $field->label]),
            $fieldKey . '_text.string' => __('The text field for :attribute must be a valid text.', ['attribute' => $field->label]),
            $fieldKey . '_text.max' => __('The text field for :attribute must not exceed 255 characters.', ['attribute' => $field->label]),
          ]);
        }
      }
    }

    $validator = Validator::make($req->all(), $rules, $customMessages);

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

          if ($field->type === 'file_expiration_date') {
            $fileFieldName = $fieldName . '_file';
            $expirationFieldName = $fieldName . '_expiration';

            // معالجة الملف
            if ($req->hasFile("additional_fields.$fileFieldName")) {
              // حذف الملف القديم إذا موجود
              if (isset($oldAdditionalData[$fieldName]['value'])) {
                $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
              }
              $path = FileHelper::uploadFile($req->file("additional_fields.$fileFieldName"), 'customers/files');

              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $path,
                'expiration' => $req->input("additional_fields.$expirationFieldName"),
                'type'  => $field->type,
              ];
            } else {
              // في حال لم يتم رفع ملف جديد، نحتفظ بالبيانات القديمة مع تحديث تاريخ الانتهاء إذا تم تغييره
              if (isset($oldAdditionalData[$fieldName])) {
                $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
                if ($req->filled("additional_fields.$expirationFieldName")) {
                  $structuredFields[$fieldName]['expiration'] = $req->input("additional_fields.$expirationFieldName");
                }
              }
            }
          } else if ($field->type === 'file_with_text') {
            $fileFieldName = $fieldName . '_file';
            $textFieldName = $fieldName . '_text';

            // معالجة الملف
            if ($req->hasFile("additional_fields.$fileFieldName")) {
              // حذف الملف القديم إذا موجود
              if (isset($oldAdditionalData[$fieldName]['value'])) {
                $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
              }
              $path = FileHelper::uploadFile($req->file("additional_fields.$fileFieldName"), 'customers/files');

              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $path,
                'text' => $req->input("additional_fields.$textFieldName"),
                'type'  => $field->type,
              ];
            } else {
              // في حال لم يتم رفع ملف جديد، نحتفظ بالبيانات القديمة مع تحديث النص إذا تم تغييره
              if (isset($oldAdditionalData[$fieldName])) {
                $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
                if ($req->filled("additional_fields.$textFieldName")) {
                  $structuredFields[$fieldName]['text'] = $req->input("additional_fields.$textFieldName");
                }
              }
            }
          } else if (in_array($fieldType, ['file', 'image'])) {
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
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Customer')]);
        }
        $user = auth()->user();
        if (!$user || !$user->checkCustomer($find->id)) {
          return response()->json(['status' => 2,  'error' => __('You do not have permission to do actions to this record')]);
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

        // (new WalletsController)->store('customer', $done->id, true);
      }

      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Customer')]);
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
        'success' => __('Customer saved successfully'),
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
      $find = Customer::with(['wallet', 'transactions', 'tasks', 'points', 'tags', 'users'])->findOrFail($req->id);

      if (!$find) {
        return response()->json(['status' => 2, 'error' => __('Can not find the selected Customer')]);
      }

      $user = auth()->user();
      if (!$user || !$user->checkCustomer($find->id)) {
        return response()->json(['status' => 2,  'error' => __('You do not have permission to do actions to this record')]);
      }

      // حذف المحفظة إن وُجدت
      if ($find->wallet) {
        $find->wallet->delete();
      }

      // التحقق من النشاطات المرتبطة
      $hasRelations =
        $find->transactions()->exists() ||
        $find->tasks()->exists() ||
        $find->points()->exists() ||
        $find->tags()->exists() ||
        $find->users()->exists();

      // حذف نهائي إذا لا يملك علاقات
      if (!$hasRelations) {
        $find->forceDelete();
      } else {
        $find->delete(); // soft delete
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
    // $user = auth()->user();
    // if (!$user || !$user->checkCustomer($data->id)) {
    //   abort(403);
    // }

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
