<?php

namespace App\Http\Controllers\admin;

use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Http\Controllers\Controller;
use App\Models\Form_Field;
use App\Models\Order;
use App\Models\Point;
use App\Models\Pricing;
use App\Models\Pricing_Customer;
use App\Models\Pricing_Geofence;
use App\Models\Pricing_Method;
use App\Models\Pricing_Template;
use App\Models\Settings;
use App\Models\Tag_Customers;
use App\Models\Tag_Pricing;
use App\Models\Task;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Services\MapboxService;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Type\Decimal;
use App\Services\TaskPricingService;
use Exception;
use Illuminate\Support\Facades\Auth;

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
    $task = Task::with(['point', 'customer'])->findOrFail($id);

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
        'pickup_note' => $task->pickup->note,
        'delivery_note' => $task->delivery->note,
        'pickup_image' => $task->pickup->note,
        'delivery_image' => $task->delivery->note,

        'customer'   => [
          'owner'  => $task->owner,
          'name'   => $task->owner == "customer" ? optional($task->customer)->name : optional($task->user)->name,
          'phone'  => $task->owner == "customer" ? optional($task->customer)->phone : optional($task->user)->phone,
          'email'  => $task->owner == "customer" ? optional($task->customer)->email : optional($task->user)->email,
          'address'  => $task->owner == "customer" ? optional($task->customer)->company_address : '',
        ],
      ]
    ]);
  }







  public function store(Request $req, TaskPricingService $pricingService)
  {

    $validation = $pricingService->validateRequest($req);
    if (!$validation['status']) {
      return response()->json([
        'status' => 0,
        'error' => $validation['errors']
      ]);
    }

    try {
      $pricing = $pricingService->calculatePricing($req);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 0,
        'error' => $e->getMessage()
      ]);
    }

    if (!$pricing['status']) {
      return response()->json([
        'status' => 2,
        'error' => $pricing['errors']
      ]);
    }

    DB::beginTransaction();
    try {
      $data = $pricing['data'];
      $taskData = $pricing['task'];

      $task = [
        'total_price'        => $data['total_price'] ?? 0,
        'form_template_id'   => $req->template,
        'user_id'            => Auth::id(),
        'pricing_id'         => $taskData['pricing'],
        'vehicles_size_id'   => $taskData['vehicles'][0]
      ];

      // الحالة: مهمة مملوكة لعميل
      if ($req->filled('owner') && $req->owner === 'customer') {
        $task['customer_id'] = $req->customer;
      }

      // الحالة: تعيين سائق للمهمة
      if ($req->filled('task_driver')) {
        $task['driver_id'] = $req->task_driver;
        $task['status'] = 'pending_payment';
      }

      // الحالة: تسعير يدوي
      if ($req->filled('manual_total_pricing')) {
        $task['total_price'] = $req->manual_total_pricing;
        $task['pricing_type'] = 'manual';
      }

      // الحالة: تسعير معلن
      if ($taskData['method'] == 0) {
        $task['total_price'] = 0;
        $task['pricing_type'] = 'manual';
        $task['status'] = 'advertised';
      }

      // إنشاء أمر عند وجود أكثر من مهمة
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

      // التعامل مع الحقول الإضافية من النموذج
      if ($req->filled('template')) {
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

      // عدد المهمات المراد إنشاؤها
      $number = $taskData['vehicles_quantity'] ?? 1;

      // إنشاء المهام مع النقاط
      $tasks = collect()->times($number, function ($iteration) use ($task, $pickup_point, $delivery_point) {
        $newTask = Task::create($task);
        $newTask->point()->create($pickup_point);
        $newTask->point()->create($delivery_point);
        return $newTask;
      });

      DB::commit();

      if ($tasks->count() === $number) {
        return response()->json([
          'status'  => 1,
          'success' => "{$number} Tasks created successfully.",
        ]);
      }

      return response()->json([
        'status'  => 2,
        'message' => "Some tasks may not have been created.",
      ]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error'  => $ex->getMessage(),
      ]);
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
        'status' => 0,
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
}
