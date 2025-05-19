<?php

namespace App\Http\Controllers\admin;

use Exception;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\Team;
use App\Models\Order;
use App\Models\Point;
use App\Models\Teams;
use App\Models\Driver;
use App\Models\Pricing;
use App\Models\Task_Ad;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\Settings;
use App\Helpers\IpHelper;
use App\Models\Form_Field;
use App\Helpers\FileHelper;
use App\Models\Tag_Pricing;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Models\Tag_Customers;
use Ramsey\Uuid\Type\Decimal;
use App\Models\Pricing_Method;
use App\Services\MapboxService;
use App\Models\Pricing_Customer;
use App\Models\Pricing_Geofence;
use App\Models\Pricing_Template;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Services\TaskPricingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FunctionsController;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TasksController extends Controller
{
  public function index()
  {
    $customers = Customer::where('status', 'active')->get();
    $vehicles = Vehicle::all();
    $templates = Form_Template::all();
    $task_template = Settings::where('key', 'task_template')->first();
    return view('admin.tasks.index', compact('customers', 'vehicles', 'templates', 'task_template'));
  }



  public function getData(Request $request)
  {
    $query = Task::with('points', 'customer', 'user');

    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('id', 'ILIKE', '%' . $search . '%');
      });
    }

    if ($request->has('filter') && !empty($request->filter)) {
      $searchDate = $request->filter;
      $query->whereDate('created_at', $searchDate);
    }


    $query->orderBy('id', 'DESC');
    $tasks = $query->get();

    $unassignedStatuses = ['in_progress', 'pending_payment', 'payment_failed', 'advertised'];
    $assignedStatuses = ['assign', 'accepted', 'start'];
    $completedStatuses = ['completed', 'canceled'];

    $grouped = [
      'unassigned' => [],
      'assigned' => [],
      'completed' => [],
    ];

    foreach ($tasks as $task) {
      $customer = $task->customer;
      $user = $task->user;

      $avatar = $customer && $customer->avatar
        ? asset('storage/' . $customer->avatar)
        : asset('assets/img/person.png');

      $item = [
        'id'     => $task->id,
        'name'   => $customer ? $customer->name : ($user->name ?? 'غير معروف'),
        'owner'  => $customer ? 'customer' : 'admin',
        'status' => $task->status,
        'avatar' => $avatar,
        'point' => $task->point()->where('type', 'pickup')->first()
      ];

      if (in_array($task->status, $unassignedStatuses)) {
        $grouped['unassigned'][] = $item;
      } elseif (in_array($task->status, $assignedStatuses)) {
        $grouped['assigned'][] = $item;
      } elseif (in_array($task->status, $completedStatuses)) {
        $grouped['completed'][] = $item;
      }
    }

    return response()->json(['data' => $grouped]);
  }

  public function show($id)
  {
    $task = Task::with(['point', 'customer', 'driver'])->findOrFail($id);

    return response()->json([
      'success' => true,
      'data'    => [
        'id'         => $task->id,
        'status'     => $task->status,
        'driver'     => $task->driver->name ?? "",
        'team'       => $task->driver->team->name ?? "",
        'order_id'   => $task->order_id ?? "",
        'created_at' => $task->created_at->toDateTimeString(),
        'owner'      => $task->owner,
        'total_price'      => $task->total_price,
        'commission'      => $task->commission,
        'pickup' => $task->pickup,
        'delivery' => $task->delivery,
        'driver_id' => $task->driver_id,
        'driver' => $task->driver ?  $task->driver->name : null,


        'point' => [
          'latitude'  => $task->pickup->latitude ?? null,
          'longitude' => $task->pickup->longitude ?? null,
          'address'   => $task->pickup->address ?? null,
        ],

        'customer'   => [
          'owner'  => $task->owner,
          'name'   => $task->owner == "customer" ? optional($task->customer)->name : optional($task->user)->name,
          'phone'  => $task->owner == "customer" ? optional($task->customer)->phone : optional($task->user)->phone,
          'email'  => $task->owner == "customer" ? optional($task->customer)->email : optional($task->user)->email,
          'address'  => $task->owner == "customer" ? optional($task->customer)->company_address : '',
        ],
        'history' => $task->history
          ->sortByDesc('id') // ✅ الترتيب بحسب ID من الأعلى إلى الأدنى
          ->map(function ($val) {
            return [
              'type' => $val->action_type,
              'description' => $val->description,
              'date' => $val->created_at->format('F, Y-d H:i'),
              'user' => optional($val->user)->name,
              'driver' => optional($val->driver)->name,
              'file' => $val->file_path
                ? [
                  'url' => asset('storage/' . $val->file_path),
                  'type' => pathinfo($val->file_path, PATHINFO_EXTENSION),
                  'name' => basename($val->file_path),
                ]
                : null,
              'color' => match ($val->action_type) {
                'added' => 'success',
                'updated' => 'info',
                'assign' => 'primary',
                'canceld' => 'danger',
                default => 'secundary',
              }
            ];
          })
          ->values()
      ]


    ]);
  }

  public function chang_status(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:tasks,id',
      'status' => 'required|in:in_progress,assign,start,completed,canceled',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'type' => 'error', 'message' => $req->id]);
    }

    try {
      $find = Task::find($req->id);
      $done = $find->update(['status' => $req->status]);

      $userIp = IpHelper::getUserIpAddress();
      $history = [
        [
          'action_type' => $req->status,
          'description' => 'Change status from ' . $find->status,
          'ip' => $userIp,
          'user_id' => Auth::user()->id
        ]
      ];
      $find->history()->createMany($history);
      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'error to Change Task Status']);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => 'Task Status changed']);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
    }
  }

  public function getToAssign($id)
  {
    try {
      $data = Task::findOrFail($id);
      if (!in_array($data->status, ['in_progress', 'advertised'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This task cannot be modified in its current state'),
        ]);
      }
      $drivers = Driver::where('vehicle_size_id', $data->vehicle_size_id)->get();
      $data->drivers = $drivers;
      return response()->json($data);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function assign(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:tasks,id',
      'driver' => 'required|exists:drivers,id',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    DB::beginTransaction();
    try {
      $data = Task::find($req->id);
      if (!in_array($data->status, ['in_progress', 'advertised'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This task cannot be modified in its current state'),
        ]);
      }
      $userIp = IpHelper::getUserIpAddress();
      $history = [
        [
          'action_type' => 'assign',
          'description' => 'assign task manual',
          'ip' => $userIp,
          'user_id' => Auth::user()->id,
          'driver_id' => $req->task_driver
        ]
      ];

      if ($data->status === 'advertised') {
        if ($data->ad->status === 'running') {
          $data->ad()->update([
            'status' => 'closed'
          ]);
        }
      }

      $data->driver_id = $req->driver;
      $data->status = 'assign';

      $driver = Driver::findOrFail($req->driver);



      $data->commission = $data->total_price - $driver->calculateCommission($data->total_price);
      $data->history()->createMany($history);

      $data->save();



      DB::commit();
      return response()->json(['status' => 1, 'success' => __('task assigned successfully')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }


  public function store(Request $req, TaskPricingService $pricingService)
  {
    // التحقق من الطلب
    $validation = $pricingService->validateRequest($req);
    if (!$validation['status']) {
      return response()->json(['status' => 0, 'error' => $validation['errors']]);
    }

    // حساب السعر
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

      $task = [
        'total_price'      => $data['total_price'] ?? 0,
        'form_template_id' => $req->template,
        'user_id'          => Auth::id(),
        'pricing_id'       => $taskData['pricing'],
        'vehicle_size_id' => $taskData['vehicles'][0]
      ];


      if ($req->filled('owner') && $req->owner === 'customer') {
        $task['customer_id'] = $req->customer;
      }

      $history = [
        [
          'action_type' => 'created',
          'description' => 'Create Task',
          'ip' => $userIp,
          'user_id' => Auth::user()->id
        ],
        [
          'action_type' => 'in_progress',
          'description' => 'Task in progress',
          'ip' => $userIp,
          'user_id' => Auth::user()->id
        ]
      ];



      if ($req->filled('manual_total_pricing')) {
        $task['total_price'] = $req->manual_total_pricing;
        $task['pricing_type'] = 'manual';
        $data['manual_pricing'] = $req->manual_total_pricing;
      }


      if ($req->filled('task_driver')) {
        $task['driver_id'] = $req->task_driver;
        $driver = Driver::findOrFail($task['driver_id']); // توقف التنفيذ هنا إذا لم يوجد السائق
        // نحسب العمولة
        $task['commission'] = $task['total_price'] - $driver->calculateCommission($task['total_price']);
        // تحديث الحالة وإضافة السجل في التاريخ
        $task['status'] = 'assign';
        $history[] = [
          'action_type' => 'assigned',
          'description' => 'assign task manual ',
          'ip' => $userIp,
          'user_id' => Auth::id(),
          'driver_id' => $req->task_driver
        ];
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

      // additional fields
      $structuredFields   = [];
      $oldAdditionalData  = [];
      $filesToDelete      = [];

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

              $path = FileHelper::uploadFile($req->file("additional_fields.$fieldName"), 'tasks/files');

              $filesToDelete[] = $path; // لتتمكن من حذفه لاحقًا عند الفشل

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

        $task['additional_data'] = $structuredFields;
      }

      // نقطة الالتقاط
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

      if ($req->hasFile('pickup_image')) {
        $pickup_point['image'] = (new FunctionsController)->convert($req->image, 'tasks/points');
      }

      // نقطة التسليم
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

      if ($req->hasFile('delivery_image')) {
        $delivery_point['image'] = (new FunctionsController)->convert($req->image, 'tasks/points');
      }

      // إنشاء المهام بعدد المركبات المطلوبة
      $number = $taskData['vehicles_quantity'] ?? 1;

      $task['pricing_history'] = $data;
      $tasks = collect()->times($number, function ($iteration) use ($task, $pickup_point, $delivery_point, $ad, $history) {
        $newTask = Task::create($task);
        $newTask->point()->create($pickup_point);
        $newTask->point()->create($delivery_point);
        $newTask->history()->createMany($history);
        if ($newTask->status === 'advertised') {
          $newTask->ad()->create($ad);
        }
        return $newTask;
      });


      DB::commit();

      return response()->json([
        'status'  => 1,
        'success' => "{$number} Tasks created successfully.",
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
    $data = Task::with('pickup', 'delivery')->findOrFail($id);
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

  public function update(Request $req, TaskPricingService $pricingService)
  {

    $oldTask = Task::findOrFail($req->id);


    // ✳️ تحقق من صلاحية التعديل
    if (!in_array($oldTask->status, ['in_progress', 'advertised'])) {
      return response()->json([
        'status' => 2,
        'error' => __('This task cannot be modified in its current state'),
      ]);
    }

    // التحقق من الطلب
    $validation = $pricingService->validateRequest($req);
    if (!$validation['status']) {
      return response()->json(['status' => 0, 'error' => $validation['errors']]);
    }

    // حساب السعر
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

      if ($taskData['vehicles_quantity'] > 1) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'You can not update Task with multiple vehicles']);
      }

      $task = [
        'total_price'      => $data['total_price'] ?? 0,
        'form_template_id' => $req->template,
        'user_id'          => Auth::id(),
        'pricing_id'       => $taskData['pricing'],
        'vehicle_size_id' => $taskData['vehicles'][0]
      ];

      if ($req->filled('owner') && $req->owner === 'customer') {
        $task['customer_id'] = $req->customer;
      }

      $history = [
        [
          'action_type' => 'updated',
          'description' => 'Task updated',
          'ip' => $userIp,
          'user_id' => Auth::user()->id
        ],
      ];


      if ($req->filled('task_driver')) {

        $task['driver_id'] = $req->task_driver;
        $driver = Driver::findOrFail($task['driver_id']); // توقف التنفيذ هنا إذا لم يوجد السائق
        // نحسب العمولة
        $task['commission'] = $task['total_price'] - $driver->calculateCommission($task['total_price']);


        // تحديث الحالة وإضافة السجل في التاريخ
        $task['status'] = 'assign';
        $history[] = [
          'action_type' => 'assigned',
          'description' => 'Assign Task manual',
          'ip' => $userIp,
          'user_id' => Auth::id(),
          'driver_id' => $req->task_driver
        ];
      }

      if ($req->filled('manual_total_pricing')) {
        $task['total_price'] = $req->manual_total_pricing;
        $task['pricing_type'] = 'manual';
        $data['manual_pricing'] = $req->manual_total_pricing;
      }

      if ($taskData['method'] == 0) {
        if (isset($taskData['vehicles_quantity']) && $taskData['vehicles_quantity'] > 1) {
          DB::rollBack();
          return response()->json(['status' => 2, 'error' => 'You can create Task AD for just one task']);
        }
        if ($req->filled('task_driver')) {
          DB::rollBack();
          return response()->json(['status' => 2, 'error' => 'You can not assign driver to advertised Task']);
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


      $oldAdditionalData = $oldTask->additional_data ?? [];
      $structuredFields  = [];
      $filesToDelete     = [];

      if ($req->filled('template')) {
        $template = Form_Template::with('fields')->find($req->input('template'));

        foreach ($template->fields as $field) {
          $fieldName = $field->name;
          $fieldType = $field->type;

          if (in_array($fieldType, ['file', 'image'])) {
            if ($req->hasFile("additional_fields.$fieldName")) {
              // حذف الملف القديم إن وجد
              if (isset($oldAdditionalData[$fieldName]['value'])) {
                FileHelper::deleteFileIfExists($oldAdditionalData[$fieldName]['value']);
              }

              $path = FileHelper::uploadFile($req->file("additional_fields.$fieldName"), 'tasks/files');

              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $path,
                'type'  => $fieldType,
              ];
            } elseif (isset($oldAdditionalData[$fieldName])) {
              // لم يتم تعديل الملف، نعيد حفظه كما هو
              $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
            }
          } else {
            if ($req->has("additional_fields.$fieldName")) {
              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $req->input("additional_fields.$fieldName"),
                'type'  => $fieldType,
              ];
            } elseif (isset($oldAdditionalData[$fieldName])) {
              // الحقل لم يُعدّل، نحتفظ بالقيمة القديمة
              $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
            }
          }
        }

        $task['additional_data'] = $structuredFields;
      }

      $imageForDelete = [];
      // نقطة الالتقاط
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

      if ($req->hasFile('pickup_image')) {
        if ($oldTask->pickup->image) {
          $imageForDelete[] = $oldTask->pickup->image;
        }
        $pickup_point['image'] = (new FunctionsController)->convert($req->image, 'tasks/points');
      }

      // نقطة التسليم
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

      if ($req->hasFile('delivery_image')) {
        if ($oldTask->delivery->image) {
          $imageForDelete[] = $oldTask->delivery->image;
        }
        $delivery_point['image'] = (new FunctionsController)->convert($req->image, 'tasks/points');
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
        'status'  => 1,
        'success' => "Tasks Updated successfully.",
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

      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }


  public function validateStep1(Request $req)
  {
    $rules = [
      'owner' => 'required|in:admin,customer',
      'customer' => 'required_if:owner,customer',
      'template' => 'required|exists:form_templates,id',
      'vehicles.*.vehicle' => 'required|exists:vehicles,id',
      'vehicles.*.vehicle_type' => 'required|exists:vehicle_types,id',
      'vehicles.*.vehicle_size' => 'required|exists:vehicle_sizes,id',
      'vehicles.*.quantity' => 'required|integer|min:1',
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

    $pricingTemplates = Pricing_Template::availableForCustomer(
      $req->template,
      $req->customer ?? null,
      $sizes
    )->pluck('id');


    if ($pricingTemplates->count() < 1) {
      return response()->json([
        'status' => 2,
        'error' => __('There is no Pricing Role match with your selections')
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

  public function validateStep2(Request $request, TaskPricingService $pricingService)
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



  public function indexList()
  {
    $teams = Teams::all();

    return view('admin.tasks.list', compact('teams'));
  }

  public function getListData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'order',
      3 => 'team',
      4 => 'driver',
      5 => 'adress',
      6 => 'start',
      7 => 'complete',
      8 => 'status',
      9 => 'created_at'
    ];

    $totalData = Task::count();
    $limit     = $request->input('length');
    $start     = $request->input('start');
    $order     = $columns[$request->input('order.0.column')] ?? 'id';
    $dir       = $request->input('order.0.dir') ?? 'desc';

    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');
    $owner    = $request->input('owner');
    $team    = $request->input('team');
    $driver    = $request->input('driver');

    $query = Task::query();

    // ✅ فلترة بالتاريخ إذا كانت القيم موجودة
    if ($fromDate && $toDate) {
      $query->whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
      ]);
    }

    if ($owner === 'customer') {
      $query->whereNotNull('customer_id');
    } elseif ($owner === 'admin') {
      $query->whereNull('customer_id');
    }

    if ($team) {
      $query->whereHas('driver.team', function ($q) use ($team) {
        $q->where('id', $team);
      });
    }

    if ($driver) {
      $query->where('driver_id', $driver);
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
        'team'       => $task->driver->team->name ?? "-",
        'driver'     => $task->driver ?? '-',
        'owner'     => $task->owner ?? "-",
        'address'    => $task->pickup->address ?? "-",
        'start'      => ($task->pickup && $task->pickup->scheduled_time)
          ? Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i')
          : "",
        'complete'   => ($task->delivery && $task->delivery->scheduled_time)
          ? Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i')
          : "",
        'status'     => $task->status,
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


  public function confirmPayment($id)
  {
    DB::beginTransaction();
    try {
      $data = Task::findOrFail($id);
      if (in_array($data->status, ['in_progress', 'advertised'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This task cannot be Payed in its current state'),
        ]);
      }
      if ($data->payment_status === 'pending') {
        $transaction = Transaction::where('reference_id', $data->id)->first();
        if (!$transaction) {
          return response()->json([
            'status' => 2,
            'error' => __('Transaction not found')
          ]);
        }
        $transaction->update([
          'status' => 'paid',
          'user_check' => Auth::user()->id,
          'user_ip' => IpHelper::getUserIpAddress(),
          'checkout_at' => Carbon::now(),
        ]);
        $data->update([
          'payment_status' => 'completed'
        ]);
        DB::commit();

        return response()->json([
          'status' => 1,
          'message' => __('Payment has been confirmed for task') . ' #' . $data->id,
        ]);
      }
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'message' => __('You can not confirm payment for this task'),
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'message' => __('Task not found')
      ]);
    }
  }

  public function cancelPayment($id)
  {
    DB::beginTransaction();
    try {
      $data = Task::findOrFail($id);
      if (in_array($data->status, ['in_progress', 'advertised'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This task cannot be Payed in its current state'),
        ]);
      }
      if ($data->payment_status !== 'pending') {
        $transaction = Transaction::where('reference_id', $data->id)->first();
        if (!$transaction) {
          return response()->json([
            'status' => 2,
            'error' => __('Transaction not found')
          ]);
        }

        Transaction::where('reference_id', $data->id)->delete();

        $data->update([
          'payment_status' => 'waiting'
        ]);

        if ($transaction->receipt_image) {
          unlink($transaction->receipt_image);
        }
        DB::commit();
        return response()->json([
          'status' => 1,
          'message' => __('Payment has been canceled for task') . ' #' . $data->is,
        ]);
      }
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'message' => __('You can not cancel payment for this task'),
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'message' => __('Task not found')
      ]);
    }
  }
}
