<?php

namespace App\Http\Controllers\investor;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\InvestorPaymentService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvestorTaskPaymentController extends Controller
{
    public function __construct(
        private InvestorPaymentService $paymentService,
        private PdfService $pdfService
    ) {}

    /**
     * عرض المهام المتاحة للدفع (للمستثمر بالمهام فقط)
     */
    public function index(Request $request)
    {
        $investor = auth()->user();
        $contract = $investor->activeInvestmentContract;

        if (!$contract || $contract->contract_type !== 'task_investment') {
            return redirect()->route('investor.dashboard')
                ->with('error', 'صفحة دفع المهام متاحة للمستثمر بالمهام فقط.');
        }

        $investorWallet = $investor->investorWallet;
        $walletBalance  = $investorWallet?->balance ?? 0;

        $query = Task::availableForInvestorPayment()
            ->with(['customer', 'pickup', 'delivery', 'ad'])
            ->latest();

        // فلتر العملاء المخصصين
        if (!empty($contract->filter_customer_ids)) {
            $query->whereIn('customer_id', $contract->filter_customer_ids);
        }

        // فلتر الحد الأدنى لعمولة المنصة (نتحقق من جدول المهام أو جدول الإعلانات)
        if ($contract->min_commission_threshold > 0) {
            $query->where(function ($q) use ($contract) {
                $q->where('commission', '>=', $contract->min_commission_threshold)
                  ->orWhereHas('ad', function ($sub) use ($contract) {
                      $sub->where('service_commission', '>=', $contract->min_commission_threshold);
                  });
            });
        }

        // فلتر البحث
        if ($request->search) {
            $query->where('id', 'like', "%{$request->search}%");
        }

        $tasks = $query->paginate(15)->withQueryString();

        return view('investor.task-payment.index', compact(
            'investor', 'contract', 'tasks', 'walletBalance'
        ));
    }

    /**
     * دفع قيمة مهمة محددة
     */
    public function pay(Request $request, Task $task)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $investor = auth()->user();
        
        // التحقق من صحة كلمة المرور
        if (!Hash::check($request->password, $investor->password)) {
            return back()->with('error', 'كلمة المرور غير صحيحة، يرجى المحاولة مرة أخرى.');
        }

        $contract = $investor->activeInvestmentContract;

        if (!$contract || $contract->contract_type !== 'task_investment') {
            return back()->with('error', 'غير مصرح بهذه العملية.');
        }

        try {
            $this->paymentService->payTask($investor, $task, $contract);
            return back()->with('success', "تم دفع قيمة المهمة #{$task->id} بنجاح وتم تسجيل عمولتك.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * عرض المهام التي دفع قيمتها هذا المستثمر
     */
    public function paidTasks(Request $request)
    {
        $investor = auth()->user();
        $wallet = $investor->userWallet;

        $tasks = Task::where('investor_id', $investor->id)
            ->with([
                'customer', 
                'pickup', 
                'delivery', 
                'ad', 
                'vehicle_size',
                'userWalletTransactions' => function($q) use ($wallet) {
                    $q->where('user_wallet_id', $wallet->id)->where('transaction_type', 'credit');
                }
            ])
            ->when($request->from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->search, function($q, $s) {
                $q->where(function($query) use ($s) {
                    $query->where('id', 'like', "%{$s}%")
                          ->orWhereHas('customer', fn($sub) => $sub->where('name', 'like', "%{$s}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('investor.paid-tasks.index', compact('investor', 'tasks'));
    }

    /**
     * تحميل تقرير المهمة للمستثمر
     */
    public function downloadReport(Task $task)
    {
        $investor = auth()->user();

        // التأكد أن المستثمر هو من مول هذه المهمة
        if ($task->investor_id !== $investor->id) {
            return back()->with('error', 'غير مصرح لك بالوصول لهذا التقرير.');
        }

        $task->load(['customer', 'pickup', 'delivery', 'vehicle_size', 'order', 'user', 'driver']);

        $customerName = optional($task->customer)->name ?? optional($task->user)->name ?? 'user';
        
        $file_name = sprintf(
            '%s_%s',
            $task->id,
            Str::slug($customerName, '_')
        );

        if ($task->driver) {
            $file_name .= "_{$task->driver->name}";
        }

        return $this->pdfService->generate('admin.tasks.report_pdf', [
            'task' => $task
        ], "Task_Report_{$file_name}.pdf");
    }
}
