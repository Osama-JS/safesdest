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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomerTasksExportForCustomer;
use App\Services\PdfService;

class TasksController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function index()
    {
        return view('customers.tasks.index');
    }


    public function getData(Request $request)
    {
        $columns = [
          1 => 'id',
          2 => 'id',
          3 => 'total_price',
          4 => 'pickup_address',
          5 => 'delivery_address',
          6 => 'driver_name',
          7 => 'vehicle_info',
          8 => 'status',
          9 => 'created_at'
        ];

        $totalData = Task::where('customer_id', auth()->user()->id)->count();
        $limit     = $request->input('length');
        $start     = $request->input('start');
        $order     = $columns[$request->input('order.0.column')] ?? 'id';
        $dir       = $request->input('order.0.dir') ?? 'desc';

        $fromDate  = $request->input('from_date');
        $toDate    = $request->input('to_date');
        $status    = $request->input('status');
        $search    = $request->input('search_term');


        $query = Task::with(['driver', 'pickup', 'delivery', 'vehicle_size.type.vehicle'])
          ->where('customer_id', auth()->user()->id);


        // فلترة بالتاريخ
        if ($fromDate && $toDate) {
            dd($query->get());

            $query->whereBetween('created_at', [
              Carbon::parse($fromDate)->startOfDay(),
              Carbon::parse($toDate)->endOfDay()
            ]);

        }



        // فلترة بالحالة
        if ($search) {
            $query->where('id', 'LIKE', '%' . $search . '%');
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('pickup', function ($q) use ($search) {
                      $q->where('address', 'like', "%{$search}%");
                  })
                  ->orWhereHas('delivery', function ($q) use ($search) {
                      $q->where('address', 'like', "%{$search}%");
                  })
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }


        if ($status) {
            $query->where('status', $status);
        }


        $totalFiltered = $query->count();

        $tasks = $query
          ->offset($start)
          ->limit($limit)
          ->orderBy($order, $dir)
          ->get();

        $data = [];
        $fakeId = $start;

        foreach ($tasks as $task) {
            // تحديد معلومات المركبة
            $vehicleInfo = '';
            if ($task->vehicle_size && $task->vehicle_size->type && $task->vehicle_size->type->vehicle) {
                $vehicleInfo = $task->vehicle_size->type->vehicle->name . '-' . $task->vehicle_size->type->name . ' - ' . $task->vehicle_size->name;
            }

            // تحديد إمكانية التتبع
            $canTrack = !$task->closed && in_array($task->status, [
              'assign',
              'started',
              'in pickup point',
              'loading',
              'in the way',
              'in delivery point',
              'unloading'
            ]);

            $data[] = [
              'id'               => $task->id,
              'customer_task_number' => $task->customer_task_number,
              'fake_id'          => ++$fakeId,
              'pickup_address'   => $task->pickup->address ?? 'N/A',
              'delivery_address' => $task->delivery->address ?? 'N/A',
              'driver'           => $task->driver ?? null,
              'whatsapp' => ($task->driver && !empty($task->driver->full_whatsapp_number))
                ? preg_replace('/[+\s-]/', '', $task->driver->full_whatsapp_number)
                : 'Not provided',
              'vehicle_info'     => $vehicleInfo,
              'total_price'      => $task->total_price ? number_format($task->total_price, 2) . ' SAR' : 'N/A',
              'status'           => $task->status,
              'created_at'       => $task->created_at->format('Y-m-d H:i'),
              'can_track'        => $canTrack,
              'closed'           => $task->closed,
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

    public function show($id)
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
          'formTemplate.fields',
          'pricingTemplate',
          'vehicle_size.type.vehicle',
          'history.user',
          'history.driver',
        ])->findOrFail($id);

        if ($task->customer_id !== Auth::user()->id) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        return view('customers.tasks.show', compact('task'));
    }

    public function track($id)
    {
        $task = Task::with([
          'pickup',
          'delivery',
          'driver',
          'history' => function ($query) {
              $query->orderBy('created_at', 'desc');
          }
        ])->where('customer_id', Auth::user()->id)->findOrFail($id);

        // Check if task can be tracked
        if ($task->closed || !in_array($task->status, ['assign', 'started', 'in pickup point', 'loading', 'in the way', 'in delivery point', 'unloading'])) {
            return redirect()->route('customer.tasks.show', $id)->with('error', 'This task cannot be tracked.');
        }

        return view('customers.tasks.track', compact('task'));
    }




    // public function downloadTaskInvoice($id)
    // {
    //   Log::info('downloadTaskInvoice Start');
    //     try {
    //         $task = Task::with([
    //             'customer',
    //             'pickup',
    //             'delivery',
    //             'vehicle_size.type.vehicle'
    //         ])
    //             ->where('customer_id', Auth::user()->id)
    //             ->where('id', $id)
    //             ->firstOrFail();

    //         // if ($task->payment_status !== 'paid' && $task->payment_status !== 'completed' && $task->status !== 'completed') {
    //         //      if (request()->ajax()) {
    //         //         return response()->json(['error' => __('Invoice is not available yet.')], 403);
    //         //      }
    //         //      return redirect()->back()->with('error', __('Invoice is not available yet.'));
    //         // }

    //         Log::info('downloadTaskInvoice going to generate pdf');

    //         $invoice_number = 'INV-' . str_pad($task->id, 6, '0', STR_PAD_LEFT);
    //         $file_name = "{$invoice_number}_{$task->customer->name}";
    //         Log::info('downloadTaskInvoice going to generate pdf');

    //         // Generate raw PDF content
    //         $pdfContent = $this->pdfService->generateRaw('admin.tasks.invoice_pdf', [
    //             'task' => $task,
    //             'invoice_number' => $invoice_number,
    //             'invoice_date' => now()
    //         ]);

    //         return response($pdfContent)
    //             ->header('Content-Type', 'application/pdf')
    //             ->header('Content-Disposition', "attachment; filename=\"{$file_name}.pdf\"")
    //             ->header('Content-Length', strlen($pdfContent));

    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         Log::info('downloadTaskInvoice ModelNotFoundException');
    //         if (request()->ajax()) {
    //             return response()->json(['error' => __('Task not found.')], 404);
    //         }
    //         return redirect()->back()->with('error', __('Task not found.'));
    //     } catch (Exception $e) {
    //         Log::info('downloadTaskInvoice Exception: '. $e->getMessage());
    //         if (request()->ajax()) {
    //             return response()->json(['error' => __('Failed to generate invoice.')], 500);
    //         }
    //         return redirect()->back()->with('error', __('Failed to generate invoice.'));
    //     }
    // }

    public function downloadTaskInvoice($id, Request $request)
    {
      Log::info('downloadTaskInvoice Start');
        try {
            // Manual Authentication for file download (supports query param token)
            $user = Auth::user();

            if (!$user) {
                // Try getting token from query string
                $token = $request->query('token');
                if ($token) {
                    $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                    if ($accessToken && $accessToken->tokenable) {
                        $user = $accessToken->tokenable;
                    }
                }
            }

            Log::info('downloadTaskInvoice user: '. $user->id);

            if (!$user) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $task = Task::with([
                'customer',
                'pickup',
                'delivery',
                'vehicle_size.type.vehicle'
            ])
                ->where('customer_id', $user->id)
                ->where('id', $id)
                ->where('id', $id)
                ->firstOrFail();

            if ($task->payment_status !== 'completed') {
                 if (request()->ajax()) {
                    return response()->json(['error' => __('Invoice is not available until payment is completed.')], 403);
                 }
                 return redirect()->back()->with('error', __('Invoice is not available until payment is completed.'));
            }

            $invoice_number = 'INV-' . str_pad($task->id, 6, '0', STR_PAD_LEFT);
            $file_name = "{$invoice_number}_{$task->customer->name}";

            return $this->pdfService->generate('admin.tasks.invoice_pdf', [
                'task' => $task,
                'invoice_number' => $invoice_number,
                'invoice_date' => now()
            ], "{$file_name}.pdf", true);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Task not found or you do not have permission to view it'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to generate invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadTaskPolicy($id, Request $request)
    {
        Log::info('downloadTaskPolicy Start');
        try {
            $user = Auth::user();

            if (!$user) {
                return redirect()->route('login');
            }

            Log::info('downloadTaskPolicy user: '. $user->id);

            $task = Task::with([
                'customer',
                'pickup',
                'delivery',
                'vehicle_size.type.vehicle',
                'driver',
                'team'
            ])
                ->where('customer_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            $file_name = "Policy-{$task->id}.pdf";

            return $this->pdfService->generate('admin.tasks.report_pdf', [
                'task' => $task
            ], $file_name, true);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', __('Task not found or you do not have permission to view it.'));
        } catch (Exception $e) {
            Log::error('Failed to generate policy: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to generate policy.'));
        }
    }

    public function printCustomPolicy($id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $task = Task::with(['customer', 'pickup', 'delivery', 'vehicle_size', 'user', 'driver'])
            ->where('customer_id', $user->id)
            ->findOrFail($id);

        if (!$task->customer || empty($task->customer->policy_file_name)) {
            \Illuminate\Support\Facades\Log::info('PrintCustomPolicy: No customer or policy file name', ['customer_id' => $task->customer_id ?? 'null', 'policy' => $task->customer?->policy_file_name]);
            return redirect()->back()->with('error', __('This task does not belong to a customer with a custom policy.'));
        }

        $viewName = str_replace(['.blade.php', '.blade', '.php'], '', $task->customer->policy_file_name);
        \Illuminate\Support\Facades\Log::info('PrintCustomPolicy: Checking view', ['original' => $task->customer->policy_file_name, 'sanitized' => $viewName]);

        // Ensure the view exists
        if (!\Illuminate\Support\Facades\View::exists($viewName)) {
             // Try with admin.tasks prefix if absolute path fails
             if (\Illuminate\Support\Facades\View::exists('admin.tasks.' . $viewName)) {
                 $viewName = 'admin.tasks.' . $viewName;
             } else {
                 \Illuminate\Support\Facades\Log::error('Custom policy view not found', ['viewName' => $viewName, 'prefixed' => 'admin.tasks.' . $viewName]);
                 return redirect()->back()->with('error', __('Custom policy template not found.') . ' (' . $viewName . ')');
             }
        }

        $customerName = $task->customer->company_name ?? $task->customer->name ?? 'customer';
        $file_name = "Shipping_Policy_{$task->id}_" . Str::slug($customerName, '_') . ".pdf";

        $customerLogo = null;
        if ($task->customer && $task->customer->image) {
            $cleanPath = preg_replace('/^storage\//', '', $task->customer->image);
            $possiblePaths = [
                public_path('storage/' . $cleanPath),
                storage_path('app/public/' . $cleanPath),
                storage_path('app/' . $cleanPath),
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($path) && is_file($path)) {
                    $customerLogo = $path;
                    break;
                }
            }
        }

        return $this->pdfService->generate($viewName, [
            'task' => $task,
            'watermark_image' => $customerLogo
        ], $file_name, true);
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
              'vehicle_size_id'  => $taskData['vehicles'][0],
              'conditions'       => $req->conditions
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

    public function update(Request $req, CustomerTaskPricingService $pricingService)
    {

        $oldTask = Task::findOrFail($req->id);
        $user = auth()->user();
        if (!$user || $user->id !== $oldTask->customer_id) {
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

            $task_template = Settings::where('key', 'task_template')->first();
            $template_id = $task_template->value;

            $task = [
              'total_price'      => $data['total_price'] ?? 0,
              'form_template_id' =>  $template_id,
              'pricing_id'       => $taskData['pricing'],
              'vehicle_size_id' => $taskData['vehicles'][0],
              'conditions'       => $req->conditions

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
                ];

                $task['driver_id'] = null;
            }


            $oldAdditionalData = $oldTask->additional_data ?? [];
            $structuredFields  = [];
            $filesToDelete     = [];

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

    public function generateReport(Request $request)
    {
        try {
            $customer = Auth::user();

            // Build query with filters
            $query = Task::with(['driver', 'pickup', 'delivery', 'vehicle_size.type.vehicle', 'payments'])
              ->where('customer_id', $customer->id);

            // Apply date filter
            if ($request->from_date && $request->to_date) {
                $query->whereBetween('created_at', [
                  Carbon::parse($request->from_date)->startOfDay(),
                  Carbon::parse($request->to_date)->endOfDay()
                ]);
            }

            // Apply status filter (multiple values)
            if ($request->has('status') && is_array($request->status) && !empty($request->status)) {
                $query->whereIn('status', $request->status);
            }

            // Apply payment status filter (multiple values)
            if ($request->has('payment_status') && is_array($request->payment_status) && !empty($request->payment_status)) {
                $query->whereIn('payment_status', $request->payment_status);
            }

            // Apply payment method filter (multiple values)
            if ($request->has('payment_method') && is_array($request->payment_method) && !empty($request->payment_method)) {
                $query->whereIn('payment_method', $request->payment_method);
            }

            $tasks = $query->orderBy('created_at', 'desc')->get();

            // Calculate statistics
            $statistics = [
              'total_tasks' => $tasks->count(),
              'completed_tasks' => $tasks->where('status', 'completed')->count(),
              'active_tasks' => $tasks->whereIn('status', [
                'in_progress', 'assign', 'started', 'in pickup point',
                'loading', 'in the way', 'in delivery point', 'unloading','invoiced'
              ])->count(),
              'canceled_tasks' => $tasks->whereIn('status', ['canceled','refund'])->count(),
              'total_amount' => $tasks->whereNotIn('status', ['canceled','refund'])->sum('total_price'),
              'paid_amount' => $tasks->where('payment_status', 'completed')->sum('total_price'),
              'pending_amount' => $tasks->where('payment_status', 'pending')->sum('total_price'),
            ];

            // Generate PDF
            return $this->generateTasksPDF($customer, $tasks, $statistics, $request);

        } catch (Exception $e) {
            return response()->json([
              'status' => 2,
              'error' => 'Error generating report: ' . $e->getMessage()
            ]);
        }
    }

    private function generateTasksPDF($customer, $tasks, $statistics, $request)
    {
        $fromDate = $request->from_date ? Carbon::parse($request->from_date)->format('d/m/Y') : 'غير محدد';
        $toDate = $request->to_date ? Carbon::parse($request->to_date)->format('d/m/Y') : 'غير محدد';
        $reportDate = Carbon::now()->format('d/m/Y H:i');

        $statusLabels = [
          'advertised' => 'معلن',
          'in_progress' => 'قيد التنفيذ',
          'assign' => 'مُعين',
          'started' => 'بدأت',
          'in pickup point' => 'في نقطة الاستلام',
          'loading' => 'جاري التحميل',
          'in the way' => 'في الطريق',
          'in delivery point' => 'في نقطة التسليم',
          'unloading' => 'جاري التفريغ',
          'completed' => 'مكتملة',
          'canceled' => 'ملغية',
          'refund' => 'مرتجع',
          'invoiced' => 'مفوتر'
        ];

        $paymentStatusLabels = [
          'waiting' => 'في الانتظار',
          'pending' => 'معلق',
          'completed' => 'مكتمل'
        ];

        $paymentMethodLabels = [
          'cash' => 'نقدي',
          'credit' => 'بطاقة ائتمان',
          'banking' => 'تحويل بنكي',
          'wallet' => 'محفظة'
        ];

        $html = $this->buildReportHTML($customer, $tasks, $statistics, [
          'from_date' => $fromDate,
          'to_date' => $toDate,
          'report_date' => $reportDate,
          'status_labels' => $statusLabels,
          'payment_status_labels' => $paymentStatusLabels,
          'payment_method_labels' => $paymentMethodLabels,
          'filters' => $request->all()
        ]);

        // Return HTML for now (we'll add PDF generation later)
        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    private function buildReportHTML($customer, $tasks, $statistics, $data)
    {
        return view('customers.tasks.report', compact('customer', 'tasks', 'statistics', 'data'))->render();
    }

    /**
     * Export tasks to Excel
     */
    public function exportToExcel(Request $request)
    {
        try {
            $customer = Auth::user();

            // Build query with filters (same as generateReport)
            $query = Task::with(['driver', 'driver.team', 'pickup', 'delivery', 'vehicle_size.type.vehicle', 'payments'])
                ->where('customer_id', $customer->id);

            // Apply date filter
            if ($request->from_date && $request->to_date) {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->from_date)->startOfDay(),
                    Carbon::parse($request->to_date)->endOfDay()
                ]);
            }

            // Apply status filter (multiple values)
            if ($request->has('status') && is_array($request->status) && !empty($request->status)) {
                $query->whereIn('status', $request->status);
            }

            // Apply payment status filter (multiple values)
            if ($request->has('payment_status') && is_array($request->payment_status) && !empty($request->payment_status)) {
                $query->whereIn('payment_status', $request->payment_status);
            }

            // Apply payment method filter (multiple values)
            if ($request->has('payment_method') && is_array($request->payment_method) && !empty($request->payment_method)) {
                $query->whereIn('payment_method', $request->payment_method);
            }

            $tasks = $query->orderBy('created_at', 'desc')->get();

            // Prepare report data
            $reportData = $this->prepareReportData($tasks, $customer, $request);

            // Generate Excel file
            $filename = 'customer_tasks_report_' . $customer->id . '_' . date('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(
                new CustomerTasksExportForCustomer($reportData, $request->all(), $customer),
                $filename
            );

        } catch (Exception $e) {
            return response()->json([
                'status' => 2,
                'error' => 'خطأ في إنشاء التقرير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare report data for Excel export
     */
    private function prepareReportData($tasks, $customer, $request)
    {
        // Process tasks data
        $processedTasks = $tasks->map(function ($task) {
            // Get effective price (0 for refunded/cancelled)
            $effectivePrice = $this->getEffectivePrice($task);

            return [
                'id' => $task->id,
                'total_price' => $effectivePrice,
                'original_price' => $task->total_price,
                'pickup_address' => $task->pickup->address ?? 'غير محدد',
                'pickup_contact_name' => $task->pickup->contact_name ?? '',
                'pickup_contact_phone' => $task->pickup->contact_phone ?? '',
                'delivery_address' => $task->delivery->address ?? 'غير محدد',
                'delivery_contact_name' => $task->delivery->contact_name ?? '',
                'delivery_contact_phone' => $task->delivery->contact_phone ?? '',
                'vehicle_name' => $this->getVehicleName($task),
                'driver_name' => $task->driver->name ?? 'غير محدد',
                'driver_phone' => $task->driver->phone ?? '',
                'team_name' => $task->driver->team->name ?? 'غير محدد',
                'status' => $task->status,
                'status_ar' => $this->getStatusInArabic($task->status),
                'payment_status' => $task->payment_status,
                'payment_status_ar' => $this->getPaymentStatusInArabic($task->payment_status),
                'payment_method' => $task->payment_method,
                'payment_method_ar' => $this->getEffectivePaymentMethod($task),
                'created_at_formatted' => $task->created_at ? $task->created_at->format('Y-m-d H:i') : '',
                'completed_at_formatted' => $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : '',
                'closed_at_formatted' => $task->closed_at ? $task->closed_at->format('Y-m-d H:i') : '',
            ];
        })->toArray();

        // Calculate summary
        $summary = $this->calculateSummary($tasks);

        return [
            'tasks' => $processedTasks,
            'summary' => $summary,
        ];
    }

    /**
     * Get effective price (0 for refunded/cancelled tasks)
     */
    private function getEffectivePrice($task)
    {
        if (in_array($task->status, ['refund', 'canceled', 'cancelled'])) {
            return 0;
        }
        return $task->total_price;
    }

    /**
     * Get vehicle name
     */
    private function getVehicleName($task)
    {
        if ($task->vehicle_size_id && $task->vehicle_size) {
            return $task->vehicle_size->type->vehicle->name . ' - ' .
                   $task->vehicle_size->type->name . ' - ' .
                   $task->vehicle_size->name;
        }
        return 'غير محدد';
    }

    /**
     * Get status in Arabic
     */
    private function getStatusInArabic($status)
    {
        $statuses = [
            'in_progress' => 'قيد التنفيذ',
            'advertised' => 'معلن عنها',
            'assign' => 'مُعيّنة',
            'started' => 'بدأت',
            'in pickup point' => 'في نقطة الاستلام',
            'loading' => 'جاري التحميل',
            'in the way' => 'في الطريق',
            'in delivery point' => 'في نقطة التسليم',
            'unloading' => 'جاري التفريغ',
            'invoiced' => 'مفوتر',
            'completed' => 'مكتملة',
            'canceled' => 'ملغية',
            'refund' => 'مرتجعة'
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Get payment status in Arabic
     */
    private function getPaymentStatusInArabic($status)
    {
        $statuses = [
            'waiting' => 'في الانتظار',
            'completed' => 'مكتمل',
            'pending' => 'معلق'
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Get effective payment method (empty for incomplete payments)
     */
    private function getEffectivePaymentMethod($task)
    {
        if (in_array($task->payment_status, ['pending', 'waiting'])) {
            return '';
        }

        return $this->getPaymentMethodInArabic($task->payment_method);
    }

    /**
     * Get payment method in Arabic
     */
    private function getPaymentMethodInArabic($method)
    {
        $methods = [
            'cash' => 'نقدي',
            'credit' => 'ائتمان',
            'credit_card' => 'بطاقة ائتمان',
            'bank_transfer' => 'تحويل بنكي',
            'banking' => 'تحويل بنكي',
            'wallet' => 'محفظة إلكترونية'
        ];

        return $methods[$method] ?? $method;
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary($tasks)
    {
        $totalTasks = $tasks->count();

        // Calculate effective amounts (excluding refunded/cancelled)
        $effectiveAmounts = $tasks->map(function ($task) {
            return $this->getEffectivePrice($task);
        });

        $totalAmount = $effectiveAmounts->sum();
        $paidAmount = $tasks->where('payment_status', 'completed')->sum(function ($task) {
            return $this->getEffectivePrice($task);
        });
        $pendingAmount = $tasks->where('payment_status', 'pending')->sum(function ($task) {
            return $this->getEffectivePrice($task);
        });

        // Status breakdown
        $statusBreakdown = [];
        $statusCounts = $tasks->groupBy('status');
        foreach ($statusCounts as $status => $group) {
            $statusBreakdown[$this->getStatusInArabic($status)] = $group->count();
        }

        return [
            'total_tasks' => $totalTasks,
            'total_amount' => $totalAmount,
            'average_amount' => $totalTasks > 0 ? $totalAmount / $totalTasks : 0,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'status_breakdown' => $statusBreakdown,
        ];
    }
}
