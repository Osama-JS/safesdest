<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\UserCommission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\UserWalletTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserCommissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_beneficiaries', ['only' => ['index', 'getData']]);
        $this->middleware('permission:manage_beneficiaries', ['only' => ['store', 'edit', 'destroy', 'changeStatus']]);
    }


    public function generateOldCommissions()
    {
        @ini_set('max_execution_time', 300);
        set_time_limit(300);

        try {
            // 1. جلب كافة إعدادات العمولات النشطة مع المستخدم ومحفظته مسبقاً
            $activeCommissions = UserCommission::where('status', true)
                ->with(['user.userWallet'])
                ->get();

            if ($activeCommissions->isEmpty()) {
                return response()->json([
                    'status' => 1,
                    'success' => __('No active user commissions found to generate.'),
                    'count' => 0,
                    'created_count' => 0,
                    'total_amount' => 0,
                ]);
            }

            // تجميع العمولات النشطة حسب معرف العميل customer_id
            $commissionsByCustomer = $activeCommissions->groupBy('customer_id');
            $eligibleCustomerIds = $commissionsByCustomer->keys()->filter()->all();

            if (empty($eligibleCustomerIds)) {
                return response()->json([
                    'status' => 1,
                    'success' => __('No eligible customers found with active commissions.'),
                    'count' => 0,
                    'created_count' => 0,
                    'total_amount' => 0,
                ]);
            }

            // 2. ضمان وجود محافظ لجميع المستخدمين المعنيين بالعمولات قبل بدء المعالجة
            $userWalletController = new UserWalletsController();
            foreach ($activeCommissions as $commission) {
                if ($commission->user && !$commission->user->userWallet) {
                    $wallet = $userWalletController->createWallet($commission->user->id, true);
                    $commission->user->setRelation('userWallet', $wallet);
                }
            }

            // 3. بناء استعلام المهام مع تصفية حصرية للمهام التابعة لهؤلاء العملاء فقط
            $tasksQuery = Task::whereIn('customer_id', $eligibleCustomerIds)
                ->where('status', 'completed')
                ->where('closed', 1)
                ->where('commission', '>', 0)
                ->with(['customer:id,name']);

            $totalTasksExamined = 0;
            $totalCommissionsCreated = 0;
            $totalAmountDistributed = 0.0;
            $authUserId = Auth::id() ?? 1;

            // 4. معالجة المهام بدفعات مجزأة لتفادي استهلاك الذاكرة
            $tasksQuery->chunkById(200, function ($tasks) use (
                $commissionsByCustomer,
                $authUserId,
                &$totalTasksExamined,
                &$totalCommissionsCreated,
                &$totalAmountDistributed
            ) {
                $taskIds = $tasks->pluck('id')->all();

                // فحص الحركات المسجلة مسبقاً لهذه الدفعة دفعة واحدة في استعلام فردي سريع
                $existingMap = UserWalletTransaction::whereIn('task_id', $taskIds)
                    ->where('transaction_type', 'credit')
                    ->where('description', 'LIKE', '%Commission from Task%')
                    ->get(['id', 'user_wallet_id', 'task_id'])
                    ->groupBy(fn($item) => "{$item->user_wallet_id}_{$item->task_id}");

                foreach ($tasks as $task) {
                    $totalTasksExamined++;
                    $userCommissions = $commissionsByCustomer->get($task->customer_id);

                    if (!$userCommissions || $userCommissions->isEmpty()) {
                        continue;
                    }

                    $totalCalculatedCommissions = 0;
                    $commissionsToDistribute = [];

                    foreach ($userCommissions as $userCommission) {
                        $calculatedCommission = $userCommission->calculateCommission($task->commission);
                        $totalCalculatedCommissions += $calculatedCommission;
                        $commissionsToDistribute[] = [
                            'user_commission' => $userCommission,
                            'amount' => $calculatedCommission,
                        ];
                    }

                    if ($totalCalculatedCommissions > $task->commission) {
                        Log::warning("User commissions total ({$totalCalculatedCommissions}) exceeds task commission ({$task->commission}) for task #{$task->id}");
                        continue;
                    }

                    $customerName = $task->customer?->name ?? 'Unknown';

                    foreach ($commissionsToDistribute as $commissionData) {
                        $userCommission = $commissionData['user_commission'];
                        $amount = $commissionData['amount'];
                        $user = $userCommission->user;

                        if (!$user || $amount <= 0) {
                            continue;
                        }

                        // التحقق من تاريخ بدء احتساب العمولات
                        if ($user->commission_start_date && $task->created_at && $task->created_at->startOfDay() < \Carbon\Carbon::parse($user->commission_start_date)->startOfDay()) {
                            continue;
                        }

                        $userWallet = $user->userWallet;
                        if (!$userWallet) {
                            continue;
                        }

                        $cacheKey = "{$userWallet->id}_{$task->id}";
                        if (isset($existingMap[$cacheKey])) {
                            continue;
                        }

                        // إنشاء حركة العمولة
                        UserWalletTransaction::create([
                            'user_wallet_id'   => $userWallet->id,
                            'amount'           => $amount,
                            'description'      => "Commission from Task: #{$task->id} - Customer: {$customerName}",
                            'transaction_type' => 'credit',
                            'task_id'          => $task->id,
                            'user_id'          => $authUserId,
                            'status'           => true,
                            'maturity_time'    => now(),
                        ]);

                        // حفظ المفتاح في الخريطة لمنع التكرار في نفس الدورة
                        $existingMap->put($cacheKey, collect([true]));

                        $totalCommissionsCreated++;
                        $totalAmountDistributed += $amount;
                    }
                }
            });

            return response()->json([
                'status' => 1,
                'success' => __('Old commissions generated successfully'),
                'count' => $totalTasksExamined,
                'created_count' => $totalCommissionsCreated,
                'total_amount' => round($totalAmountDistributed, 2),
            ]);
        } catch (Exception $e) {
            Log::error('Generate Old Commissions Error: ' . $e->getMessage());
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    private function calculateAndDistributeUserCommissions($task)
    {
        try {
            // التحقق من وجود عميل للمهمة
            if (!$task->customer_id) {
                return;
            }

            // جلب العمولات النشطة للعميل
            $userCommissions = UserCommission::where('customer_id', $task->customer_id)
                ->where('status', true)
                ->with('user')
                ->get();

            if ($userCommissions->isEmpty()) {
                return;
            }

            // التحقق من وجود عمولة في المهمة
            if ($task->commission <= 0) {
                return;
            }

            $totalCalculatedCommissions = 0;
            $commissionsToDistribute = [];

            // حساب إجمالي العمولات المطلوبة
            foreach ($userCommissions as $userCommission) {
                $calculatedCommission = $userCommission->calculateCommission($task->commission);
                $totalCalculatedCommissions += $calculatedCommission;

                $commissionsToDistribute[] = [
                    'user_commission' => $userCommission,
                    'amount' => $calculatedCommission
                ];
            }

            // التحقق من أن إجمالي العمولات لا يتجاوز عمولة المهمة
            if ($totalCalculatedCommissions > $task->commission) {
                Log::warning("User commissions total ({$totalCalculatedCommissions}) exceeds task commission ({$task->commission}) for task #{$task->id}");
                return;
            }

            // توزيع العمولات على المستخدمين
            foreach ($commissionsToDistribute as $commissionData) {
                $userCommission = $commissionData['user_commission'];
                $amount = $commissionData['amount'];
                $user = $userCommission->user;

                if (!$user) {
                    continue;
                }

                // التحقق من تاريخ بدء احتساب العمولات للمستخدم
                if ($user->commission_start_date && $task->created_at->startOfDay() < \Carbon\Carbon::parse($user->commission_start_date)->startOfDay()) {
                    Log::info("Skipping commission for user #{$user->id} on task #{$task->id}. Task created before user's commission_start_date.");
                    continue;
                }

                // إنشاء أو جلب محفظة المستخدم
                $userWallet = $user->userWallet;
                if (!$userWallet) {
                    $userWalletController = new UserWalletsController();
                    $userWallet = $userWalletController->createWallet($user->id, true);
                }

                // ✅ التحقق من أن المستخدم لم يستلم عمولته لهذه المهمة من قبل
                $alreadyReceived = UserWalletTransaction::where('user_wallet_id', $userWallet->id)
                    ->where('task_id', $task->id)
                    ->where('transaction_type', 'credit')
                    ->where('description', 'LIKE', '%Commission from Task%')
                    ->exists();

                if ($alreadyReceived) {
                    Log::info("User #{$user->id} has already received commission for task #{$task->id}, skipping...");
                    continue; // تجاوز المستخدم ولا تكرر العملية
                }

                // إضافة العمولة إلى محفظة المستخدم
                UserWalletTransaction::create([
                    'user_wallet_id' => $userWallet->id,
                    'amount' => $amount,
                    'description' => "Commission from Task: #{$task->id} - Customer: {$task->customer->name}",
                    'transaction_type' => 'credit',
                    'task_id' => $task->id,
                    'user_id' => Auth::user()->id,
                    'status' => true,
                    'maturity_time' => now(),
                ]);

                Log::info("Commission of {$amount} SAR added to user #{$user->id} wallet for task #{$task->id}");
            }

            Log::info("Successfully distributed {$totalCalculatedCommissions} SAR in commissions for task #{$task->id}");

        } catch (Exception $e) {
            Log::error("Error calculating user commissions for task #{$task->id}: " . $e->getMessage());
        }
    }


    /**
     * عرض صفحة إدارة العمولات
     */
    public function index()
    {
        $users = User::where('status', 'active')->where(function($q) {
            $q->where('investor', '!=', 1)->orWhereNull('investor');
        })->get();
        $customers = Customer::where('status', 'active')->get();

        $totalCommissions = UserCommission::count();
        $activeCommissions = UserCommission::where('status', true)->count();
        $inactiveCommissions = UserCommission::where('status', false)->count();

        return view('admin.commissions.index', [
            'users' => $users,
            'customers' => $customers,
            'totalCommissions' => $totalCommissions,
            'activeCommissions' => $activeCommissions,
            'inactiveCommissions' => $inactiveCommissions,
        ]);
    }

    /**
     * جلب بيانات العمولات للجدول
     */
    public function getData(Request $request)
    {
        $columns = [
            1 => 'id',
            2 => 'user_id',
            3 => 'customer_id',
            4 => 'commission_type',
            5 => 'commission_value',
            6 => 'status',
        ];

        $totalData = UserCommission::count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        $query = UserCommission::with(['user', 'customer']);

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            })->orWhereHas('customer', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            });

            $totalFiltered = $query->count();
        }

        $commissions = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        foreach ($commissions as $commission) {
            $nestedData = [];
            $nestedData['id'] = $commission->id;
            $nestedData['fake_id'] = $commission->id;
            $nestedData['user'] = $commission->user ? $commission->user->name : 'N/A';
            $nestedData['customer'] = $commission->customer ? $commission->customer->name : 'N/A';
            $nestedData['commission_type'] = $commission->commission_type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت';
            $nestedData['commission_value'] = $commission->commission_value . ($commission->commission_type === 'percentage' ? '%' : ' ريال');
            $nestedData['status'] = $commission->status;
            $nestedData['created_at'] = $commission->created_at->format('Y-m-d H:i');
            $data[] = $nestedData;
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
            'summary' => [
                'edit_permission' => auth()->user()->can('manage_user_commissions'),
                'delete_permission' => auth()->user()->can('manage_user_commissions'),
            ]
        ]);
    }

    /**
     * حفظ أو تحديث عمولة
     */
    public function store(Request $request)
    {
        // Handle multiple commissions from customer modal
        if ($request->has('commissions')) {
            return $this->storeMultipleCommissions($request);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'customer_id' => 'required|exists:customers,id',
            'commission_type' => 'required|in:fixed,percentage',
            'commission_value' => 'required|numeric|min:0',
        ], [
            'user_id.required' => __('Please select a user'),
            'user_id.exists' => __('Selected user does not exist'),
            'customer_id.required' => __('Please select a customer'),
            'customer_id.exists' => __('Selected customer does not exist'),
            'commission_type.required' => __('Please select commission type'),
            'commission_type.in' => __('Invalid commission type'),
            'commission_value.required' => __('Please enter commission value'),
            'commission_value.numeric' => __('Commission value must be a number'),
            'commission_value.min' => __('Commission value must be greater than 0'),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 2, 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $data = [
                'user_id' => $request->user_id,
                'customer_id' => $request->customer_id,
                'commission_type' => $request->commission_type,
                'commission_value' => $request->commission_value,
                'status' => $request->has('status') ? true : false,
            ];

            if ($request->filled('id')) {
                // تحديث عمولة موجودة
                $commission = UserCommission::findOrFail($request->id);
                $commission->update($data);
                $message = __('Commission updated successfully');
            } else {
                // التحقق من عدم وجود عمولة مكررة
                $exists = UserCommission::where('user_id', $request->user_id)
                    ->where('customer_id', $request->customer_id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'status' => 2,
                        'error' => __('Commission already exists for this user and customer')
                    ]);
                }

                // إنشاء عمولة جديدة
                UserCommission::create($data);
                $message = __('Commission created successfully');
            }

            DB::commit();
            return response()->json(['status' => 1, 'success' => $message]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    /**
     * جلب بيانات عمولة للتعديل
     */
    public function edit($id)
    {
        try {
            $commission = UserCommission::with(['user', 'customer'])->findOrFail($id);
            return response()->json($commission);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => __('Commission not found')]);
        }
    }

    /**
     * حذف عمولة
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:user_commissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 2, 'error' => __('Invalid commission ID')]);
        }

        try {
            $commission = UserCommission::findOrFail($request->id);
            $commission->delete();

            return response()->json(['status' => 1, 'success' => __('Commission deleted successfully')]);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    /**
     * تغيير حالة العمولة
     */
    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:user_commissions,id',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 2, 'error' => __('Invalid data')]);
        }

        try {
            $commission = UserCommission::findOrFail($request->id);
            $commission->update(['status' => $request->status]);

            $message = $request->status ? __('Commission activated successfully') : __('Commission deactivated successfully');
            return response()->json(['status' => 1, 'success' => $message]);

        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    /**
     * جلب العمولات حسب العميل
     */
    public function getCommissionsByCustomer($customerId)
    {
        try {
            $commissions = UserCommission::with('user')
                ->where('customer_id', $customerId)
                ->where('status', true)
                ->get();

            return response()->json(['status' => 1, 'data' => $commissions]);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Store multiple commissions from customer modal
     */
    private function storeMultipleCommissions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'commissions' => 'required|array',
                'commissions.*.user_id' => 'required|exists:users,id',
                'commissions.*.commission_type' => 'required|in:fixed,percentage',
                'commissions.*.commission_value' => 'required|numeric|min:0',
                'commissions.*.id' => 'nullable|exists:user_commissions,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 2,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ]);
            }

            $customerId = $request->customer_id;
            $commissions = $request->commissions;

            DB::beginTransaction();

            // Get existing commission IDs to track which ones to keep
            $existingIds = collect($commissions)->pluck('id')->filter()->toArray();

            // Delete commissions that are no longer in the list
            UserCommission::where('customer_id', $customerId)
                ->when(!empty($existingIds), function ($query) use ($existingIds) {
                    $query->whereNotIn('id', $existingIds);
                })
                ->when(empty($existingIds), function ($query) {
                    // If no existing IDs, delete all commissions for this customer
                    $query->where('id', '>', 0);
                })
                ->delete();

            // Process each commission
            foreach ($commissions as $commissionData) {
                $data = [
                    'user_id' => $commissionData['user_id'],
                    'customer_id' => $customerId,
                    'commission_type' => $commissionData['commission_type'],
                    'commission_value' => $commissionData['commission_value'],
                    'status' => true // Default to active
                ];

                if (!empty($commissionData['id'])) {
                    // Update existing commission
                    $commission = UserCommission::find($commissionData['id']);
                    if ($commission) {
                        $commission->update($data);
                    }
                } else {
                    // Check for duplicate user-customer pair
                    $existingCommission = UserCommission::where('user_id', $data['user_id'])
                        ->where('customer_id', $customerId)
                        ->first();

                    if (!$existingCommission) {
                        UserCommission::create($data);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'success' => __('Commissions updated successfully')
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 2,
                'error' => $e->getMessage()
            ]);
        }
    }
}
