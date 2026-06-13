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
use App\Models\Wallet;

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
        $banks = \App\Models\Bank::where('is_active', true)->get();

        return view('admin.drivers.index', compact('templates', 'teams', 'roles', 'vehicles', 'driver_template', 'banks'));
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
          6 => 'whatsapp',
          7 => 'team',
          8 => 'role',
          9 => 'tags',
          10 => 'status',
          11 => 'created_at'
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
                  ->orWhere('driver_code', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhereRaw("additional_data::text ILIKE ?", ["%{$search}%"]);
            });
        }
        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $totalFiltered = $query->count();

        $drivers = $query
          ->with(['team', 'role', 'tags.tag'])
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
              'driver_code' => $val->driver_code,
              'name' => $val->name,
              'image'      => $val->image ? url($val->image) : null,
              'username' => $val->username,
              'email' => $val->email,
              'phone' => $val->phone_code . ' ' . $val->phone,
              'whatsapp'   => $val->full_whatsapp_number ? str_replace('+', '', $val->full_whatsapp_number) : 'Not provided',
              'team'       => $val->team->name ?? 'No Team',
              'tags'       => $val->tags->pluck('tag.name')->implode(', '),
              'role'       => $val->role->name ?? "",
              'wallet'     => $val->wallet?->id,
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
            $driver = Driver::find($req->id);
            $done = $driver->update(['status' => $req->status]);

            if ($done) {
                // Send notification to driver
                $statusMessages = [
                    'active' => [
                        'title' => 'تم تفعيل حسابك! 🎉',
                        'msg' => 'تمت الموافقة على حسابك، يمكنك الآن البدء في استقبال المهام.'
                    ],
                    'blocked' => [
                        'title' => 'تنبيه: إيقاف الحساب',
                        'msg' => 'تم إيقاف حسابك مؤقتاً، يرجى التواصل مع الإدارة لمزيد من التفاصيل.'
                    ],
                    'verified' => [
                        'title' => 'توثيق الحساب',
                        'msg' => 'تم توثيق حسابك بنجاح.'
                    ]
                ];

                if (isset($statusMessages[$req->status])) {
                    app(\App\Services\NotificationService::class)->send(
                        'driver',
                        [$driver->id],
                        $statusMessages[$req->status]['title'],
                        $statusMessages[$req->status]['msg'],
                        '/images/admin-icon.png',
                        '/images/banner.png',
                        "/profile",
                        'account_status'
                    );
                }
            }

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
        $req->merge([
             'iban_number' => preg_replace('/\s+/', '', $req->iban_number),
         ]);
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
          // WhatsApp validation rules
          'phone_is_whatsapp'       => 'nullable|boolean',
          'whatsapp_country_code'   => 'nullable|string|max:10',
          'whatsapp_number'         => 'nullable|string|max:20',
          // Bank details validation rules
          'bank_name'               => 'nullable|string|max:255',
          'custom_bank_name'        => 'nullable|string|max:255',
          'account_number'          => 'nullable|string|min:8|max:20|regex:/^[0-9]+$/',
          'iban_number'             => 'nullable|string|size:24|regex:/^SA[0-9]{22}$/',
          'bic_code'                => 'nullable|string|max:20',
          'beneficiary_name'        => 'nullable|string|max:255',
          'bank_address1'           => 'nullable|string|max:255',
          'bank_address2'           => 'nullable|string|max:255',
          'bank_city'               => 'nullable|string|max:255',
          'bank_country'            => 'nullable|string|size:2',
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
                        $rules[$fieldKey][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif';
                        $rules[$fieldKey][] = 'max:10240';
                        break;
                    case 'image':
                        $rules[$fieldKey][] = 'image';
                        $rules[$fieldKey][] = 'mimes:jpeg,png,jpg,webp,gif';
                        $rules[$fieldKey][] = 'max:5120';
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
              'commission_type' => $req->commission_type,
              'commission'      => $req->commission,
              // WhatsApp data processing
              'phone_is_whatsapp' => $req->has('phone_is_whatsapp') ? (bool)$req->phone_is_whatsapp : false,
              // Bank details processing
              'bank_name' => $req->bank_name === 'other' ? $req->custom_bank_name : $req->bank_name,
              'account_number' => $req->account_number,
              'iban_number' => $req->iban_number ? str_replace(' ', '', $req->iban_number) : null,
              'bic_code' => $req->bic_code,
              'beneficiary_name' => $req->beneficiary_name,
              'bank_address1' => $req->bank_address1,
              'bank_address2' => $req->bank_address2,
              'bank_city' => $req->bank_city,
              'bank_country' => $req->bank_country ?? 'SA',
            ];

            // Handle WhatsApp number logic
            if ($req->has('phone_is_whatsapp') && $req->phone_is_whatsapp) {
                // If phone is WhatsApp, copy phone data to WhatsApp fields
                $data['whatsapp_country_code'] = $req->phone_code;
                $data['whatsapp_number'] = $req->phone;
            } else {
                // Use separate WhatsApp fields
                $data['whatsapp_country_code'] = $req->whatsapp_country_code;
                $data['whatsapp_number'] = $req->whatsapp_number;
            }

            if ($req->filled('team')) {
                $data['team_id'] = $req->team;
            }

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
                    } elseif ($field->type === 'file_with_text') {
                        $fileFieldName = $fieldName . '_file';
                        $textFieldName = $fieldName . '_text';

                        // معالجة الملف
                        if ($req->hasFile("additional_fields.$fileFieldName")) {
                            // حذف الملف القديم إذا موجود
                            if (isset($oldAdditionalData[$fieldName]['value'])) {
                                $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
                            }
                            $path = FileHelper::uploadFile($req->file("additional_fields.$fileFieldName"), 'drivers/files');

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
                    } elseif (in_array($fieldType, ['file', 'image'])) {
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
                    $data['image'] = (new FunctionsController())->convert($req->image, 'drivers');
                }

                $done = $find->update($data);

                if ($req->role) {
                    $find->syncRoles($req->role);
                }
            } else {
                if ($req->hasFile('image')) {
                    $data['image'] = (new FunctionsController())->convert($req->image, 'drivers');
                }

                $done = Driver::create($data);

                if ($req->role) {
                    $role = Role::find($req->role);
                    if ($role) {
                        $done->assignRole($role->name);
                    }
                }

                (new WalletsController())->store('driver', $done->id, true);
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



    public function createWallet(Request $req)
    {
        $validator = Validator::make($req->all(), [
          'id' => 'required|exists:drivers,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'type' => 'error', 'message' => $req->id]);
        }

        try {
            $find = Driver::find($req->id);
            if ($find->status != 'active') {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => 'Drive is not active']);
            }
            if ($find->wallet) {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => 'Wallet already exists']);
            }
            $wallet = Wallet::where('user_type', 'driver')->where('driver_id', $req->id)->first();
            if ($wallet) {
                return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'this wallet already exist']);
            }
            $done = (new WalletsController())->store('driver', $req->id, true);

            if (!$done) {
                return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'error to create Wallet']);
            }
            return response()->json(['status' => 1, 'type' => 'success', 'message' => 'Wallet created successfully']);
        } catch (Exception $ex) {
            return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $req)
    {
        DB::beginTransaction();

        try {
            $find = Driver::with(['wallet', 'transactions', 'tasks', 'possible_tasks', 'tags'])->findOrFail($req->id);

            if (!$find) {
                return response()->json(['status' => 2, 'error' => __('Can not find the selected Driver')]);
            }

            $user = auth()->user();
            if (!$user || !$user->checkDriver($find->id)) {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
            }

            // حذف المحفظة إن وُجدت
            if ($find->wallet) {
                $find->wallet->delete();
            }

            // التحقق من وجود علاقات (نشاطات)
            $hasRelations =
              $find->transactions()->exists() ||
              $find->tasks()->exists() ||
              $find->possible_tasks()->exists() ||
              $find->tags()->exists();

            // حذف نهائي إذا لا يوجد علاقات
            if (!$hasRelations) {
                $find->forceDelete();
            } else {
                $find->delete(); // soft delete
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
              'price'       => $item->total_price,
              'commission'       => $item->commission,
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
