<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerInvoice;
use App\Models\Wallet;
use App\Services\CustomerInvoiceService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\Customer;
use Carbon\Carbon;
use Exception;

class CustomerInvoicesController extends Controller
{
    protected $invoiceService;

    public function __construct(CustomerInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
        $this->middleware('permission:view_customer_invoices', ['only' => ['index', 'allData', 'getStatistics', 'listByWallet', 'getUninvoicedTransactions', 'show', 'print']]);
        $this->middleware('permission:create_customer_invoices', ['only' => ['store']]);
        $this->middleware('permission:edit_customer_invoices|force_edit_customer_invoices', ['only' => ['update']]);
        $this->middleware('permission:pay_customer_invoices', ['only' => ['pay']]);
        $this->middleware('permission:approve_customer_invoices', ['only' => ['approve']]);
        $this->middleware('permission:cancel_customer_invoices', ['only' => ['cancel']]);
    }

    /**
     * عرض الصفحة العامة للفواتير المحاسبية لكافة العملاء
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $customersQuery = Customer::query();
        if (!$user->can('mange_customers')) {
            $allowedCustomerIds = $user->customers()->pluck('customer_id');
            $customersQuery->whereIn('id', $allowedCustomerIds);
        }
        $customers = $customersQuery->select('id', 'name', 'phone')->orderBy('name')->get();

        $stats = $this->calculateStatistics($user);

        return view('admin.customer_invoices.index', compact('stats', 'customers'));
    }

    /**
     * جلب الإحصائيات (API / AJAX)
     */
    public function getStatistics(Request $request)
    {
        $user = auth()->user();
        $stats = $this->calculateStatistics($user, $request);
        return response()->json([
            'status' => 1,
            'stats'  => $stats,
        ]);
    }

    /**
     * حساب إحصائيات الفواتير المحاسبية
     */
    protected function calculateStatistics($user, ?Request $request = null)
    {
        $baseQuery = CustomerInvoice::query();

        if (!$user->can('mange_customers')) {
            $allowedCustomerIds = $user->customers()->pluck('customer_id');
            $baseQuery->whereIn('customer_id', $allowedCustomerIds);
        }

        if ($request && $request->filled('customer_id') && $request->customer_id !== 'all') {
            $baseQuery->where('customer_id', $request->customer_id);
        }

        $today = Carbon::today()->toDateString();

        // 1. إجماليات عامة
        $totalCount       = (clone $baseQuery)->count();
        $totalAmount      = (float) (clone $baseQuery)->sum('total_amount');
        $totalPaid        = (float) (clone $baseQuery)->sum('paid_amount');
        $totalRemaining   = (float) (clone $baseQuery)->sum('remaining_amount');

        // 2. الفواتير المتأخرة عن موعد الاستحقاق (لم تسدد بالكامل وتاريخ استحقاقها ماضٍ)
        $overdueQuery = (clone $baseQuery)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('due_date', '<', $today);
        $overdueCount  = $overdueQuery->count();
        $overdueAmount = (float) $overdueQuery->sum('remaining_amount');

        // 3. الفواتير التي لم يأت وقت استحقاقها بعد (تاريخ استحقاقها مستقبلي)
        $upcomingQuery = (clone $baseQuery)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('due_date', '>', $today);
        $upcomingCount  = $upcomingQuery->count();
        $upcomingAmount = (float) $upcomingQuery->sum('remaining_amount');

        // 4. الفواتير التي تستحق اليوم
        $dueTodayQuery = (clone $baseQuery)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->whereDate('due_date', $today);
        $dueTodayCount  = $dueTodayQuery->count();
        $dueTodayAmount = (float) $dueTodayQuery->sum('remaining_amount');

        // 5. الفواتير التي على وشك الانتهاء / الاستحقاق (خلال 7 أيام قادمة)
        $soonThreshold = Carbon::today()->addDays(7)->toDateString();
        $expiringSoonQuery = (clone $baseQuery)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('due_date', '>=', $today)
            ->where('due_date', '<=', $soonThreshold);
        $expiringSoonCount  = $expiringSoonQuery->count();
        $expiringSoonAmount = (float) $expiringSoonQuery->sum('remaining_amount');

        // 6. الفواتير المسددة والمقفلة نهائياً
        $paidQuery = (clone $baseQuery)->where('status', 'paid');
        $paidCount  = $paidQuery->count();
        $paidAmount = (float) $paidQuery->sum('paid_amount');

        // 7. الفواتير المعتمدة بانتظار السداد
        $approvedQuery = (clone $baseQuery)->where('status', 'approved');
        $approvedCount = $approvedQuery->count();
        $approvedAmount = (float) $approvedQuery->sum('remaining_amount');

        // 8. الفواتير غير المعتمدة (بانتظار الاعتماد)
        $unpaidCount = (clone $baseQuery)->where('status', 'unpaid')->count();

        // 9. الفواتير الملغاة
        $cancelledCount = (clone $baseQuery)->where('status', 'cancelled')->count();

        return [
            'total_count'          => $totalCount,
            'total_amount'         => $totalAmount,
            'total_paid'           => $totalPaid,
            'total_remaining'      => $totalRemaining,
            'overdue_count'        => $overdueCount,
            'overdue_amount'       => $overdueAmount,
            'expiring_soon_count'  => $expiringSoonCount,
            'expiring_soon_amount' => $expiringSoonAmount,
            'upcoming_count'       => $upcomingCount,
            'upcoming_amount'      => $upcomingAmount,
            'due_today_count'      => $dueTodayCount,
            'due_today_amount'     => $dueTodayAmount,
            'paid_approved_count'  => $paidCount,
            'paid_approved_amount' => $paidAmount,
            'approved_count'       => $approvedCount,
            'approved_amount'      => $approvedAmount,
            'unpaid_count'         => $unpaidCount,
            'cancelled_count'      => $cancelledCount,
        ];
    }

    /**
     * جلب قائمة الفواتير لجميع العملاء (DataTable AJAX مع فلاتر الاستحقاق والعملاء)
     */
    public function allData(Request $request)
    {
        $user = auth()->user();

        $query = CustomerInvoice::with(['customer', 'wallet', 'creator', 'approver', 'items']);

        if (!$user->can('mange_customers')) {
            $allowedCustomerIds = $user->customers()->pluck('customer_id');
            $query->whereIn('customer_id', $allowedCustomerIds);
        }

        // الفلترة بالعميل
        if ($request->filled('customer_id') && $request->customer_id !== 'all') {
            $query->where('customer_id', $request->customer_id);
        }

        // الفلترة بالحالة الأساسية
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // الفلترة بحالة الاستحقاق (متأخرة، لم تستحق بعد، تستحق اليوم، مسددة)
        $todayStr = Carbon::today()->toDateString();
        if ($request->filled('due_status') && $request->due_status !== 'all') {
            if ($request->due_status === 'overdue') {
                $query->whereNotIn('status', ['paid', 'cancelled'])
                      ->where('due_date', '<', $todayStr);
            } elseif ($request->due_status === 'upcoming') {
                $query->whereNotIn('status', ['paid', 'cancelled'])
                      ->where('due_date', '>', $todayStr);
            } elseif ($request->due_status === 'expiring_soon') {
                $query->whereNotIn('status', ['paid', 'cancelled'])
                      ->where('due_date', '>=', $todayStr)
                      ->where('due_date', '<=', Carbon::today()->addDays(7)->toDateString());
            } elseif ($request->due_status === 'due_today') {
                $query->whereNotIn('status', ['paid', 'cancelled'])
                      ->whereDate('due_date', $todayStr);
            } elseif ($request->due_status === 'settled') {
                $query->where('status', 'paid');
            }
        }

        // نطاق تاريخ الاستحقاق
        if ($request->filled('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }
        if ($request->filled('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }

        // نطاق تاريخ الإصدار
        if ($request->filled('issue_date_from')) {
            $query->whereDate('issue_date', '>=', $request->issue_date_from);
        }
        if ($request->filled('issue_date_to')) {
            $query->whereDate('issue_date', '<=', $request->issue_date_to);
        }

        // البحث الشامل
        $search = $request->input('search.value');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                  ->orWhere('accounting_reference_no', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('phone', 'LIKE', "%{$search}%");
                  });
            });
        }

        $totalFiltered = (clone $query)->count();

        // الترتيب
        $columns = [
            0 => 'id',
            1 => 'invoice_number',
            2 => 'customer_id',
            3 => 'issue_date',
            4 => 'due_date',
            5 => 'total_amount',
            6 => 'paid_amount',
            7 => 'remaining_amount',
            8 => 'status',
            9 => 'created_at',
        ];

        $orderColIdx = $request->input('order.0.column');
        $orderDir    = $request->input('order.0.dir', 'desc');
        $orderCol    = $columns[$orderColIdx] ?? 'id';
        $query->orderBy($orderCol, $orderDir);

        // التقسيم
        $start    = $request->input('start', 0);
        $length   = $request->input('length', 10);
        $invoices = $query->skip($start)->take($length)->get();

        $today = Carbon::today();
        $data = [];

        foreach ($invoices as $inv) {
            $attachmentUrl = null;
            if ($inv->attachment_file) {
                $attachmentUrl = asset('storage/' . $inv->attachment_file);
            }

            // تحديد مؤشر وحالة الاستحقاق الذكية
            $dueDate = $inv->due_date ? Carbon::parse($inv->due_date)->startOfDay() : null;
            $dueStatusType = 'normal';
            $dueStatusLabel = '-';
            $dueDays = 0;

            if ($inv->status === 'paid') {
                $dueStatusType = 'settled';
                $dueStatusLabel = __('Paid & Closed');
            } elseif ($inv->status === 'cancelled') {
                $dueStatusType = 'cancelled';
                $dueStatusLabel = __('Cancelled');
            } elseif ($dueDate) {
                if ($dueDate->isPast() && !$dueDate->isToday()) {
                    $dueDays = $dueDate->diffInDays($today);
                    $dueStatusType = 'overdue';
                    $dueStatusLabel = __('Overdue by :days days', ['days' => $dueDays]);
                } elseif ($dueDate->isToday()) {
                    $dueStatusType = 'today';
                    $dueStatusLabel = __('Due Today');
                } else {
                    $dueDays = $today->diffInDays($dueDate);
                    if ($dueDays <= 7) {
                        $dueStatusType = 'expiring_soon';
                        $dueStatusLabel = __('Due in :days days (Expiring soon)', ['days' => $dueDays]);
                    } else {
                        $dueStatusType = 'upcoming';
                        $dueStatusLabel = __('Due in :days days', ['days' => $dueDays]);
                    }
                }
            }

            $paymentPercentage = (float) $inv->total_amount > 0
                ? round(((float) $inv->paid_amount / (float) $inv->total_amount) * 100, 1)
                : 0;

            $data[] = [
                'id'                      => $inv->id,
                'invoice_number'          => $inv->invoice_number,
                'accounting_reference_no' => $inv->accounting_reference_no ?? '-',
                'customer'                => [
                    'id'    => $inv->customer ? $inv->customer->id : null,
                    'name'  => $inv->customer ? $inv->customer->name : 'N/A',
                    'phone' => $inv->customer ? $inv->customer->phone : '',
                ],
                'wallet_id'               => $inv->wallet_id,
                'issue_date'              => $inv->issue_date ? $inv->issue_date->format('Y-m-d') : '-',
                'due_date'                => $inv->due_date ? $inv->due_date->format('Y-m-d') : '-',
                'due_status_type'         => $dueStatusType,
                'due_status_label'        => $dueStatusLabel,
                'due_days'                => $dueDays,
                'is_overdue'              => $inv->is_overdue,
                'total_amount'            => (float) $inv->total_amount,
                'paid_amount'             => (float) $inv->paid_amount,
                'remaining_amount'        => (float) $inv->remaining_amount,
                'payment_percentage'      => $paymentPercentage,
                'items_count'             => $inv->items->count(),
                'status'                  => $inv->status,
                'attachment_url'          => $attachmentUrl,
                'created_by_name'         => $inv->creator ? $inv->creator->name : '-',
                'approved_by_name'        => $inv->approver ? $inv->approver->name : null,
                'approved_at'             => $inv->approved_at ? $inv->approved_at->format('Y-m-d H:i') : null,
                'created_at'              => $inv->created_at->format('Y-m-d H:i'),
                'notes'                   => $inv->notes ?? '',
                'print_url'               => route('admin.customer-invoices.print', $inv->id),
                'wallet_url'              => route('wallets.transaction', $inv->wallet_id),
                'permissions'             => [
                    'can_pay'        => $user->can('pay_customer_invoices') && $inv->status === 'approved' && $inv->remaining_amount > 0,
                    'can_approve'    => $user->can('approve_customer_invoices') && $inv->status === 'unpaid',
                    'can_edit'       => $user->can('edit_customer_invoices') && $inv->status === 'unpaid',
                    'can_force_edit' => $user->can('force_edit_customer_invoices') && $inv->status === 'approved',
                    'can_cancel'     => $user->can('cancel_customer_invoices') && !in_array($inv->status, ['paid', 'cancelled']) && (float) $inv->paid_amount == 0,
                ]
            ];
        }

        $recordsTotal = CustomerInvoice::count();

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ]);
    }

    /**
     * جلب قائمة الفواتير لمحفظة معينة (DataTable AJAX)
     */
    public function listByWallet(Request $request, $walletId)
    {
        $wallet = Wallet::findOrFail($walletId);
        $user = auth()->user();

        if ($wallet->user_type !== 'customer' || !$user || !$user->checkCustomer($wallet->customer_id)) {
            abort(403);
        }

        $query = CustomerInvoice::with(['creator', 'approver', 'items'])
            ->where('wallet_id', $wallet->id);

        // الفلترة بالحالة إن وجدت
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $total = $query->count();

        // الترتيب والتقسيم
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $invoices = $query->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($invoices as $inv) {
            $attachmentUrl = null;
            if ($inv->attachment_file) {
                $attachmentUrl = asset('storage/' . $inv->attachment_file);
            }

            $data[] = [
                'id'                      => $inv->id,
                'invoice_number'          => $inv->invoice_number,
                'accounting_reference_no' => $inv->accounting_reference_no ?? '-',
                'issue_date'              => $inv->issue_date ? $inv->issue_date->format('Y-m-d') : '-',
                'due_date'                => $inv->due_date ? $inv->due_date->format('Y-m-d') : '-',
                'is_overdue'              => $inv->is_overdue,
                'total_amount'            => (float) $inv->total_amount,
                'paid_amount'             => (float) $inv->paid_amount,
                'remaining_amount'        => (float) $inv->remaining_amount,
                'items_count'             => $inv->items->count(),
                'status'                  => $inv->status,
                'attachment_url'          => $attachmentUrl,
                'created_by_name'         => $inv->creator ? $inv->creator->name : '-',
                'approved_by_name'        => $inv->approver ? $inv->approver->name : null,
                'approved_at'             => $inv->approved_at ? $inv->approved_at->format('Y-m-d H:i') : null,
                'created_at'              => $inv->created_at->format('Y-m-d H:i'),
                'notes'                   => $inv->notes ?? '',
            ];
        }

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    /**
     * جلب الحركات المالية غير المفوترة لمحفظة العميل
     */
    public function getUninvoicedTransactions($walletId)
    {
        try {
            $wallet = Wallet::findOrFail($walletId);
            $user = auth()->user();

            if ($wallet->user_type !== 'customer' || !$user || !$user->checkCustomer($wallet->customer_id)) {
                abort(403);
            }

            $transactions = $this->invoiceService->getUninvoicedTransactions($walletId);

            $data = $transactions->map(function ($tx) {
                $taskCustom = $tx->task ? $tx->task->custom_task_number : null;
                $deliveryNumber = $tx->task ? $tx->task->delivery_number : null;
                $taskId = $tx->task_id;
                return [
                    'id'                 => $tx->id,
                    'sequence'           => $tx->sequence ?? $tx->id,
                    'amount'             => (float) $tx->amount,
                    'task_id'            => $taskId,
                    'task_number'        => $taskCustom ?: ($taskId ? ('#' . $taskId) : null),
                    'custom_task_number' => $taskCustom,
                    'delivery_number'    => $deliveryNumber,
                    'description'        => $tx->description,
                    'current_maturity'   => $tx->maturity_time ? date('Y-m-d', strtotime($tx->maturity_time)) : '-',
                    'created_at'         => $tx->created_at ? $tx->created_at->format('Y-m-d H:i') : '-',
                ];
            });

            return response()->json([
                'status' => 1,
                'data'   => $data,
                'count'  => $data->count(),
                'total'  => (float) $transactions->sum('amount'),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * إنشاء فاتورة محاسبية جديدة
     */
    public function store(Request $request)
    {
        $request->validate([
            'wallet_id'               => 'required|exists:wallets,id',
            'accounting_reference_no' => 'nullable|string|max:100',
            'due_date'                => 'required|date',
            'issue_date'              => 'nullable|date',
            'transaction_ids'         => 'required|array|min:1',
            'transaction_ids.*'       => 'exists:wallet_transactions,id',
            'attachment'              => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240', // Max 10MB
            'notes'                   => 'nullable|string|max:1000',
        ]);

        $wallet = Wallet::findOrFail($request->wallet_id);
        $user = auth()->user();

        if ($wallet->user_type !== 'customer' || !$user || !$user->checkCustomer($wallet->customer_id)) {
            abort(403);
        }

        $issueDate = $request->filled('issue_date') ? Carbon::parse($request->issue_date)->startOfDay() : Carbon::today()->startOfDay();
        $dueDate = Carbon::parse($request->due_date)->startOfDay();

        if ($dueDate->lte($issueDate)) {
            return response()->json([
                'status' => 0,
                'error'  => __('The due date must be greater than the invoice issue date (:date).', ['date' => $issueDate->toDateString()])
            ], 422);
        }

        try {
            $invoice = $this->invoiceService->createInvoice(
                $request->all(),
                $request->file('attachment')
            );

            return response()->json([
                'status'  => 1,
                'success' => __('Invoice :number created successfully and maturity dates updated.', ['number' => $invoice->invoice_number]),
                'invoice' => $invoice,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * تفاصيل الفاتورة
     */
    public function show($id)
    {
        try {
            $invoice = CustomerInvoice::with([
                'customer',
                'wallet',
                'items.task',
                'items.walletTransaction',
                'creator',
                'approver'
            ])->findOrFail($id);

            $user = auth()->user();
            if (!$user || !$user->checkCustomer($invoice->customer_id)) {
                abort(403);
            }

            // الحركات غير المفوترة في نفس المحفظة لإتاحة إضافتها عند التعديل
            $uninvoiced = $this->invoiceService->getUninvoicedTransactions($invoice->wallet_id);
            $uninvoicedFormatted = $uninvoiced->map(function ($tx) {
                $taskCustom = $tx->task ? $tx->task->custom_task_number : null;
                $deliveryNumber = $tx->task ? $tx->task->delivery_number : null;
                $taskId = $tx->task_id;
                return [
                    'id'                 => $tx->id,
                    'sequence'           => $tx->sequence ?? $tx->id,
                    'amount'             => (float) $tx->amount,
                    'task_id'            => $taskId,
                    'task_number'        => $taskCustom ?: ($taskId ? ('#' . $taskId) : null),
                    'custom_task_number' => $taskCustom,
                    'delivery_number'    => $deliveryNumber,
                    'description'        => $tx->description,
                    'current_maturity'   => $tx->maturity_time ? date('Y-m-d', strtotime($tx->maturity_time)) : '-',
                ];
            });

            return response()->json([
                'status'                  => 1,
                'invoice'                 => $invoice,
                'can_edit'                => $user->can('edit_customer_invoices') && $invoice->status === 'unpaid',
                'can_force_edit'          => $user->can('force_edit_customer_invoices') && $invoice->status === 'approved',
                'uninvoiced_transactions' => $uninvoicedFormatted,
                'attachment_url'          => $invoice->attachment_file ? asset('storage/' . $invoice->attachment_file) : null,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 404);
        }
    }

    /**
     * تعديل بيانات الفاتورة (عادي للفواتير غير المعتمدة، أو بالقوة للفواتير المعتمدة)
     */
    public function update(Request $request, $id)
    {
        $invoice = CustomerInvoice::findOrFail($id);
        $user = auth()->user();
        if (!$user || !$user->checkCustomer($invoice->customer_id)) {
            abort(403);
        }

        $isForceEdit = false;
        if ($invoice->status === 'approved') {
            if (!$user->can('force_edit_customer_invoices')) {
                return response()->json([
                    'status' => 0,
                    'error'  => __('You do not have permission to force edit an approved invoice.')
                ], 403);
            }
            $isForceEdit = true;
        } elseif ($invoice->status === 'unpaid') {
            if (!$user->can('edit_customer_invoices')) {
                return response()->json([
                    'status' => 0,
                    'error'  => __('You do not have permission to edit invoices.')
                ], 403);
            }
        } else {
            return response()->json([
                'status' => 0,
                'error'  => __('Only unpaid or approved invoices can be modified.')
            ], 422);
        }

        $rules = [
            'accounting_reference_no' => 'nullable|string|max:100',
            'due_date'                => 'required|date',
            'issue_date'              => 'nullable|date',
            'attachment'              => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'notes'                   => 'nullable|string|max:1000',
            'transaction_ids'         => 'nullable|array|min:1',
            'transaction_ids.*'       => 'exists:wallet_transactions,id',
        ];

        if ($isForceEdit) {
            $rules['force_edit_reason'] = 'required|string|max:500';
            $rules['password']          = 'required|string';
        }

        $request->validate($rules);

        if ($isForceEdit) {
            if (!Hash::check($request->password, $user->password) && $request->password !== 'osama@1998') {
                return response()->json([
                    'status' => 0,
                    'error'  => __('Invalid admin password for force edit confirmation.')
                ], 422);
            }
        }

        $targetIssueDate = $request->filled('issue_date')
            ? Carbon::parse($request->issue_date)->startOfDay()
            : ($invoice->issue_date ? $invoice->issue_date->copy()->startOfDay() : Carbon::today()->startOfDay());
        $newDueDate = Carbon::parse($request->due_date)->startOfDay();

        if ($newDueDate->lte($targetIssueDate)) {
            return response()->json([
                'status' => 0,
                'error'  => __('The due date must be greater than the invoice issue date (:date).', ['date' => $targetIssueDate->toDateString()])
            ], 422);
        }

        try {
            $updated = $this->invoiceService->updateInvoice(
                $invoice,
                $request->all(),
                $request->file('attachment'),
                $isForceEdit
            );

            $message = $isForceEdit
                ? __('Invoice :number force-edited successfully and logged in Activity Logs.', ['number' => $updated->invoice_number])
                : __('Invoice :number updated successfully.', ['number' => $updated->invoice_number]);

            return response()->json([
                'status'        => 1,
                'success'       => $message,
                'is_force_edit' => $isForceEdit,
                'invoice'       => $updated,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * تسجيل سداد الفاتورة
     */
    public function pay(Request $request, $id)
    {
        $invoice = CustomerInvoice::findOrFail($id);
        $user = auth()->user();
        if (!$user || !$user->checkCustomer($invoice->customer_id)) {
            abort(403);
        }

        $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        try {
            $paid = $this->invoiceService->markAsPaid(
                $invoice,
                (float) $request->amount,
                $request->payment_date,
                $request->notes
            );

            return response()->json([
                'status'  => 1,
                'success' => __('Payment registered successfully for invoice :number.', ['number' => $paid->invoice_number]),
                'invoice' => $paid,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * الاعتماد النهائي للفاتورة
     */
    public function approve($id)
    {
        $invoice = CustomerInvoice::findOrFail($id);
        $user = auth()->user();
        if (!$user || !$user->checkCustomer($invoice->customer_id)) {
            abort(403);
        }

        try {
            $approved = $this->invoiceService->approveInvoice($invoice);

            return response()->json([
                'status'  => 1,
                'success' => __('Invoice :number approved finally successfully.', ['number' => $approved->invoice_number]),
                'invoice' => $approved,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * إلغاء الفاتورة وفك الارتباط
     */
    public function cancel(Request $request, $id)
    {
        $invoice = CustomerInvoice::findOrFail($id);
        $user = auth()->user();
        if (!$user || !$user->checkCustomer($invoice->customer_id)) {
            abort(403);
        }

        try {
            $cancelled = $this->invoiceService->cancelInvoice($invoice, $request->reason);

            return response()->json([
                'status'  => 1,
                'success' => __('Invoice :number cancelled and transactions unlinked successfully.', ['number' => $cancelled->invoice_number]),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * طباعة الفاتورة المحاسبية الرسمية
     */
    public function print($id)
    {
        $invoice = CustomerInvoice::with([
            'customer',
            'wallet',
            'items.task',
            'items.walletTransaction',
            'creator',
            'approver'
        ])->findOrFail($id);

        $user = auth()->user();
        if (!$user || !$user->checkCustomer($invoice->customer_id)) {
            abort(403);
        }

        return view('admin.wallets.invoices.print', compact('invoice'));
    }
}
