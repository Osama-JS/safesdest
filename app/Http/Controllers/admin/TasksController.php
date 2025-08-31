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
use App\Models\Transaction;
use App\Models\Task_History;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Models\Tag_Customers;
use Ramsey\Uuid\Type\Decimal;
use App\Models\Pricing_Method;
use App\Services\MapboxService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Pricing_Customer;
use App\Models\Pricing_Geofence;
use App\Models\Pricing_Template;
use App\Models\Wallet_Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\TaskPricingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Jobs\SendEmailNotificationJob;
use App\Models\Team_Wallet_Transaction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FunctionsController;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\NotificationService;

class TasksController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_tasks', ['only' => ['index', 'getData', 'indexList', 'getListData']]);
        $this->middleware('permission:create_tasks', ['only' => ['store']]);
        $this->middleware('permission:edit_tasks', ['only' => ['edit', 'update']]);
        $this->middleware('permission:show_tasks', ['only' => ['showDetails', 'show']]);
        $this->middleware('permission:delete_tasks', ['only' => ['destroy']]);
        $this->middleware('permission:status_tasks', ['only' => ['chang_status', 'taskAddNote']]);
        $this->middleware('permission:assign_tasks', ['only' => ['getToAssign', 'assign']]);
        $this->middleware('permission:pricing_tasks', ['only' => ['editPricing', 'updatePricing']]);
        $this->middleware('permission:close_tasks', ['only' => ['closeTask']]);
        $this->middleware('permission:refund_tasks', ['only' => ['refundTask']]);
        $this->middleware('permission:pay_tasks', ['only' => ['paymentInfo', 'confirmPayment', 'cancelPayment', 'paymentRequestInfo']]);
    }

    public function index()
    {
        $customers = Auth::user()->customers;
        if (Auth::user()->can('mange_customers')) {
            $customers = Customer::where('status', 'active')->get();
        }
        $vehicles = Vehicle::all();
        $templates = Form_Template::all();
        $teams = Teams::all(); // Add teams for filtering
        $task_template = Settings::where('key', 'task_template')->first();
        $task_from_template = Settings::where('key', 'task_from_port_template')->first();
        $task_to_template = Settings::where('key', 'task_to_port_template')->first();
        return view('admin.tasks.index', compact('customers', 'vehicles', 'templates', 'teams', 'task_template', 'task_from_template', 'task_to_template'));
    }

    public function getData(Request $request)
    {
        $query = Task::with('points', 'customer', 'user', 'driver', 'driver.team');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'ILIKE', '%' . $search . '%');
            });
        }

        // Date range filter (replaces single date filter)
        if (
            $request->has('from_date') && $request->has('to_date') &&
            !empty($request->from_date) && !empty($request->to_date)
        ) {
            $query->whereBetween('created_at', [
              Carbon::parse($request->from_date)->startOfDay(),
              Carbon::parse($request->to_date)->endOfDay()
            ]);
        }

        // Owner type filter
        if ($request->has('owner') && !empty($request->owner)) {
            if ($request->owner === 'admin') {
                $query->whereNotNull('user_id')->whereNull('customer_id');
            } elseif ($request->owner === 'customer') {
                $query->whereNotNull('customer_id');
            }
        }

        // Team filter
        if ($request->has('team') && !empty($request->team)) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('team_id', $request->team);
            });
        }

        // Driver filter
        if ($request->has('driver') && !empty($request->driver)) {
            $query->where('driver_id', $request->driver);
        }

        $query->orderBy('id', 'DESC');
        $tasks = $query->get();

        $unassignedStatuses = ['in_progress', 'pending_payment', 'payment_failed', 'advertised'];
        $assignedStatuses = ['assign', 'started', 'in pickup point', 'loading', 'in the way', 'in delivery point','invoiced', 'unloading'];
        $completedStatuses = ['completed', 'canceled','refund'];

        $grouped = [
          'unassigned' => [],
          'assigned' => [],
          'completed' => [],
        ];

        foreach ($tasks as $task) {
            $customer = $task->customer;
            $user = $task->user;
            $driver = $task->driver;

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

            if ($driver) {
                $item['driver'] = [
                  'id' => $driver->id,
                  'name' => $driver->name,
                  'phone' => $driver->phone,
                  'phone_code' => $driver->phone_code,
                  'avatar' => $driver->image ? asset('storage/' . $driver->image) : asset('assets/img/person.png'),
                  'team' => $driver->team ? $driver->team->name : null,
                ];
            }

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
            'driver' => $task->driver ? $task->driver->name : null,


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

            'driver' => $task->driver ? [
              'name'   => optional($task->driver)->name,
              'phone'  => optional($task->driver)->phone_code . optional($task->driver)->phone,
              'whatsapp'    => $task->driver->full_whatsapp_number ? str_replace('+', '', $task->driver->full_whatsapp_number) : 'Not provided',
              'email'  => optional($task->driver)->email,
              'image'  => optional($task->driver)->image,
            ] : null,

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
          'status' => 'required|in:in_progress,started,in pickup point,loading,in the way,in delivery point,unloading,completed,invoiced,canceled',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'type' => 'error', 'message' => $req->id]);
        }

        try {
            $find = Task::find($req->id);
            $status = $find->status;
            $user = auth()->user();
            if (!$user || !$user->checkTask($req->id)) {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
            }
            if ($find->status === 'advertised') {
                return response()->json(['status' =>  2, 'type' => 'error', 'message' => __('This Task is in advertised mode you can not change status')]);
            }
            if ($find->closed) {
                return response()->json(['status' =>  2, 'type' => 'error', 'message' => __('This Task is already closed')]);
            }
            $data = [
              'status' => $req->status
            ];
            if ($req->status === 'completed') {
                $data['completed_at'] = now();
            }

            $done = $find->update($data);

            $userIp = IpHelper::getUserIpAddress();
            $history = [
              [
                'action_type' => $req->status,
                'description' => 'Change status from ' . $status . 'to ' . $find->status,
                'ip' => $userIp,
                'user_id' => Auth::user()->id
              ]
            ];
            $find->history()->createMany($history);
            if (!$done) {
                return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'error to Change Task Status']);
            }


            // رسائل مخصصة لكل نوع مستخدم
            $notiMessages = [
                'user' => [
                    'title' => '📌 تحديث حالة المهمة الخاصة بك',
                    'msg'   => "تم تحديث حالة المهمة رقم #{$find->id} من '{$status}' إلى '{$find->status}'."
                ],
                'customer' => [
                    'title' => 'تحديث حالة طلبك',
                    'msg'   => "تم تغيير حالة المهمة رقم #{$find->id} من '{$status}' إلى '{$find->status}'."
                ],
                'driver' => [
                    'title' => 'تحديث حالة المهمة الخاصة المعينة لك',
                    'msg'   => "تم تغيير حالة المهمة رقم #{$find->id} من '{$status}' إلى '{$find->status}'."
                ],
            ];

            // قائمة المستلمين: [نوع => IDs]
            $recipients = [
                'user' => [
                    $find->user_id,
                    optional(
                        User::select('id')->where('email', config('app.admin_email', 'info@safedest.com'))->first()
                    )->id
                ],
                'customer' => [$find->customer_id],
            ];

            // ✨ إذا كان هناك سائق مرتبط بالمهمة أضفه
            if (!empty($find->driver_id)) {
                $recipients['driver'] = [$find->driver_id];
            }


            foreach ($recipients as $type => $ids) {
                // نظّف المصفوفة من null
                $ids = array_filter($ids);

                if (!empty($ids) && isset($notiMessages[$type])) {
                    app(\App\Services\NotificationService::class)->send(
                        $type,
                        $ids, // هنا صارت كلها مصفوفة IDs صحيحة
                        $notiMessages[$type]['title'],
                        $notiMessages[$type]['msg'],
                        '/images/admin-icon.png',
                        '/images/banner.png',
                        "tasks/{$find->id}",
                        'task_status'
                    );
                }
            }
            return response()->json(['status' => 1, 'type' => 'success', 'message' => 'Task Status changed']);
        } catch (Exception $ex) {
            return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
        }
    }

    public function taskAddNote(Request $req)
    {
        $validator = Validator::make($req->all(), [
          'description' => 'nullable|string|required_without:file',
          'file' => 'nullable|file|max:10240|required_without:description',
          'task' => 'required|exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
              'status' => 0,
              'error'  => $validator->errors()
            ]);
        }

        DB::beginTransaction();
        try {

            $find = Task::findOrFail($req->task);
            $user = auth()->user();
            if (!$user || !$user->checkTask($find->id)) {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
            }

            $filePath = null;
            $fileType = null;

            if ($req->hasFile('file')) {
                $file = $req->file('file');

                // إنشاء بادئة عشوائية مكونة من أرقام فقط (مثلاً: 4 أرقام)
                $prefix = rand(1000, 9999);

                // الحصول على الاسم الأصلي للملف
                $originalName = $file->getClientOriginalName();

                // اسم الملف النهائي: بادئة-الاسم_الأصلي
                $fileName = $prefix . '-' . $originalName;

                // حفظ الملف في مجلد 'task_histories' داخل التخزين العام
                $filePath = $file->storeAs('task_histories', $fileName, 'public');

                // استخراج نوع الملف (الامتداد)
                $fileType = $file->getClientOriginalExtension();
            }
            Task_History::create([
              'task_id' => $req->task,
              'description' => $req->description,
              'file_path' => $filePath,
              'file_type' => $fileType,
              'user_id' => Auth::user()->id,
              'action_type' => 'added',
            ]);

            $task = Task::find($req->task);
            if ($task) {
                // رسائل مخصصة لكل نوع
                $notifications = [
                    'user' => [
                        'title' => 'إضافة ملاحظة للمهمة',
                        'msg'   => "تمت إضافة ملاحظة إلى مهمتك رقم #{$task->id}"
                    ],
                    'customer' => [
                        'title' => 'تحديث على المهمة الخاصة بك',
                        'msg'   => "تمت إضافة ملاحظة على مهمتك رقم #{$task->id} من قِبل فريق العمل"
                    ],
                    'driver' => [
                        'title' => 'تنبيه للسائق',
                        'msg'   => "تمت إضافة ملاحظة على المهمة رقم #{$task->id} التي تعمل عليها"
                    ],
                ];

                // قائمة المستلمين: [نوع => ID]
                $recipients = [
                    'user'     => $task->user_id,
                    'customer' => $task->customer_id,
                    'driver'   => $task->driver_id,
                ];

                foreach ($recipients as $type => $id) {
                    if ($id && isset($notifications[$type])) {
                        $noti = $notifications[$type];

                        app(\App\Services\NotificationService::class)->send(
                            $type,
                            [$id], // IDs المستلمين
                            $noti['title'],
                            $noti['msg'],
                            '/images/admin-icon.png',
                            '/images/banner.png',
                            "/tasks/{$task->id}",
                            'task_note' // نوع الإشعار
                        );
                    }
                }

            }



            DB::commit();
            return response()->json([
              'status' => 1,
              'success' => 'Task Note Added Successfully',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            if ($req->hasFile('file')) {
                unlink($filePath);
            }
            return response()->json([
              'status' => 2,
              'error'  => $ex->getMessage()
            ]);
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
            $data = Task::with(['customer', 'user', 'pickup', 'delivery', 'vehicle_size'])->find($req->id);

            if ($data->closed) {
                return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'This Task is already closed']);
            }

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


            if ($data->commission_type == 'dynamic') {
                $data->commission =  $driver->calculateCommission($data->total_price);
            }

            if ($driver->team) {
                $data->team_id = $driver->team_id;
            }

            $data->history()->createMany($history);

            $data->save();


            // رسائل مخصصة لكل نوع
            $notifications = [
                'user' => [
                    'title' => 'تحديث على مهمتك',
                    'msg'   => "تم تعيين المهمة رقم #{$data->id} إلى سائق لتنفيذها"
                ],
                'customer' => [
                    'title' => 'مهمتك قيد التنفيذ',
                    'msg'   => "تم تعيين المهمة رقم #{$data->id} إلى سائق وسيتم تنفيذها قريبًا"
                ],
                'driver' => [
                    'title' => 'تم تعيين مهمة جديدة لك',
                    'msg'   => "لقد تم تعيين المهمة رقم #{$data->id} لك، يرجى مراجعة التفاصيل والبدء في التنفيذ"
                ],
            ];

            // قائمة المستلمين: [نوع => ID]
            $recipients = [
                'user'     => $data->user_id,
                'customer' => $data->customer_id,
                'driver'   => $data->driver_id,
            ];

            foreach ($recipients as $type => $id) {
                if ($id && isset($notifications[$type])) {
                    $noti = $notifications[$type];

                    app(\App\Services\NotificationService::class)->send(
                        $type,
                        [$id], // IDs المستلمين
                        $noti['title'],
                        $noti['msg'],
                        '/images/admin-icon.png',
                        '/images/banner.png',
                        "/tasks/{$data->id}",
                        'task_note' // نوع الإشعار
                    );
                }
            }



            // $task = Task::with(['customer', 'pickup', 'delivery', 'vehicle_size', 'order', 'user'])->findOrFail($req->id);
            // $pdf = Pdf::loadView('admin.tasks.report_pdf', compact('task'));

            // $pdfPath = storage_path("app/public/task-report-{$task->id}.pdf");
            // Storage::put("public/task-report-{$task->id}.pdf", $pdf->output());

            // dd('osama');
            // // إرسال الإشعارات بالبريد الإلكتروني
            // $this->sendTaskAssignmentNotifications($data, $driver);

            DB::commit();
            return response()->json(['status' => 1, 'success' => __('task assigned successfully')]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }


    public function store(Request $req, TaskPricingService $pricingService)
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

            $task = [
              'total_price'      => $data['total_price'] ?? 0,
              'form_template_id' => $req->template,
              'user_id'          => Auth::id(),
              'pricing_id'       => $taskData['pricing'],
              'vehicle_size_id'  => $taskData['vehicles'][0]
            ];

            if ($req->filled('owner') && $req->owner === 'customer') {
                if (!Auth::user()->can('mange_customers')) {
                    $ownsCustomer = Auth::user()->customers()->where('id', $req->customer)->exists();
                    if (!$ownsCustomer) {
                        return response()->json([
                          'status' => 2,
                          'error' => ['You do not have permission to create task for this customer']
                        ]);
                    }
                }
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

            if ($req->filled('created_at')) {
                $task['created_at'] = $req->created_at;
            }




            if ($req->filled('task_driver')) {
                $task['driver_id'] = $req->task_driver;
                $driver = Driver::findOrFail($task['driver_id']);
                $task['commission'] = $driver->calculateCommission($task['total_price']);
                $task['status'] = 'assign';
                $history[] = [
                  'action_type' => 'assigned',
                  'description' => 'assign task manual ',
                  'ip' => $userIp,
                  'user_id' => Auth::id(),
                  'driver_id' => $req->task_driver
                ];
            }

            if (isset($data['service_commission']) && $data['service_commission'] !== '') {
                if ($data['service_commission'] > $task['total_price']) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('Commission cannot be greater than total price')]);
                }
                $task['commission_type'] = 'manual';
                $task['commission'] = $data['service_commission'];
                $data['manual_commission'] = $data['service_commission'];
            }



            if ($req->filled('manual_commission')) {
                if ($req->manual_commission > $task['total_price']) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('Commission cannot be greater than total price')]);
                }
                $task['commission_type'] = 'manual';
                $task['commission'] = $req->manual_commission;
                $data['manual_commission'] = $req->manual_commission;
            }


            if ($req->filled('pricing_details')) {
                $details = $req->pricing_details ?? [];
                $sumDetails = collect($details)->sum(function ($item) {
                    return is_numeric($item['amount'] ?? null) ? $item['amount'] : 0;
                });
                if ($sumDetails > $task['total_price']) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('Pricing details total cannot be greater than total price')]);
                }
                $task['pricing_details'] = $details;
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

                if (!$req->filled('max_price') || !$req->filled('min_price')) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('You must set min and max price for advertised task')]);
                }
                if ($req->max_price < $req->min_price) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('Max price must be greater than min price')]);
                }

                if ($req->min_price < 0 || $req->max_price < 0) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('Min price must be greater than 0')]);
                }


                $ad = [
                  'highest_price' => $req->max_price,
                  'lowest_price' => $req->min_price,
                  'description' =>  $req->note_price,
                  'included' =>  $req->included ?? false,
                  'service_commission_type' => ($data['service_commission_type'] === 'percentage' ? 0 : 1) ?? 0,
                  'service_commission' =>  $data['service_tax_commission'] ?? 0,
                  'vat_commission' => $data['vat_commission'] ?? 0,
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

            if ($req->filled('template')) {
                $data['form_template_id'] = $req->template;
                $template = Form_Template::with('fields')->find($req->input('template'));

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
                    $drivers = Driver::where([
                            ['status', '=', 'active'],
                            ['vehicle_size_id', '=', $newTask->vehicle_size_id],
                        ])
                        ->pluck('id');
                    app(\App\Services\NotificationService::class)->send(
                        'driver',
                        $drivers->toArray(), // IDs المستلمين (تحويل الـ Collection إلى مصفوفة)
                        '📢 إعلان مهمة جديدة', // العنوان
                        "هناك مهمة جديدة تناسب مركبتك (#{$newTask->id})، الرجاء الدخول ومراجعة التفاصيل.", // الرسالة
                        '/images/admin-icon.png',
                        '/images/banner.png',
                        "/tasks/{$newTask->id}",
                        'task_announcement' // نوع الإشعار
                    );
                }


                $notiMessages = [
                    'user' => [
                        'title' => 'إنشاء مهمة جديدة',
                        'msg'   => "تم إنشاء مهمة جديدة رقم #{$newTask->id} بنجاح."
                    ],
                    'customer' => [
                        'title' => 'إنشاء مهمة جديدة',
                        'msg'   => "تم إنشاء مهمة جديدة لحسابك رقم #{$newTask->id} بنجاح. من قبل الـ Adminstrator"
                    ],
                ];

                // قائمة المستلمين: [نوع => ID]
                $recipients = [
                    'user' => [
                        $newTask->user_id,
                        optional(
                            User::select('id')->where('email', config('app.admin_email', 'info@safedest.com'))->first()
                        )->id
                    ],
                    'customer' => [$newTask->customer_id],
                ];

                foreach ($recipients as $type => $ids) {
                    // نظّف المصفوفة من null
                    $ids = array_filter($ids);

                    if (!empty($ids) && isset($notiMessages[$type])) {
                        app(\App\Services\NotificationService::class)->send(
                            $type,
                            $ids, // هنا صارت كلها مصفوفة IDs صحيحة
                            $notiMessages[$type]['title'],
                            $notiMessages[$type]['msg'],
                            '/images/admin-icon.png',
                            '/images/banner.png',
                            "/tasks/{$newTask->id}",
                            'task_status'
                        );
                    }
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
        if (!$user || !$user->checkTask($data->id)) {
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

        $data->vehicle_type = $data->vehicle_size->vehicle_type_id;
        $data->vehicle = $data->vehicle_size->type->vehicle_id;
        $fields = Form_Field::where('form_template_id', $data->form_template_id)->get();

        $data->fields =  $fields;

        return response()->json($data);
    }

    public function update(Request $req, TaskPricingService $pricingService)
    {

        $oldTask = Task::findOrFail($req->id);
        $user = auth()->user();
        if (!$user || !$user->checkTask($oldTask->id)) {
            return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
        }

        if ($oldTask->closed) {
            return response()->json(['status' =>  2, 'error' => 'This Task is already closed']);
        }
        // ✳️ تحقق من صلاحية التعديل
        if (!in_array($oldTask->status, ['in_progress', 'advertised'])) {
            return response()->json([
              'status' => 2,
              'error' => __('This task cannot be modified in its current state'),
            ]);
        }

        // التحقق من الطلب
        $validation = $pricingService->validateRequest($req, "update");
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
                if (!Auth::user()->can('mange_customers')) {
                    $ownsCustomer = Auth::user()->customers()->where('id', $req->customer)->exists();

                    if (!$ownsCustomer) {
                        return response()->json([
                          'status' => 2,
                          'error' => ['You do not have permission to create task for this customer']
                        ]);
                    }
                }
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

            if ($req->filled('manual_total_pricing')) {
                $task['total_price'] = $req->manual_total_pricing;
                $task['pricing_type'] = 'manual';
                $data['manual_pricing'] = $req->manual_total_pricing;
            }

            if ($req->filled('created_at')) {
                $task['created_at'] = $req->created_at;
            }


            if ($req->filled('task_driver')) {

                $task['driver_id'] = $req->task_driver;
                $driver = Driver::findOrFail($task['driver_id']); // توقف التنفيذ هنا إذا لم يوجد السائق
                // نحسب العمولة
                $task['commission'] = $driver->calculateCommission($task['total_price']);


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

            if (isset($data['service_commission']) && $data['service_commission'] !== '') {
                if ($data['service_commission'] > $task['total_price']) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('Commission cannot be greater than total price')]);
                }
                $task['commission_type'] = 'manual';
                $task['commission'] = $data['service_commission'];
                $data['manual_commission'] = $data['service_commission'];
            }

            if ($req->filled('manual_commission')) {
                if ($req->manual_commission > $task['total_price']) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('Commission cannot be greater than total price')]);
                }
                $task['commission_type'] = 'manual';
                $task['commission'] = $req->manual_commission;
                $data['manual_commission'] = $req->manual_commission;
            }


            if ($req->filled('pricing_details')) {
                $details = $req->pricing_details ?? [];
                $sumDetails = collect($req->input('pricing_details', []))
                  ->sum(function ($item) {
                      return is_numeric($item['amount'] ?? null) ? $item['amount'] : 0;
                  });
                if ($sumDetails > $task['total_price']) {
                    DB::rollBack();
                    return response()->json(['status' => 2, 'error' => __('Pricing details total cannot be greater than total price')]);
                }
                $task['pricing_details'] = $details;
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
                  'included' =>  $req->included ?? false,
                  'service_commission_type' => ($data['service_commission_type'] === 'percentage' ? 0 : 1) ?? 0,
                  'service_commission' =>  $data['service_tax_commission'] ?? 0,
                  'vat_commission' => $data['vat_commission'] ?? 0,
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
                              'label'      => $field->label,
                              'value'      => $path,
                              'expiration' => $req->input("additional_fields.$expirationFieldName"),
                              'type'       => $fieldType,
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
                                  'label'      => $field->label,
                                  'value'      => null,
                                  'expiration' => $req->input("additional_fields.$expirationFieldName"),
                                  'type'       => $fieldType,
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
                $pickup_point['image'] = (new FunctionsController())->convert($req->pickup_image, 'tasks/points');
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
          2 => 'price',
          3 => 'team',
          4 => 'driver',
          5 => 'address',
          6 => 'start',
          7 => 'complete',
          7 => 'delevery',
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
        $search    = $request->input('search.value'); // البحث من DataTables
        $statusFilter = $request->input('status_filter'); // فلتر الحالة

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

        // 🔍 إضافة البحث
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                ->orWhere('delivery_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('order', function ($orderQuery) use ($search) {
                      $orderQuery->where('id', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('driver', function ($driverQuery) use ($search) {
                      $driverQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('username', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('pickup', function ($pickupQuery) use ($search) {
                      $pickupQuery->where('address', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('delivery', function ($deliveryQuery) use ($search) {
                      $deliveryQuery->where('address', 'LIKE', "%{$search}%");
                  })
                ;
            });
        }

        // 🔍 فلتر الحالة
        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
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
              'price'      => $task->total_price,
              'team'       => $task->driver->team->name ?? "-",
              'driver'     => $task->driver ?? '-',
              'owner'     => $task->owner ?? "-",
              'owner_info' => match ($task->owner) {
                  'admin' => $task->user->name ?? '-',
                  'customer' => $task->customer->name ?? '-',
                  default => '-',
              },
              'address'    => $task->pickup->address ?? "-",
              'start'      => ($task->pickup && $task->pickup->scheduled_time)
                ? Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i')
                : "",
              'complete'   => ($task->delivery && $task->delivery->scheduled_time)
                ? Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i')
                : "",
              'status'     => $task->status,
              'closed'     => $task->closed,
              'delivery'     => $task->delivery_number ?? '',
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
                $transiction = Transaction::with('user')->where('reference_id', $data->id)->first();
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

    public function paymentRequestInfo($id)
    {
        try {
            $task = Task::with(['driver', 'customer', 'user', 'team.users.user', 'pickup', 'delivery'])
                ->findOrFail($id);

            // Calculate driver amount (total_price - commission)
            $driverAmount = $task->total_price - $task->commission;

            // Get owner name
            $ownerName = '';
            if ($task->customer_id) {
                $ownerName = $task->customer->name ?? 'Customer';
            } elseif ($task->user_id) {
                $ownerName = $task->user->name ?? 'Admin User';
            }

            // Get driver name
            $driverName = $task->driver->name ??  'غير محدد';
            $driverPhone = $task->driver->name ? $task->driver->name  . $task->driver->phone_code .  $task->driver->phone : "";

            // Get team leader name - get first user in team as team leader
            $teamLeaderName = '';
            $teamLeaderPhone = '';
            if ($task->team_id && $task->team && $task->team->users->isNotEmpty()) {
                $teamLeaderName = $task->team->users->first()->user->name ?? 'غير محدد';
                $teamLeaderPhone = $task->team->users->first()->user->name ? $task->team->users->first()->user->phone_code .  $task->team->users->first()->user->phone : "";
            }

            // Get addresses
            $pickupAddress = $task->pickup->address ?? 'غير محدد';
            $deliveryAddress = $task->delivery->address ?? 'غير محدد';

            return response()->json([
                'status' => 1,
                'task' => [
                    'id' => $task->id,
                    'total_price' => $task->total_price,
                    'commission' => $task->commission,
                    'driver_amount' => $driverAmount,
                    'owner_name' => $ownerName,
                    'driver_name' => $driverName,
                    'driver_phone' => $driverPhone,
                    'team_leader_name' => $teamLeaderName,
                    'team_leader_phone' => $teamLeaderPhone,
                    'pickup_address' => $pickupAddress,
                    'delivery_address' => $deliveryAddress
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'error' => __('Task not found')
            ]);
        }
    }


    public function confirmPayment($id)
    {
        DB::beginTransaction();
        try {
            $data = Task::findOrFail($id);
            $user = auth()->user();
            if (!$user || !$user->checkTask($data->id)) {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
            }
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
            $user = auth()->user();
            if (!$user || !$user->checkTask($data->id)) {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
            }
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
                  'message' => __('Payment has been canceled for task') . ' #' . $data->id,
                ]);
            }
            DB::rollBack();
            return response()->json([
              'status' => 2,
              'message' => __('You can not cancel payment for this task ' . $data->payment_status),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
              'status' => 2,
              'message' => __('Task not found')
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

        return view('admin.tasks.show', compact('task'));
    }


    public function downloadTaskReport($id)
    {
        $task = Task::with(['customer', 'pickup', 'delivery', 'vehicle_size', 'order', 'user'])->findOrFail($id);

        return view('admin.tasks.report', compact('task'));
    }


    public function destroy(Request $req)
    {
        $validator = Validator::make($req->all(), [
          'id' => 'required|exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
              'status' => 2,
              'error' => __('Invalid task ID')
            ]);
        }

        DB::beginTransaction();

        try {
            // جلب المهمة مع جميع العلاقات المرتبطة
            $task = Task::with([
              'payments',
              'points',
              'history',
              'ad',
              'customer.wallet.transactions',
              'driver.wallet.transactions'
            ])->findOrFail($req->id);

            // 🚫 فحص الحالات التي تمنع الحذف
            $deletionChecks = $this->validateTaskDeletion($task);
            if (!$deletionChecks['canDelete']) {
                DB::rollBack();
                return response()->json([
                  'status' => 2,
                  'error' => $deletionChecks['reason']
                ]);
            }

            // 📁 جمع جميع الملفات المرتبطة بالمهمة
            $filesToDelete = $this->collectTaskFiles($task);

            // 🗑️ حذف البيانات المرتبطة بالترتيب الصحيح
            $this->deleteRelatedData($task);

            // 🗑️ حذف المهمة نفسها
            $task->delete();

            DB::commit();

            // 🧹 حذف الملفات بعد نجاح العملية
            $this->deleteTaskFiles($filesToDelete);

            return response()->json([
              'status' => 1,
              'success' => __('Task deleted successfully')
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
              'status' => 2,
              'error' => __('Error deleting task: ') . $ex->getMessage()
            ]);
        }
    }

    /**
     * التحقق من إمكانية حذف المهمة
     *
     * @param Task $task
     * @return array
     */
    private function validateTaskDeletion($task)
    {
        // 🚫 لا يمكن حذف المهام المكتملة
        if (in_array($task->status, ['completed', 'canceled'])) {
            return [
              'canDelete' => false,
              'reason' => __('Cannot delete completed or canceled tasks')
            ];
        }

        // 🚫 لا يمكن حذف المهام المدفوعة
        if (in_array($task->payment_status, ['completed', 'pending'])) {
            return [
              'canDelete' => false,
              'reason' => __('Cannot delete tasks with completed or pending payments')
            ];
        }

        // 🚫 لا يمكن حذف المهام التي لها معاملات محفظة
        $walletTransactions = \App\Models\Wallet_Transaction::where('task_id', $task->id)->count();
        if ($walletTransactions > 0) {
            return [
              'canDelete' => false,
              'reason' => __('Cannot delete tasks with wallet transactions')
            ];
        }

        // 🚫 لا يمكن حذف المهام التي لها معاملات دفع
        if ($task->payments && $task->payments->count() > 0) {
            return [
              'canDelete' => false,
              'reason' => __('Cannot delete tasks with payment records')
            ];
        }

        // 🚫 لا يمكن حذف المهام المغلقة
        if ($task->closed) {
            return [
              'canDelete' => false,
              'reason' => __('Cannot delete closed tasks')
            ];
        }

        // ✅ يمكن حذف المهام في الحالات المسموحة فقط
        $allowedStatuses = ['in_progress', 'advertised'];
        if (!in_array($task->status, $allowedStatuses)) {
            return [
              'canDelete' => false,
              'reason' => __('Can only delete tasks in progress or advertised status')
            ];
        }

        return [
          'canDelete' => true,
          'reason' => null
        ];
    }

    /**
     * جمع جميع الملفات المرتبطة بالمهمة
     *
     * @param Task $task
     * @return array
     */
    private function collectTaskFiles($task)
    {
        $filesToDelete = [];

        // 📁 ملفات من additional_data
        if ($task->additional_data && is_array($task->additional_data)) {
            foreach ($task->additional_data as $fieldName => $fieldData) {
                if (isset($fieldData['type']) && isset($fieldData['value'])) {
                    $fieldType = $fieldData['type'];
                    $fieldValue = $fieldData['value'];

                    // ملفات من الحقول المركبة والعادية
                    if (in_array($fieldType, ['file', 'image', 'file_expiration_date', 'file_with_text']) && !empty($fieldValue)) {
                        $filesToDelete[] = $fieldValue;
                    }
                }
            }
        }

        // 📁 ملفات من task history
        if ($task->history) {
            foreach ($task->history as $historyRecord) {
                if (!empty($historyRecord->file_path)) {
                    $filesToDelete[] = $historyRecord->file_path;
                }
            }
        }

        // 📁 ملفات من task points (images)
        if ($task->points) {
            foreach ($task->points as $point) {
                if (!empty($point->image)) {
                    $filesToDelete[] = $point->image;
                }
            }
        }

        // 📁 ملف delivery note
        if (!empty($task->delivery_note)) {
            $filesToDelete[] = $task->delivery_note;
        }

        return array_filter(array_unique($filesToDelete));
    }

    /**
     * حذف البيانات المرتبطة بالمهمة بالترتيب الصحيح
     *
     * @param Task $task
     * @return void
     */
    private function deleteRelatedData($task)
    {
        // 🗑️ حذف معاملات المحفظة المرتبطة بالمهمة (إذا لم تكن مكتملة)
        \App\Models\Wallet_Transaction::where('task_id', $task->id)
          ->where('status', 0) // فقط المعاملات غير المكتملة
          ->delete();

        // 🗑️ حذف العروض المرتبطة بإعلان المهمة
        if ($task->ad) {
            \App\Models\Task_Offire::where('task_ad_id', $task->ad->id)->delete();
            $task->ad->delete();
        }

        // 🗑️ حذف تاريخ المهمة
        $task->history()->delete();

        // 🗑️ حذف نقاط المهمة
        $task->points()->delete();

        // 🗑️ حذف المدفوعات المرتبطة (إذا كانت في حالة pending فقط)
        $task->payments()->where('status', 'pending')->delete();

        // 🗑️ حذف المعاملات المرتبطة بالمهمة من جدول transactions
        if ($task->customer) {
            $task->customer->transactions()
              ->where('reference_id', $task->id)
              ->where('type', 'delivery')
              ->where('status', '!=', 'completed')
              ->delete();
        }

        if ($task->driver) {
            $task->driver->transactions()
              ->where('reference_id', $task->id)
              ->where('type', 'delivery')
              ->where('status', '!=', 'completed')
              ->delete();
        }
    }

    /**
     * حذف الملفات المرتبطة بالمهمة
     *
     * @param array $filesToDelete
     * @return void
     */
    private function deleteTaskFiles($filesToDelete)
    {
        foreach ($filesToDelete as $filePath) {
            if (!empty($filePath)) {
                try {
                    // استخدام FileHelper للحذف الآمن
                    FileHelper::deleteFileIfExists($filePath);
                } catch (Exception $e) {
                    // تسجيل الخطأ ولكن لا نوقف العملية
                    Log::warning("Failed to delete file: {$filePath}. Error: " . $e->getMessage());
                }
            }
        }
    }


    public function closeTask(Request $req)
    {
        $validator = Validator::make($req->all(), [
          'id' => 'required|exists:tasks,id',
          'delivery_number' => 'nullable|string|max:255',
          'delivery_note' => 'required|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx,txt,csv|max:10240',
        ], [
          'id.required'  => __('Can not find the selected Task'),
          'id.exists'  => __('Can not find the selected Task'),
          'delivery_number.string' => __('The delivery number must be a valid text.'),
          'delivery_number.max' => __('The delivery number may not be greater than 255 characters.'),
          'delivery_note.required' => __('The delivery note file is required.'),
          'delivery_note.file' => __('The delivery note must be a valid file.'),
          'delivery_note.mimes' => __('The delivery note must be a file of type: jpeg, png, jpg, webp, pdf, doc, docx, txt, csv.'),
          'delivery_note.max' => __('The delivery note file size must not exceed 10MB.'),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        DB::beginTransaction();
        $deliveryNotePath = null;

        try {
            $task = Task::findOrFail($req->id);
            $user = auth()->user();

            if (!$user || !$user->checkTask($task->id)) {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
            }

            if ($task->closed) {
                return response()->json([
                  'status' => 2,
                  'error' => __('This Task already closed'),
                ]);
            }

            if ($task->status !== 'completed') {
                return response()->json([
                  'status' => 2,
                  'error' => __('This task cannot be closed in its current state'),
                ]);
            }

            if ($task->payment_status !== 'completed') {
                return response()->json([
                  'status' => 2,
                  'error' => __('This transaction cannot be closed until the payment is completed.'),
                ]);
            }

            $driver = Driver::find($task->driver_id);
            if (!$driver) {
                return response()->json([
                  'status' => 2,
                  'error' => __('This Task cannot be closed due to a driver issue'),
                ]);
            }

            // حذف الملف القديم إذا كان موجوداً
            if ($task->delivery_note) {
                FileHelper::deleteFileIfExists($task->delivery_note);
            }

            // رفع الملف الجديد باستخدام FileHelper
            $deliveryNotePath = FileHelper::uploadFile($req->file('delivery_note'), 'tasks/deliveryNotes');

            // تحديث المهمة مع رقم مذكرة التوصيل والملف
            $updateData = [
              'closed' => true,
              'delivery_note' => $deliveryNotePath,
              'delivery_number' => $req->delivery_number
            ];

            $task->update($updateData);

            $task->history()->create([
              'action_type' => 'closed',
              'description' => 'Task closed by admin' . ($req->delivery_number ? ' - Delivery Number: ' . $req->delivery_number : ''),
              'ip' => IpHelper::getUserIpAddress(),
              'user_id' => Auth::user()->id,
            ]);

            $wallet = $driver->wallet;
            if (!$wallet) {
                return response()->json([
                  'status' => 2,
                  'error' => __('This Task cannot be closed due to a wallet issue'),
                ]);
            }

            $data = [
              'amount'              => $task->total_price - $task->commission,
              'description'         => 'Delivery Amount for Task #' . $task->id . ($req->delivery_number ? ' - Delivery Number: ' . $req->delivery_number : ''),
              'transaction_type'    => 'credit',
              'wallet_id'           => $wallet->id,
              'maturity_time'       => Carbon::now()->copy()->addDays(3),
              'task_id'             => $task->id,
            ];
            if ($driver->team()->exists()) {
                $data['team_id'] = $driver->team->id;
            }

            Wallet_Transaction::create($data);

            if ($driver->team()->exists()) {
                Team_Wallet_Transaction::create([
                  'amount'              => $task->total_price - $task->commission,
                  'description'         => 'Delivery Amount for Task #' . $task->id . ($req->delivery_number ? ' - Delivery Number: ' . $req->delivery_number : '') . 'Driver: ' . $driver->name,
                  'transaction_type'    => 'credit',
                  'team_wallet_id'      => $driver->team->teamWallet->id,
                  'task_id'             => $task->id,
                ]);
            }

            // app(\App\Services\NotificationService::class)->send(
            //     'driver',
            //     $driver->id, // IDs المستلمين
            //     '📌 تم إقفال المهمة', // العنوان
            //     "تم إقفال المهمة رقم (#{$$task->id}). الرجاء مراجعة المهام الخاصة بك.", // الرسالة
            //     '/images/admin-icon.png',
            //     '/images/banner.png',
            //     "/tasks/{$$task->id}",
            //     'task_closed' // نوع الإشعار
            // );


            DB::commit();
            return response()->json(['status' => 1, 'success' => __('Task closed successfully')]);
        } catch (Exception $e) {
            DB::rollBack();
            // حذف الملف في حالة حدوث خطأ
            if ($deliveryNotePath) {
                FileHelper::deleteFileIfExists($deliveryNotePath);
            }
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    public function refundTask(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|exists:tasks,id',
            'resone' => 'required|string|max:500',
          ], [
            'id.required'  => __('Can not find the selected Task'),
            'id.exists'  => __('Can not find the selected Task'),
            'resone.required' => __('The Refund resone is required'),
            'resone.string' => __('The Refund resone must be a valid text'),
            'resone.max' => __('The Refund resone may not be greater than 500 characters'),

          ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {
            $task = Task::findOrFail($req->id);
            if (!$task->closed || $task->status === 'canceled' || $task->payment_status !== 'completed') {
                return response()->json(['status' => 2, 'error' => __('This Task cannot be refunded in its current state')]);
            }

            if ($task->ad) {
                if ($task->ad->offers) {
                    $task->ad->offers()->delete();
                }
                $task->ad()->delete();
            }

            $wallet_transaction = Wallet_Transaction::where('task_id', $task->id)->delete();
            $transaction = Transaction::where('reference_id', $task->id)->first();
            $transaction_receipt_image = null;
            if ($transaction) {
                if ($transaction->receipt_image) {
                    $transaction_receipt_image = $transaction->receipt_image;
                }
                $transaction->delete();
            }

            if ($task->payments) {
                $task->payments()->delete();
            }

            $notifications = [
               'user' => [
                   'title' => 'your task #'. $task->id .' was refunded and canceld',
                   'msg'   => "this task was refunded because of: " . $req->resone
               ],
               'customer' => [
                   'title' =>  'your task #'. $task->id .' was refunded and canceld',
                   'msg'   => "this task was refunded because of: " . $req->resone
               ],
               'driver' => [
                  'title' => "Task #{$task->id} that was assigned to you was refunded and canceled",
                  'msg'   => "This task was refunded because of: {$req->resone}"
                ],

            ];
            // قائمة المستلمين: [نوع => ID]
            $recipients = [
                'user'     => $task->user_id,
                'customer' => $task->customer_id,
                'driver'   => $task->driver_id,
            ];

            $deleviry_note = $task->deleviry_note;

            $task->update([
                          'status' => 'refund',
                          'closed' => false,
                          'payment_status' => 'waiting',
                          'driver_id' => null,
                          'deleviry_note' => null,
                          'delivery_number' => null
                        ]);

            $task->history()->create([
              'action_type' => 'refund',
              'description' => 'The task was refunded by admin. Resone: ' . $req->resone,
              'ip' => IpHelper::getUserIpAddress(),
              'user_id' => Auth::user()->id,
            ]);

            if ($deleviry_note) {
                FileHelper::deleteFileIfExists($deleviry_note);
            }
            if ($transaction_receipt_image) {
                unlink($transaction_receipt_image);
            }

            foreach ($recipients as $type => $id) {
                if ($id && isset($notifications[$type])) {
                    $noti = $notifications[$type];

                    app(\App\Services\NotificationService::class)->send(
                        $type,
                        [$id], // IDs المستلمين
                        $noti['title'],
                        $noti['msg'],
                        '/images/admin-icon.png',
                        '/images/banner.png',
                        "/tasks/{$task->id}",
                        'refund_task' // نوع الإشعار
                    );
                }
            }


            DB::commit();

            return response()->json(['status' => 1, 'success' => __('Task refunded successfully')]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }

    }


    public function editPricing($id)
    {
        try {
            $data = Task::select(['id', 'closed', 'payment_status', 'total_price', 'commission', 'pricing_details'])->findOrFail($id);
            $user = auth()->user();
            if (!$user || !$user->checkTask($data->id)) {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
            }
            if ($data->closed && Auth::user()->role_id !== 1) {
                return response()->json(['status' => 2, 'error' => __('This Task already closed. you can not update it')]);
            }
            if ($data->payment_status !== 'waiting'  && Auth::user()->role_id !== 1) {
                return response()->json(['status' => 2, 'error' => __('You cannot modify the pricing of this task as it has already been paid for')]);
            }
            return response()->json(['status' => 1, 'data' => $data]);
        } catch (Exception $ex) {
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    public function updatePricing(Request $req)
    {
        $validator = Validator::make($req->all(), [
          'price' => 'required|numeric|min:0',
          'commission' => 'required|numeric|lt:price',
          'pricing_details' => 'nullable|array',
          'pricing_details.*.label' => 'required_with:pricing_details.*.amount|string',
          'pricing_details.*.amount' => 'required_with:pricing_details.*.label|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }

        DB::beginTransaction();
        try {
            $find = Task::findOrFail($req->id);
            $user = auth()->user();
            if (!$user || !$user->checkTask($find->id)) {
                return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
            }
            if ($find->closed && Auth::user()->role_id !== 1) {
                return response()->json(['status' => 2, 'error' => __('This Task already closed. you can not update it')]);
            }
            $details = $req->pricing_details ?? [];
            $sumDetails = collect($req->input('pricing_details', []))
              ->sum(function ($item) {
                  return is_numeric($item['amount'] ?? null) ? $item['amount'] : 0;
              });
            if ($sumDetails > $req->price) {
                DB::rollBack();
                return response()->json(['status' => 2, 'error' => __('Pricing details total cannot be greater than total price')]);
            }

            $userIp = IpHelper::getUserIpAddress();
            $history = [
              [
                'action_type' => 'updated',
                'description' => 'Update Task Pricing Manual',
                'ip' => $userIp,
                'user_id' => Auth::user()->id
              ]
            ];
            $find->history()->createMany($history);
            $done = $find->update([
              'total_price' => $req->price,
              'commission' => $req->commission,
              'pricing_details' => $details,
              'pricing_type' => 'manual',
              'commission_type' => 'manual'
            ]);

            if (!$done) {
                DB::rollBack();
                return response()->json(['status' => 2, 'error' => __('Error: can not Update the task pricing')]);
            }
            DB::commit();
            return response()->json(['status' => 1, 'success' => __('pricing Updated successfully')]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
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

    public function connectTeam(Request $req)
    {
        DB::beginTransaction();
        try {
            $task = Task::findOrFail($req->id);
            if ($task->team) {
                return response()->json(['status' => 2, 'error' => __('This task already connected to a team')]);
            }
            if (!$task->driver) {
                return response()->json(['status' => 2, 'error' => __('This task not assign to driver')]);
            }

            if ($task->driver->team_id) {
                $task->team_id = $task->driver->team_id;
                $task->save();
                if ($task->closed) {
                    $transaction = Wallet_Transaction::where('task_id', $task->id)->where('transaction_type', 'credit')->first();
                    if ($transaction) {
                        Team_Wallet_Transaction::create([
                          'amount'              => $transaction->amount,
                          'description'         => $transaction->description . ' Driver: ' . $task->driver->name,
                          'transaction_type'    => 'credit',
                          'team_wallet_id'      => $task->driver->team->teamWallet->id,
                          'task_id'           => $task->id

                        ]);
                    }
                    $transaction->team_id =  $task->driver->team_id;
                    $transaction->save();
                }
                DB::commit();
                return response()->json(['status' => 1, 'success' => __('conected to team seccessfully')]);
            }

            return response()->json(['status' => 2, 'error' => __('the driver is not connected to a team ')]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    /**
     * Send email notifications for task assignment
     *
     * @param Task $task
     * @param Driver $driver
     * @return void
     */
    private function sendTaskAssignmentNotifications($task, $driver)
    {
        try {
            // إعداد بيانات المهمة المشتركة
            $taskData = [
              'Task ID' => $task->id,
              'Task Type' => 'Delivery Task',
              'Pickup Address' => $task->pickup ? $task->pickup->address : 'Not specified',
              'Delivery Address' => $task->delivery ? $task->delivery->address : 'Not specified',
              'Price' => $task->total_price - $task->commission . ' SAR',
              'Vehicle Size' => $task->vehicle_size ? $task->vehicle_size->type->vehicle->name . ' - ' . $task->vehicle_size->type->name . ' - (' . $task->vehicle_size->name . ')' : 'Not specified',
              'Assignment Date' => now()->format('Y-m-d H:i'),
              'Status' => 'Assigned'
            ];

            // إرسال إشعار للسائق
            $this->sendDriverAssignmentNotification($driver, $task, $taskData);
            $taskData['Price'] = $task->total_price . ' SAR';
            // إرسال إشعار لصاحب المهمة (Admin أو Customer)
            $this->sendTaskOwnerNotification($task, $driver, $taskData);
        } catch (Exception $e) {
            // تسجيل الخطأ دون إيقاف العملية الأساسية
            Log::error('Failed to send task assignment notifications', [
              'task_id' => $task->id,
              'driver_id' => $driver->id,
              'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send assignment notification to driver
     *
     * @param Driver $driver
     * @param Task $task
     * @param array $taskData
     * @return void
     */
    private function sendDriverAssignmentNotification($driver, $task, $taskData)
    {
        if (!$driver->email) {
            return;
        }

        $emailData = [
          'to' => $driver->email,
          'subject' => 'New Task Assigned to You - Task #' . $task->id,
          'template' => 'emails.task-assigned',
          'type' => 'task_assignment',
          'priority' => 'high',
          'user_name' => $driver->name,
          'action_url' => route('driver.task.show', $task->id),
          'action_text' => 'View Task Details',
          'additional_data' => array_merge($taskData, [
            'Driver Name' => $driver->name,
            'Driver Phone' => $driver->phone,
            'Instructions' => 'Please check the task details and contact the customer if needed.'
          ])
        ];

        dispatch(new SendEmailNotificationJob($emailData));
    }

    /**
     * Send notification to task owner (Admin or Customer)
     *
     * @param Task $task
     * @param Driver $driver
     * @param array $taskData
     * @return void
     */
    private function sendTaskOwnerNotification($task, $driver, $taskData)
    {
        $owner = null;
        $ownerEmail = null;
        $ownerName = null;
        $dashboardUrl = null;

        // تحديد صاحب المهمة
        if ($task->customer_id && $task->customer) {
            // المهمة تخص عميل
            $owner = $task->customer;
            $ownerEmail = $owner->email;
            $ownerName = $owner->name;
            $dashboardUrl = route('customer.tasks.show', $task->id);
        } elseif ($task->user_id && $task->user) {
            // المهمة تخص مدير
            $owner = $task->user;
            $ownerEmail = $owner->email;
            $ownerName = $owner->name;
            $dashboardUrl = route('task.show', $task->id);
        }

        if (!$ownerEmail) {
            return;
        }

        $emailData = [
          'to' => $ownerEmail,
          'subject' => 'Driver Assigned to Your Task #' . $task->id,
          'template' => 'emails.notification',
          'type' => 'task_assignment_owner',
          'priority' => 'normal',
          'user_name' => $ownerName,
          'content' => 'A driver has been assigned to your delivery task. The driver will contact you soon to coordinate the pickup and delivery.',
          'action_url' => $dashboardUrl,
          'action_text' => 'View Task Details',
          'additional_data' => array_merge($taskData, [
            'Assigned Driver' => $driver->name,
            'Driver Phone' => $driver->phone,
            'Driver Vehicle' => $driver->vehicle_size ? $driver->vehicle_size->name : 'Not specified',
            'Expected Contact' => 'The driver will contact you within 30 minutes',
            'Support Phone' => config('app.support_phone', 'Contact Support')
          ])
        ];

        dispatch(new SendEmailNotificationJob($emailData));
    }
}
