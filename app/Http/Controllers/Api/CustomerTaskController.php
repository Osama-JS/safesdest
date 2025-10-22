<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Task;
use App\Models\Form_Template;
use App\Models\Form_Field;
use App\Models\Pricing_Template;
use App\Models\Vehicle_Size;
use App\Models\Settings;
use App\Models\Vehicle;
use App\Models\Task_History;
use App\Models\Driver;
use App\Models\Point;
use App\Models\Pricing;
use App\Models\Pricing_Method;
use App\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use App\Helpers\FileHelper;
use App\Helpers\IpHelper;
use App\Services\CustomerTaskPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class CustomerTaskController extends Controller
{
    public function getInitData()
    {
        try {
            // جلب جميع المركبات
            $vehicles = Vehicle::with([
                 'types' => function ($query) {
                     $query->select('id', 'vehicle_id', 'name', 'en_name')
                         ->with(['sizes' => function ($q) {
                             $q->select('id', 'vehicle_type_id', 'name');
                         }]);
                 }
             ])->get(['id', 'name', 'en_name']);


            // دالة مساعدة لجلب بيانات القالب بناءً على مفتاح الإعداد
            $getTemplateFields = function ($settingKey) {
                $setting = Settings::where('key', $settingKey)->first();

                if (!$setting) {
                    return null;
                }

                $template = Form_Template::find($setting->value);

                if (!$template) {
                    return null;
                }

                $fields = Form_Field::where('form_template_id', $template->id)
                    ->where('customer_can', 'write')
                    ->orderBy('order')
                    ->get([
                        'id',
                        'name',
                        'label',
                        'type',
                        'value',
                        'required',
                        'order',
                    ]);

                return [
                    'template' => $template,
                    'fields' => $fields,
                ];
            };

            // جلب القوالب الثلاثة
            $taskTemplate = $getTemplateFields('task_template');
            $taskFromTemplate = $getTemplateFields('task_from_port_template');
            $taskToTemplate = $getTemplateFields('task_to_port_template');

            // إرجاع البيانات بصيغة JSON
            return response()->json([
                'status' => 200,
                'message' => 'Data retrieved successfully',
                'data' => [
                    'vehicles' => $vehicles,
                    'task_template' => $taskTemplate,
                    'task_from_template' => $taskFromTemplate,
                    'task_to_template' => $taskToTemplate,
                ],
            ]);

        } catch (\Exception $e) {
            // التعامل مع الأخطاء بشكل منظم
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getTasksMap(Request $request)
    {
        try {
            $customer = $request->user();
            $query = Task::where('customer_id', $customer->id)->where('closed', false)->where('status', '!=', 'canceled')->where('status', '!=', 'refund');

            $tasks = $query->get();

            $tasksData = $tasks->map(function ($task) {
                $lat = $task->pickup->latitude;
                $lng = $task->pickup->longitude;
                if ($task->driver_id && in_array($task->status, ['in_progress', 'assign'])) {
                    $lat = $task->driver->altitude;
                    $lng = $task->driver->longitude;
                }
                return [
                    'id' => $task->id,
                    'status' => $task->status,
                    'lat' => $lat,
                    'lng' => $lng,
                    'pickup_address' => $task->pickup->address,
                    'delivery_address' => $task->delivery->address,
                    'driver_name' => $task->driver ? $task->driver->name : null,
                    'driver_phone' => $task->driver ? $task->driver->phone : null,
                    'driver_image' => $task->driver ? $task->driver->image ? url($task->driver->image) : null : null,
                    'price' => $task->total_price,
                    'currency' => 'SAR',
                    'vehicle' => $task->vehicle_size ? $task->vehicle_size->type->vehicle->name . '-' . $task->vehicle_size->type->name . ' - ' . $task->vehicle_size->name : null,
                    'created_at' => $task->created_at,
                ];
            });
            return response()->json([
                'status' => 200,
                'message' => 'Tasks retrieved successfully',
                'data' => $tasksData,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve tasks',
                'error' => $ex->getMessage()
            ]);
        }

    }

    public function getTasks(Request $request)
    {
        try {
            $customer = $request->user();

            $query = Task::where('customer_id', $customer->id);

            // Apply filters
            if ($request->filled('status')) {
                $statuses = is_array($request->status) ? $request->status : [$request->status];
                $query->whereIn('status', $statuses);
            }


            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhere('to_location', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $tasks = $query->with(['driver', 'vehicle_size', 'pricingTemplate'])
                          ->paginate($perPage);

            $tasksData = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'status' => $task->status,
                    'closed' => $task->closed,
                    'payment_status' => $task->payment_status,
                    'payment_method' => $task->payment_method,
                    'conditions' => $task->conditions,
                    'pricing_method' => $task->pricing_history['pricing_method_id'],

                    'pickup' => [
                        'lat' => $task->pickup->latitude,
                        'lng' => $task->pickup->longitude,
                        'address' => $task->pickup->address,
                        'contact_name' => $task->pickup->contact_name,
                        'contact_phone' => $task->pickup->contact_phone,
                        'note' => $task->pickup->note,
                        'scheduled_time' => $task->pickup->scheduled_time,
                        'image' => $task->pickup->image ? url('storage/' . $task->pickup->image) : null,
                    ],
                    'delivery' => [
                        'lat' => $task->delivery->latitude,
                        'lng' => $task->delivery->longitude,
                        'address' => $task->delivery->address,
                        'contact_name' => $task->delivery->contact_name,
                        'contact_phone' => $task->delivery->contact_phone,
                        'note' => $task->delivery->note,
                        'scheduled_time' => $task->delivery->scheduled_time,
                        'image' => $task->delivery->image ? url('storage/' . $task->delivery->image) : null,
                    ],
                    'price' => $task->total_price,
                    'currency' => 'SAR',
                    'driver' => $task->driver ? [
                        'name' => $task->driver->name,
                        'phone' => $task->driver->phone,
                        'image' => $task->driver->image ? asset('storage/' . $task->driver->image) : null,

                    ] : null,
                    'ad' => $task->ad ? [
                        'description' => $task->ad->description,
                        'min' => $task->ad->lowest_price,
                        'max' => $task->ad->highest_price
                    ] : null,
                    'vehicle' => $task->vehicle_size
                        ? $task->vehicle_size->type->vehicle->name . '-' . $task->vehicle_size->type->name . ' - ' . $task->vehicle_size->name
                        : null,
                    'vehicle_id' => $task->vehicle_size->type->vehicle->id,
                    'vehicle_type_id' => $task->vehicle_size->type->id,
                    'vehicle_size_id' => $task->vehicle_size->id,
                    // 👇 هنا التعديل المطلوب بدقة
                    'additional_data' => collect($task->customer_visible_additional_data)->map(function ($item) {
                        if (
                            isset($item['type'], $item['value'])
                            && in_array($item['type'], ['image', 'file_expiration_date'])
                            && is_string($item['value'])
                            && !str_starts_with($item['value'], 'http')
                        ) {
                            $item['value'] = url('storage/' . $item['value']);
                        }
                        return $item;
                    }),

                    'created_at' => $task->created_at,
                ];
            });

            return response()->json([
                'status' => 200,
                'data' => [
                    'tasks' => $tasksData,
                    'pagination' => [
                        'current_page' => $tasks->currentPage(),
                        'last_page' => $tasks->lastPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                        'from' => $tasks->firstItem(),
                        'to' => $tasks->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get tasks',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function validateStep1(Request $req)
    {
        Log::alert($req);

        $rules = [
            'vehicles.*.vehicle' => 'required|exists:vehicles,id',
            'vehicles.*.vehicle_type' => 'required|exists:vehicle_types,id',
            'vehicles.*.vehicle_size' => 'required|exists:vehicle_sizes,id',
            'vehicles.*.quantity' => 'nullable|integer|min:1',
        ];

        $task_template = Settings::where('key', 'task_template')->first();
        $template_id = $task_template->value;
        if ($task_template) {
            $fields = Form_Field::where('form_template_id', $req->template)->get();
            foreach ($fields as $field) {
                $fieldKey = 'additional_fields.' . $field->name;
                $rules[$fieldKey] = [];
                // لا نضع required للحقول المركبة هنا
                if (!$req->filled('id') && $field->required && !in_array($field->type, ['file_expiration_date', 'file_with_text','image','file'])) {
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
            }
        }

        $validator = Validator::make($req->all(), $rules, $customMessages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'error' => $validator->errors()
            ]);
        }

        $user = $req->user();
        Log::alert("vehicles: ".$req->input('vehicles'));

        // $sizes = collect($req->input('vehicles'))->pluck('vehicle_size')->unique()->filter()->values();
        $vehicles = json_decode($req->input('vehicles'), true); // تحويل JSON إلى array
        $sizes = collect($vehicles)
            ->pluck('vehicle_size')
            ->unique()
            ->filter()
            ->values();

        Log::alert("sizes: ".$sizes);

        if ($sizes->count() > 1) {
            return response()->json([
                'status' => 400,
                'message' => __('You cannot select more than one truck size in the same order')
            ]);
        }

        $task_template = Settings::where('key', 'task_template')->first();
        if (!$task_template) {
            return response()->json([
                'status' => 400,
                'message' => __('Error to Create the task')
            ]);
        }

        $pricingTemplates = Pricing_Template::availableForCustomer(
            $task_template->value,
            $user->id,
            $sizes
        )->pluck('id');
        Log::alert("user: ".$req->user()->id . " sizes: ". $sizes . "  pricingTemplates: " .  $pricingTemplates);

        if ($pricingTemplates->count() < 1) {
            return response()->json([
                'status' => 400,
                'message' => __('There is no Role match with your selections')
            ]);
        }

        $methodIds = Pricing::whereIn('pricing_template_id', $pricingTemplates)->where('status', true)->pluck('pricing_method_id');

        $methods = Pricing_Method::whereIn('id', $methodIds)->get();

        if ($methods->count() < 1) {
            return response()->json([
                'status' => 400,
                'message' => __('Error to find Pricing Methods')
            ]);
        }

        foreach ($methods as $key) {
            if ($key->type === 'points') {
                $pricing = $key->pricing()->whereIn('pricing_template_id', $pricingTemplates)->with('parametars')->first(); // eager load parametars

                if ($pricing && $pricing->parametars->isNotEmpty()) {
                    $fromIds = $pricing->parametars->pluck('from_val')->unique();
                    $toIds = $pricing->parametars->pluck('to_val')->unique();
                    $allPointIds = $fromIds->merge($toIds)->unique();

                    $points = Point::whereIn('id', $allPointIds)->get()->keyBy('id'); // تحميل كل النقاط دفعة واحدة

                    $paramData = $pricing->parametars->map(function ($param) use ($points) {
                        return [
                            'from_point' => $points->get($param->from_val),
                            'to_point' => $points->get($param->to_val),
                            'price' => $param->price,
                            'param' => $param->id,
                        ];
                    });

                    $key->params = $paramData;
                }
            }
        }

        return response()->json([
            'status' => 200,
            'success' => __('Validation passed ✅'),
            'data' => $methods
        ]);
    }

    public function validateStep2(Request $request, CustomerTaskPricingService $pricingService)
    {
        // تحقق من صحة البيانات
        $validation = $pricingService->validateRequest($request);
        if (!$validation['status']) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'error' => $validation['errors']
            ]);
        }

        // احسب السعر
        try {
            $pricing = $pricingService->calculatePricing($request);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error calculating pricing',
                'error' => $e->getMessage()
            ]);
        }

        if (!$pricing['status']) {
            return response()->json([
                'status' => 400,
                'message' => 'Error calculating pricing',
                'error' => $pricing['errors']
            ]);
        }

        return response()->json([
            'status' => 200,
            'success' => __('Validation passed ✅'),
            'data' => $pricing['data']
        ]);
    }

    public function store(Request $req, CustomerTaskPricingService $pricingService)
    {

        Log::alert("Start store");

        $validation = $pricingService->validateRequest($req);
        if (!$validation['status']) {
            return response()->json(['status' => 422, 'message' => 'Validation failed', 'error' => $validation['errors']]);
        }

        try {
            $pricing = $pricingService->calculatePricing($req);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error calculating pricing', 'error' => $e->getMessage()]);
        }

        if (!$pricing['status']) {
            return response()->json(['status' => 400, 'message' => 'Error calculating pricing', 'error' => $pricing['errors']]);
        }

        DB::beginTransaction();
        try {
            $userIp = IpHelper::getUserIpAddress();
            $data = $pricing['data'];
            $taskData = $pricing['task'];
            $ad = [];
            $history = [];

            $task_template = Settings::where('key', 'task_template')->first();
            $template_id = $task_template->value;

            $task = [
                'total_price' => $data['total_price'] ?? 0,
                'form_template_id' => $template_id,
                'owner' => 'customer',
                'customer_id' => Auth::id(),
                'pricing_id' => $taskData['pricing'],
                'vehicle_size_id' => $taskData['vehicles'][0],
                'conditions' => $req->conditions
            ];

            $history = [
                [
                    'action_type' => 'created',
                    'description' => 'Create Task By Customer',
                    'ip' => $userIp,
                ],
                [
                    'action_type' => 'in_progress',
                    'description' => 'Task in progress',
                    'ip' => $userIp,
                ]
            ];

            if (isset($data['service_commission']) && $data['service_commission'] !== '') {
                if ($data['service_commission'] > $task['total_price']) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('Commission cannot be greater than total price')]);
                }

                $task['commission_type'] = 'manual';
                $task['commission'] = $data['service_commission'];
                $data['manual_commission'] = $data['service_commission'];
            }

            if ($taskData['method'] == 0) {
                if (isset($taskData['vehicles_quantity']) && $taskData['vehicles_quantity'] > 1) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('You can create Task AD for just one task')]);
                }
                if ($req->filled('task_driver')) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('You can not assign driver to advertised Task')]);
                }
                $task['total_price'] = 0;
                $task['pricing_type'] = 'manual';
                $task['status'] = 'advertised';
                $ad = [
                    'highest_price' => $req->max_price,
                    'lowest_price' => $req->min_price,
                    'description' => $req->note_price,
                    'included' => $req->included ?? false,
                    'service_commission_type' => ($data['service_commission_type'] === 'percentage' ? 0 : 1) ?? 0,
                    'service_commission' => $data['service_tax_commission'] ?? 0,
                    'vat_commission' => $data['vat_commission'] ?? 0,
                ];
                $history[] = [
                    'action_type' => 'advertised',
                    'description' => 'set as Advertised',
                    'ip' => $userIp,
                ];
                $task['driver_id'] = null;
            }

            if (isset($taskData['vehicles_quantity']) && $taskData['vehicles_quantity'] > 1) {
                $order = Order::create([
                    'customer_id' => $task['customer_id'] ?? null,
                    'user_id' => Auth::id(),
                ]);
                if (!$order) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => 'Errors to create the tasks Collection']);
                }
                $task['order_id'] = $order->id;
            }

            $structuredFields = [];
            $filesToDelete = [];
            $origenToDelete = [];

            if ($task_template) {
                $data['form_template_id'] = $template_id;
                $template = Form_Template::with('fields')->find($template_id);

                foreach ($template->fields as $field) {
                    $fieldName = $field->name;
                    $fieldType = $field->type;

                    if ($fieldType === 'file_expiration_date') {
                        $fileFieldName = $fieldName . '_file';
                        $expirationFieldName = $fieldName . '_expiration';

                        if ($req->hasFile("additional_fields.$fileFieldName")) {
                            $path = FileHelper::uploadFile($req->file("additional_fields.$fileFieldName"), 'tasks/files');
                            $origenToDelete[] = $path;
                            $filesToDelete[] = $path;
                            $structuredFields[$fieldName] = [
                                'label' => $field->label,
                                'value' => $path,
                                'expiration' => $req->input("additional_fields.$expirationFieldName"),
                                'type' => $fieldType,
                            ];
                        } elseif ($req->filled("additional_fields.$expirationFieldName")) {
                            $structuredFields[$fieldName] = [
                                'label' => $field->label,
                                'value' => null,
                                'expiration' => $req->input("additional_fields.$expirationFieldName"),
                                'type' => $fieldType,
                            ];
                        }
                    } elseif (in_array($fieldType, ['file', 'image'])) {
                        if ($req->hasFile("additional_fields.$fieldName")) {
                            $path = FileHelper::uploadFile($req->file("additional_fields.$fieldName"), 'tasks/files');
                            $origenToDelete[] = $path;
                            $filesToDelete[] = $path;
                            $structuredFields[$fieldName] = [
                                'label' => $field->label,
                                'value' => $path,
                                'type' => $fieldType,
                            ];
                        }
                    } else {
                        if ($req->has("additional_fields.$fieldName")) {
                            $structuredFields[$fieldName] = [
                                'label' => $field->label,
                                'value' => $req->input("additional_fields.$fieldName"),
                                'type' => $fieldType,
                            ];
                        }
                    }
                }
                $task['additional_data'] = $structuredFields;
            }

            $pickup_point = [
                'type' => 'pickup',
                'sequence' => 1,
                'contact_name' => $req->pickup_name,
                'contact_phone' => $req->pickup_phone,
                'contact_emil' => $req->pickup_email,
                'address' => $req->pickup_address,
                'latitude' => $req->pickup_latitude,
                'longitude' => $req->pickup_longitude,
                'scheduled_time' => $req->pickup_before,
                'note' => $req->pickup_note,
            ];
            $delivery_point = [
                'type' => 'delivery',
                'sequence' => 1,
                'contact_name' => $req->delivery_name,
                'contact_phone' => $req->delivery_phone,
                'contact_emil' => $req->delivery_email,
                'address' => $req->delivery_address,
                'latitude' => $req->delivery_latitude,
                'longitude' => $req->delivery_longitude,
                'scheduled_time' => $req->delivery_before,
                'note' => $req->delivery_note,
            ];

            if ($req->hasFile('pickup_image')) {
                $pickup_point['image'] = (new FunctionsController())->convert($req->pickup_image, 'tasks/points');
            }

            if ($req->hasFile('delivery_image')) {
                $delivery_point['image'] = (new FunctionsController())->convert($req->delivery_image, 'tasks/points');
            }

            $number = $taskData['vehicles_quantity'] ?? 1;
            $task['pricing_history'] = $data;

            $tasks = collect()->times($number, function ($iteration) use ($task, $pickup_point, $delivery_point, $ad, $history) {
                $newAdditionalData = [];

                foreach ($task['additional_data'] as $key => $field) {
                    if (in_array($field['type'], ['file', 'image', 'file_expiration_date']) && !empty($field['value'])) {
                        $newFilePath = FileHelper::duplicateFile($field['value'], 'tasks/c/files');

                        $newAdditionalData[$key] = [
                            'label' => $field['label'],
                            'value' => $newFilePath,
                            'type' => $field['type'],
                        ];

                        if (isset($field['expiration'])) {
                            $newAdditionalData[$key]['expiration'] = $field['expiration'];
                        }
                    } else {
                        $newAdditionalData[$key] = $field;
                    }
                }

                // 🟢 استخدم نسخة جديدة من $task
                $taskCopy = $task;
                $taskCopy['additional_data'] = $newAdditionalData;

                $newTask = Task::create($taskCopy);
                $newTask->point()->create($pickup_point);
                $newTask->point()->create($delivery_point);
                $newTask->history()->createMany($history);

                if ($newTask->status === 'advertised') {
                    $newTask->ad()->create($ad);
                }

                return $newTask;
            });

            foreach ($origenToDelete ?? [] as $file) {
                FileHelper::deleteFileIfExists($file);
            }
            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => "$number Tasks created successfully.",
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            foreach ($filesToDelete ?? [] as $file) {
                FileHelper::deleteFileIfExists($file);
            }

            if ($req->hasFile('pickup_image') && isset($pickup_point['image'])) {
                unlink($pickup_point['image']);
            }

            if ($req->hasFile('delivery_image') && isset($delivery_point['image'])) {
                unlink($delivery_point['image']);
            }

            return response()->json([
                'status' => 500,
                'message' => 'Error creating tasks',
                'error' => $ex->getMessage(),
            ]);
        }
    }

    public function edit(Request $req, $id)
    {
        $data = Task::with('pickup', 'delivery', 'ad')->findOrFail($id);
        $user = $req->user();
        if (!$user || $user->id !== $data->customer_id) {
            return response()->json(['status' => 423,  'message' => __('You do not have permission to do actions to this record')]);
        }
        if ($data->closed) {
            return response()->json(['status' => 400, 'message' => 'This Task is already closed']);
        }
        if (!in_array($data->status, ['in_progress', 'advertised'])) {
            return response()->json([
                'status' => 400,
                'message' => __('This task cannot be modified in its current state'),
            ]);
        }

        $data->vehicle_type = $data->vehicle_size->vehicle_type_id;
        $data->vehicle = $data->vehicle_size->type->vehicle_id;
        $fields = Form_Field::where('form_template_id', $data->form_template_id)->get();

        $data->fields = $fields;

        return response()->json([
            'status' => 200,
            'message' => 'Task retrieved successfully',
            'data' => $data
        ]);
    }

    public function update(Request $req, CustomerTaskPricingService $pricingService)
    {
        $oldTask = Task::findOrFail($req->id);
        $user = $req->user();
        if (!$user || $user->id !== $oldTask->customer_id) {
            return response()->json(['status' => 400,  'message' => __('You do not have permission to do actions to this record')]);
        }

        if ($oldTask->closed) {
            return response()->json(['status' => 400, 'message' => 'This Task is already closed']);
        }
        // ✳️ تحقق من صلاحية التعديل
        if (!in_array($oldTask->status, ['in_progress', 'advertised'])) {
            return response()->json([
                'status' => 400,
                'message' => __('This task cannot be modified in its current state'),
            ]);
        }

        // التحقق من الطلب
        $validation = $pricingService->validateRequest($req, "update");
        if (!$validation['status']) {
            return response()->json(['status' => 422, 'message' => 'Validation failed', 'error' => $validation['errors']]);
        }

        // حساب السعر
        try {
            $pricing = $pricingService->calculatePricing($req);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error calculating pricing', 'error' => $e->getMessage()]);
        }

        if (!$pricing['status']) {
            return response()->json(['status' => 400, 'message' => 'Error calculating pricing' , 'error' => $pricing['errors']]);
        }

        DB::beginTransaction();
        try {
            $userIp = IpHelper::getUserIpAddress();
            $data = $pricing['data'];
            $taskData = $pricing['task'];
            $ad = [];
            $history = [];

            if ($taskData['vehicles_quantity'] > 1) {
                DB::rollBack();
                return response()->json(['status' => 400, 'message' => 'You can not update Task with multiple vehicles']);
            }

            $task_template = Settings::where('key', 'task_template')->first();
            $template_id = $task_template->value;

            $task = [
                'total_price' => $data['total_price'] ?? 0,
                'form_template_id' => $template_id,
                'pricing_id' => $taskData['pricing'],
                'vehicle_size_id' => $taskData['vehicles'][0],
                'conditions' => $req->conditions
            ];

            $history = [
                [
                    'action_type' => 'updated',
                    'description' => 'Task updated By Customer',
                    'ip' => $userIp,
                ],
            ];

            if ($taskData['method'] == 0) {
                if (isset($taskData['vehicles_quantity']) && $taskData['vehicles_quantity'] > 1) {
                    DB::rollBack();
                    return response()->json(['status' => 400, 'message' => 'You can create Task AD for just one task']);
                }
                if ($req->filled('task_driver')) {
                    DB::rollBack();
                    return response()->json(['status' => 400, 'message' => 'You can not assign driver to advertised Task']);
                }
                $task['total_price'] = 0;
                $task['pricing_type'] = 'manual';
                $task['status'] = 'advertised';
                $ad = [
                    'highest_price' => $req->max_price,
                    'lowest_price' => $req->min_price,
                    'description' => $req->note_price,
                    'included' => $req->included ?? false,
                    'service_commission_type' => ($data['service_commission_type'] === 'percentage' ? 0 : 1) ?? 0,
                    'service_commission' => $data['service_tax_commission'] ?? 0,
                    'vat_commission' => $data['vat_commission'] ?? 0,
                ];
                $history[] = [
                    'action_type' => 'advertised',
                    'description' => 'set as Advertised',
                    'ip' => $userIp,
                ];

                $task['driver_id'] = null;
            }

            $oldAdditionalData = $oldTask->additional_data ?? [];
            $structuredFields = [];
            $filesToDelete = [];

            if ($task_template) {
                $template = Form_Template::with('fields')->find($template_id);

                foreach ($template->fields as $field) {
                    $fieldName = $field->name;
                    $fieldType = $field->type;

                    if ($fieldType === 'file_expiration_date') {
                        $fileFieldName = $fieldName . '_file';
                        $expirationFieldName = $fieldName . '_expiration';

                        if ($req->hasFile("additional_fields.$fileFieldName")) {
                            // حذف الملف القديم إن وجد
                            if (isset($oldAdditionalData[$fieldName]['value'])) {
                                FileHelper::deleteFileIfExists($oldAdditionalData[$fieldName]['value']);
                            }

                            $path = FileHelper::uploadFile($req->file("additional_fields.$fileFieldName"), 'tasks/files');

                            $structuredFields[$fieldName] = [
                                'label' => $field->label,
                                'value' => $path,
                                'expiration' => $req->input("additional_fields.$expirationFieldName"),
                                'type' => $fieldType,
                            ];
                        } elseif (isset($oldAdditionalData[$fieldName])) {
                            // لم يتم رفع ملف جديد، نحافظ على الملف القديم مع تحديث تاريخ الانتهاء إذا تم تعديله
                            $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
                            if ($req->filled("additional_fields.$expirationFieldName")) {
                                $structuredFields[$fieldName]['expiration'] = $req->input("additional_fields.$expirationFieldName");
                            }
                        } else {
                            // لم يتم رفع ملف جديد ولا يوجد ملف قديم، لكن قد يكون هناك تاريخ انتهاء فقط
                            if ($req->filled("additional_fields.$expirationFieldName")) {
                                $structuredFields[$fieldName] = [
                                    'label' => $field->label,
                                    'value' => null,
                                    'expiration' => $req->input("additional_fields.$expirationFieldName"),
                                    'type' => $fieldType,
                                ];
                            }
                        }
                    } elseif (in_array($fieldType, ['file', 'image'])) {
                        if ($req->hasFile("additional_fields.$fieldName")) {
                            if (isset($oldAdditionalData[$fieldName]['value'])) {
                                FileHelper::deleteFileIfExists($oldAdditionalData[$fieldName]['value']);
                            }

                            $path = FileHelper::uploadFile($req->file("additional_fields.$fieldName"), 'tasks/files');

                            $structuredFields[$fieldName] = [
                                'label' => $field->label,
                                'value' => $path,
                                'type' => $fieldType,
                            ];
                        } elseif (isset($oldAdditionalData[$fieldName])) {
                            $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
                        }
                    } else {
                        if ($req->has("additional_fields.$fieldName")) {
                            $structuredFields[$fieldName] = [
                                'label' => $field->label,
                                'value' => $req->input("additional_fields.$fieldName"),
                                'type' => $fieldType,
                            ];
                        } elseif (isset($oldAdditionalData[$fieldName])) {
                            $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
                        }
                    }
                }

                $task['additional_data'] = $structuredFields;
            }

            $imageForDelete = [];
            // نقطة الالتقاط
            $pickup_point = [
                'type' => 'pickup',
                'sequence' => 1,
                'contact_name' => $req->pickup_name,
                'contact_phone' => $req->pickup_phone,
                'contact_emil' => $req->pickup_email,
                'address' => $req->pickup_address,
                'latitude' => $req->pickup_latitude,
                'longitude' => $req->pickup_longitude,
                'scheduled_time' => $req->pickup_before,
                'note' => $req->pickup_note,
            ];

            if ($req->hasFile('pickup_image')) {
                if ($oldTask->pickup->image) {
                    $imageForDelete[] = $oldTask->pickup->image;
                }
                $pickup_point['image'] = (new FunctionsController())->convert($req->pickup_image, 'tasks/points');
            }

            // نقطة التسليم
            $delivery_point = [
                'type' => 'delivery',
                'sequence' => 1,
                'contact_name' => $req->delivery_name,
                'contact_phone' => $req->delivery_phone,
                'contact_emil' => $req->delivery_email,
                'address' => $req->delivery_address,
                'latitude' => $req->delivery_latitude,
                'longitude' => $req->delivery_longitude,
                'scheduled_time' => $req->delivery_before,
                'note' => $req->delivery_note,
            ];

            if ($req->hasFile('delivery_image')) {
                if ($oldTask->delivery->image) {
                    $imageForDelete[] = $oldTask->delivery->image;
                }
                $delivery_point['image'] = (new FunctionsController())->convert($req->delivery_image, 'tasks/points');
            }
            $newTask = Task::findOrFail($req->id);
            $newTask->update($task);
            $newTask->pickup()->update($pickup_point);
            $newTask->delivery()->update($delivery_point);
            $newTask->history()->createMany($history);
            if ($newTask->status !== 'advertised' && $oldTask->status !== 'advertised') {
                $oldTask->ad()->delete();
            }
            if ($newTask->status === 'advertised') {
                if ($oldTask->has('ad')) {
                    $newTask->ad()->update($ad);
                } else {
                    $newTask->ad()->create($ad);
                }
            }
            DB::commit();
            foreach ($imageForDelete ?? [] as $file) {
                unlink($file);
                FileHelper::deleteFileIfExists($file);
            }

            return response()->json([
                'status' => 200,
                'message' => "Tasks Updated successfully.",
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            foreach ($filesToDelete ?? [] as $file) {
                FileHelper::deleteFileIfExists($file);
            }

            if ($req->hasFile('pickup_image') && isset($pickup_point['image'])) {
                unlink($pickup_point['image']);
            }

            if ($req->hasFile('delivery_image') && isset($delivery_point['image'])) {
                unlink($delivery_point['image']);
            }

            return response()->json(['status' => 500, 'message' => 'Error creating tasks' , 'error' => $ex->getMessage()]);
        }
    }

}
