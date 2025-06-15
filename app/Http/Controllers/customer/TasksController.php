<?php

namespace App\Http\Controllers\customer;

use App\Helpers\FileHelper;
use App\Helpers\IpHelper;
use Exception;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\Point;
use App\Models\Pricing;
use App\Models\Settings;
use App\Models\Form_Field;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\Pricing_Method;
use App\Models\Pricing_Template;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use App\Models\Form_Template;
use App\Models\Order;
use App\Services\CustomerTaskPricingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TasksController extends Controller
{
  public function index()
  {
    return view('customers.tasks.index');
  }


  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'order',
      3 => 'adress',
      4 => 'start',
      5 => 'complete',
      6 => 'status',
      7 => 'created_at'
    ];

    $totalData = Task::where('customer_id', auth()->user()->id)->count();
    $limit     = $request->input('length');
    $start     = $request->input('start');
    $order     = $columns[$request->input('order.0.column')] ?? 'id';
    $dir       = $request->input('order.0.dir') ?? 'desc';

    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');


    $query = Task::where('customer_id', auth()->user()->id);

    // ✅ فلترة بالتاريخ إذا كانت القيم موجودة
    if ($fromDate && $toDate) {
      $query->whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
      ]);
    }

    $totalFiltered = $query->count();

    $tasks = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    foreach ($tasks as $task) {
      $data[] = [
        'id'         => $task->id,
        'order'      => $task->order->id ?? "-",
        'driver'     => $task->driver ?? '-',
        'address'    => $task->pickup->address ?? "-",
        'start'      => ($task->pickup && $task->pickup->scheduled_time)
          ? Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i')
          : "",
        'complete'   => ($task->delivery && $task->delivery->scheduled_time)
          ? Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i')
          : "",
        'status'     => $task->status,
        'closed'     => $task->closed,
        'payment'     => $task->payment_status,
        'created_at' => $task->created_at->format('Y-m-d H:i'),
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

  public function paymentInfo($id)
  {
    try {
      $data = Task::findOrFail($id);
      if ($data->customer_id !== Auth::user()->id) {
        return response()->json([
          'status' => 2,
          'error' => __('Error to find the task'),
        ]);
      }
      if (in_array($data->status, ['in_progress', 'advertised'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This task cannot be Payed in its current state'),
        ]);
      }
      if ($data->payment_status !== 'waiting') {
        $transiction = Transaction::where('reference_id', $data->id)->first();
        return response()->json([
          'status' => 3,
          'message' => __('This task has already make payment request and it is ' . $data->payment_status),
          'data' => $transiction
        ]);
      }
      return response()->json($data);
    } catch (Exception $e) {
      return response()->json([
        'status' => 2,
        'error' => __('Task not found')
      ]);
    }
  }

  public function showDetails($id)
  {
    $task = Task::with([
      'customer',
      'driver',
      'user',
      'pickup',
      'delivery',
      'points',
      'payments',
      'order',
      'formTemplate',
      'pricingTemplate',
      'vehicle_size',
      'history.user',
      'history.driver',
    ])->findOrFail($id);

    if ($task->customer_id !== Auth::user()->id) {
      return redirect()->back();
    }
    return view('admin.tasks.show', compact('task'));
  }


  public function validateStep1(Request $req)
  {
    $rules = [

      'vehicles.*.vehicle' => 'required|exists:vehicles,id',
      'vehicles.*.vehicle_type' => 'required|exists:vehicle_types,id',
      'vehicles.*.vehicle_size' => 'required|exists:vehicle_sizes,id',
      'vehicles.*.quantity' => 'nullable|integer|min:1',
    ];


    $task_template = Settings::where('key', 'task_template')->first();
    $template_id = $task_template->value;
    if ($task_template) {
      $fields = Form_Field::where('form_template_id', $template_id)->get();

      foreach ($fields as $field) {
        $fieldKey = 'additional_fields.' . $field->name;
        $rules[$fieldKey] = [];

        // إذا لم تكن العملية تعديل أو الحقل مطلوب فعليًا
        if (!$req->filled('id') && $field->required) {
          if ($field->type == "file_expiration_date") {
            $rules[$fieldKey . '_file'][] = 'required';
            $rules[$fieldKey . '_expiration'][] = 'required';
          } else {
            $rules[$fieldKey][] = 'required';
          }
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
            $rules[$fieldKey . '_file'][] = 'file';
            $rules[$fieldKey . '_file'][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif';
            $rules[$fieldKey . '_file'][] = 'max:10240';

            $rules[$fieldKey . '_expiration'][] = 'date';

            // إذا الحقل مطلوب، نضيف required حسب الحاجة
            if (!$req->filled('id') && $field->required) {
              $rules[$fieldKey . '_file'][] = 'required_with:' . $fieldKey . '_expiration';
              $rules[$fieldKey . '_expiration'][] = 'required_with:' . $fieldKey . '_file';
            }

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
        'error' => $validator->errors()
      ]);
    }

    $sizes = collect($req->input('vehicles'))->pluck('vehicle_size')->unique()->filter()->values();

    if ($sizes->count() > 1) {
      return response()->json([
        'status' => 2,
        'error' => __('You cannot select more than one truck size in the same order')
      ]);
    }

    $task_template = Settings::where('key', 'task_template')->first();
    if (!$task_template) {
      return response()->json([
        'status' => 2,
        'error' => __('Error to Create the task')
      ]);
    }


    $pricingTemplates = Pricing_Template::availableForCustomer(
      $task_template->value,
      Auth::user()->id,
      $sizes
    )->pluck('id');


    if ($pricingTemplates->count() < 1) {
      return response()->json([
        'status' => 2,
        'error' => __('There is no Role match with your selections')
      ]);
    }

    $methodIds = Pricing::whereIn('pricing_template_id', $pricingTemplates)->where('status', true)->pluck('pricing_method_id');

    $methods = Pricing_Method::whereIn('id', $methodIds)->get();

    if ($methods->count() < 1) {
      return response()->json([
        'status' => 2,
        'error' => __('Error to find Pricing Methods')
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
      'status' => 1,
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
        'status' => 0,
        'error' => $validation['errors']
      ]);
    }

    // احسب السعر
    try {
      $pricing = $pricingService->calculatePricing($request);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 2,
        'error' => $e->getMessage()
      ]);
    }

    if (!$pricing['status']) {
      return response()->json([
        'status' => 2,
        'error' => $pricing['errors']
      ]);
    }

    // dd($pricing['data']);
    return response()->json([
      'status' => 1,
      'success' => __('Validation passed ✅'),
      'data' => $pricing['data']
    ]);
  }

  public function store(Request $req, CustomerTaskPricingService $pricingService)
  {
    $validation = $pricingService->validateRequest($req);
    if (!$validation['status']) {
      return response()->json(['status' => 0, 'error' => $validation['errors']]);
    }

    try {
      $pricing = $pricingService->calculatePricing($req);
    } catch (\Exception $e) {
      return response()->json(['status' => 0, 'error' => $e->getMessage()]);
    }

    if (!$pricing['status']) {
      return response()->json(['status' => 2, 'error' => $pricing['errors']]);
    }

    DB::beginTransaction();
    try {
      $userIp = IpHelper::getUserIpAddress();
      $data     = $pricing['data'];
      $taskData = $pricing['task'];
      $ad = [];
      $history = [];

      $task_template = Settings::where('key', 'task_template')->first();
      $template_id = $task_template->value;

      $task = [
        'total_price'      => $data['total_price'] ?? 0,
        'form_template_id' => $template_id,
        'owner'            =>  'customer',
        'customer_id'      => Auth::id(),
        'pricing_id'       => $taskData['pricing'],
        'vehicle_size_id'  => $taskData['vehicles'][0]
      ];



      $history = [
        [
          'action_type' => 'created',
          'description' => 'Create Task',
          'ip' => $userIp,
        ],
        [
          'action_type' => 'in_progress',
          'description' => 'Task in progress',
          'ip' => $userIp,
        ]
      ];

      if ($taskData['method'] == 0) {
        if (isset($taskData['vehicles_quantity']) && $taskData['vehicles_quantity'] > 1) {
          DB::rollBack();
          return response()->json(['status' => 2, 'error' => __('You can create Task AD for just one task')]);
        }
        if ($req->filled('task_driver')) {
          DB::rollBack();
          return response()->json(['status' => 2, 'error' => __('You can not assign driver to advertised Task')]);
        }
        $task['total_price']  = 0;
        $task['pricing_type'] = 'manual';
        $task['status']       = 'advertised';
        $ad = [
          'highest_price' => $req->max_price,
          'lowest_price' => $req->min_price,
          'description' =>  $req->note_price,
        ];
        $history[] = [
          'action_type' => 'advertised',
          'description' => 'set as Advertised',
          'ip' => $userIp,
          'user_id' => Auth::user()->id,
        ];
        $task['driver_id'] = null;
      }

      if (isset($taskData['vehicles_quantity']) && $taskData['vehicles_quantity'] > 1) {
        $order = Order::create([
          'customer_id' => $task['customer_id'] ?? null,
          'user_id'     => Auth::id(),
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
                'label'      => $field->label,
                'value'      => $path,
                'expiration' => $req->input("additional_fields.$expirationFieldName"),
                'type'       => $fieldType,
              ];
            } elseif ($req->filled("additional_fields.$expirationFieldName")) {
              $structuredFields[$fieldName] = [
                'label'      => $field->label,
                'value'      => null,
                'expiration' => $req->input("additional_fields.$expirationFieldName"),
                'type'       => $fieldType,
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
                'type'  => $fieldType,
              ];
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
        $task['additional_data'] = $structuredFields;
      }

      $pickup_point = [
        'type'           => 'pickup',
        'sequence'       => 1,
        'contact_name'   => $req->pickup_name,
        'contact_phone'  => $req->pickup_phone,
        'contact_emil'   => $req->pickup_email,
        'address'        => $req->pickup_address,
        'latitude'       => $req->pickup_latitude,
        'longitude'      => $req->pickup_longitude,
        'scheduled_time' => $req->pickup_before,
        'note'           => $req->pickup_note,
      ];
      $delivery_point = [
        'type'           => 'delivery',
        'sequence'       => 1,
        'contact_name'   => $req->delivery_name,
        'contact_phone'  => $req->delivery_phone,
        'contact_emil'   => $req->delivery_email,
        'address'        => $req->delivery_address,
        'latitude'       => $req->delivery_latitude,
        'longitude'      => $req->delivery_longitude,
        'scheduled_time' => $req->delivery_before,
        'note'           => $req->delivery_note,
      ];

      if ($req->hasFile('pickup_image')) {
        $pickup_point['image'] = (new FunctionsController)->convert($req->pickup_image, 'tasks/points');
      }

      if ($req->hasFile('delivery_image')) {
        $delivery_point['image'] = (new FunctionsController)->convert($req->delivery_image, 'tasks/points');
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
              'type'  => $field['type'],
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
        'status'  => 1,
        'success' => "$number Tasks created successfully.",
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
        'status' => 2,
        'error'  => $ex->getMessage(),
      ]);
    }
  }


  public function edit($id)
  {
    $data = Task::with('pickup', 'delivery', 'ad')->findOrFail($id);
    $user = auth()->user();
    if (!$user || $user->id !==  $data->customer_id) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
    }
    if ($data->closed) {
      return response()->json(['status' =>  2, 'error' => 'This Task is already closed']);
    }
    if (!in_array($data->status, ['in_progress', 'advertised'])) {
      return response()->json([
        'status' => 2,
        'error' => __('This task cannot be modified in its current state'),
      ]);
    }


    // $data->pickup->scheduled_time = $data->pickup->scheduled_time->format('Y-m-d\TH:i');
    // $data->delivery->scheduled_time = $data->delivery->scheduled_time->format('Y-m-d\TH:i');
    $data->vehicle_type = $data->vehicle_size->vehicle_type_id;
    $data->vehicle = $data->vehicle_size->type->vehicle_id;
    $fields = Form_Field::where('form_template_id', $data->form_template_id)->get();

    $data->fields =  $fields;

    return response()->json($data);
  }


  public function taskTracking($id)
  {
    try {
      $task = Task::findOrFail($id);
      if ($task->closed) {
        return redirect()->back();
      }
      $pickup = [
        'lat' => $task->pickup->latitude,
        'lng' => $task->pickup->longitude,
      ];

      $dropoff = [
        'lat' => $task->delivery->latitude,
        'lng' => $task->delivery->longitude,
      ];

      $driver = null;
      if ($task->driver_id && $task->driver) {
        $driver = [
          'lat' => $task->driver->altitude,
          'lng' => $task->driver->longitude,
        ];
      }
      return view('admin.tasks.tracking', compact('task', 'pickup', 'dropoff', 'driver'));
    } catch (Exception $ex) {
      return redirect()->back();
    }
  }
}
