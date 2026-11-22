<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\InvestorWallet;
use App\Models\InvestorWalletTransaction;
use App\Models\Task;
use App\Models\Task_History;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProblematicTasksExport;
use Exception;

class InvestorAuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_investors_audit', ['only' => ['index', 'exportExcel']]);
        $this->middleware('permission:edit_investors_audit_funding', ['only' => ['updateFundingAmount']]);
        $this->middleware('permission:delete_investors_audit_transaction', ['only' => ['deleteOrphanedTransaction']]);
    }

    /**
     * Display comprehensive audit of investors, wallets, and funded tasks.
     */
    public function index()
    {
        $data = $this->calculateAuditData();
        return view('admin.investors.audit', [
            'totals' => $data['totals'],
            'investorsData' => $data['investorsData'],
            'problematicTasks' => $data['problematicTasks'],
        ]);
    }

    /**
     * Export problematic tasks to Excel.
     */
    public function exportExcel()
    {
        $problematicTasks = $this->getProblematicTasks();
        $fileName = 'problematic_tasks_audit_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new ProblematicTasksExport($problematicTasks), $fileName);
    }

    /**
     * Edit/Adjust task funding amount (Debit transaction in investor wallet).
     */
    public function updateFundingAmount(Request $request)
    {
        $request->validate([
            'task_id' => 'required|integer|exists:tasks,id',
            'new_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ], [
            'task_id.required' => 'رقم المهمة مطلوب.',
            'task_id.exists' => 'المهمة غير موجودة في النظام.',
            'new_amount.required' => 'مبلغ التمويل الجديد مطلوب.',
            'new_amount.numeric' => 'يجب أن يكون المبلغ رقماً صحيحاً.',
            'new_amount.min' => 'لا يمكن أن يكون المبلغ سالباً.',
        ]);

        try {
            DB::beginTransaction();

            $taskId = $request->task_id;
            $newAmount = floatval($request->new_amount);
            $notes = $request->notes;
            $currentUser = Auth::user();

            $task = Task::with('investor')->findOrFail($taskId);

            // Fetch funding debit transactions for this task
            $debitTxs = InvestorWalletTransaction::where('task_id', $taskId)
                ->where('transaction_type', 'debit')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($debitTxs->count() === 0) {
                // If no debit transaction exists, but investor is assigned, create one
                $wallet = null;
                if ($task->investor_id) {
                    $wallet = InvestorWallet::where('user_id', $task->investor_id)->first();
                }
                if (!$wallet) {
                    return response()->json([
                        'status' => 0,
                        'error' => 'لا توجد حركة تمويل سابقة لهذه المهمة، ولا توجد محفظة استثمار مرتبطة بالمهمة.',
                    ]);
                }

                $tx = InvestorWalletTransaction::create([
                    'investor_wallet_id' => $wallet->id,
                    'task_id' => $taskId,
                    'transaction_type' => 'debit',
                    'source_type' => 'capital',
                    'amount' => $newAmount,
                    'description' => "دفع قيمة المهمة #{$taskId}",
                    'performed_by' => $currentUser->id,
                ]);

                $logMessage = "تم إنشاء تمويل للمهمة بمبلغ " . number_format($newAmount, 2) . " ر.س بواسطة {$currentUser->name}" . ($notes ? " - {$notes}" : '');

                // 1. Task History Log
                Task_History::create([
                    'action_type' => 'updated',
                    'description' => $logMessage,
                    'task_id' => $taskId,
                    'user_id' => $currentUser->id,
                ]);

                // 2. Activity Log
                ActivityLog::create([
                    'user_id' => $currentUser->id,
                    'action' => 'إنشاء تمويل',
                    'table_name' => 'investor_wallet_transactions',
                    'record_id' => (string) $tx->id,
                    'old_values' => null,
                    'new_values' => [
                        'amount' => $newAmount,
                        'task_id' => $taskId,
                        'log' => $logMessage,
                    ],
                    'ip_address' => request()->ip(),
                ]);

                DB::commit();

                return response()->json([
                    'status' => 1,
                    'success' => "تم إنشاء حركة تمويل بمبلغ " . number_format($newAmount, 2) . " ر.س للمهمة #{$taskId} بنجاح.",
                ]);
            }

            // If there's 1 debit transaction
            if ($debitTxs->count() === 1) {
                $tx = $debitTxs->first();
                $oldAmount = floatval($tx->amount);

                // Keep description clean without appended audit trail
                $tx->amount = $newAmount;
                $tx->description = "دفع قيمة المهمة #{$taskId}";
                $tx->performed_by = $currentUser->id;
                $tx->save();

                $logMessage = "تم تعديل التمويل من " . number_format($oldAmount, 2) . " إلى " . number_format($newAmount, 2) . " ر.س بواسطة {$currentUser->name}" . ($notes ? " - {$notes}" : '');

                // 1. Task History Log
                Task_History::create([
                    'action_type' => 'updated',
                    'description' => $logMessage,
                    'task_id' => $taskId,
                    'user_id' => $currentUser->id,
                ]);

                // 2. Activity Log
                ActivityLog::create([
                    'user_id' => $currentUser->id,
                    'action' => 'تعديل تمويل',
                    'table_name' => 'investor_wallet_transactions',
                    'record_id' => (string) $tx->id,
                    'old_values' => ['amount' => $oldAmount],
                    'new_values' => [
                        'amount' => $newAmount,
                        'task_id' => $taskId,
                        'log' => $logMessage,
                    ],
                    'ip_address' => request()->ip(),
                ]);

                DB::commit();

                $diff = $oldAmount - $newAmount;
                $effectText = $diff > 0 
                    ? "وتم إرجاع فارق بقيمة " . number_format($diff, 2) . " ر.س إلى رصيد محفظة المستثمر المتاح."
                    : ($diff < 0 ? "وتم خصم فارق إضافي بقيمة " . number_format(abs($diff), 2) . " ر.س من رصيد المستثمر." : "");

                return response()->json([
                    'status' => 1,
                    'success' => "تم تعديل مبلغ التمويل للمهمة #{$taskId} إلى " . number_format($newAmount, 2) . " ر.س بنجاح. {$effectText}",
                ]);
            }

            // If there are multiple debit transactions (duplicate funding)
            if ($debitTxs->count() > 1) {
                $primaryTx = $debitTxs->first();
                $oldTotal = floatval($debitTxs->sum('amount'));

                $primaryTx->amount = $newAmount;
                $primaryTx->description = "دفع قيمة المهمة #{$taskId}";
                $primaryTx->performed_by = $currentUser->id;
                $primaryTx->save();

                // Delete excess duplicate debit transactions
                for ($i = 1; $i < $debitTxs->count(); $i++) {
                    $debitTxs[$i]->delete();
                }

                $logMessage = "تم تصحيح التمويل المكرر للمهمة من إجمالي " . number_format($oldTotal, 2) . " إلى " . number_format($newAmount, 2) . " ر.س بواسطة {$currentUser->name}" . ($notes ? " - {$notes}" : '');

                // 1. Task History Log
                Task_History::create([
                    'action_type' => 'updated',
                    'description' => $logMessage,
                    'task_id' => $taskId,
                    'user_id' => $currentUser->id,
                ]);

                // 2. Activity Log
                ActivityLog::create([
                    'user_id' => $currentUser->id,
                    'action' => 'تصحيح تمويل مكرر',
                    'table_name' => 'investor_wallet_transactions',
                    'record_id' => (string) $primaryTx->id,
                    'old_values' => ['total_debited' => $oldTotal],
                    'new_values' => [
                        'amount' => $newAmount,
                        'task_id' => $taskId,
                        'log' => $logMessage,
                    ],
                    'ip_address' => request()->ip(),
                ]);

                DB::commit();

                return response()->json([
                    'status' => 1,
                    'success' => "تم تصحيح التمويل المكرر للمهمة #{$taskId} وضبط مبلغ التمويل إلى " . number_format($newAmount, 2) . " ر.س وحذف التكرارات بنجاح.",
                ]);
            }

            DB::commit();
            return response()->json(['status' => 1, 'success' => 'تمت العملية بنجاح.']);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'error' => 'حدث خطأ أثناء تعديل مبلغ التمويل: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete orphaned refund/settlement transaction (credit without debit).
     */
    public function deleteOrphanedTransaction(Request $request)
    {
        $request->validate([
            'transaction_id' => 'nullable|integer|exists:investor_wallet_transactions,id',
            'task_id' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $query = InvestorWalletTransaction::where('transaction_type', 'credit');

            if ($request->filled('transaction_id')) {
                $query->where('id', $request->transaction_id);
            } elseif ($request->filled('task_id')) {
                $query->where('task_id', $request->task_id);
            } else {
                return response()->json(['status' => 0, 'error' => 'معرف الحركة أو المهمة مطلوب.']);
            }

            $tx = $query->first();

            if (!$tx) {
                return response()->json(['status' => 0, 'error' => 'حركة الاسترداد غير موجودة أو تم حذفها مسبقاً.']);
            }

            $taskId = $tx->task_id;
            $amount = floatval($tx->amount);
            $currentUser = Auth::user();

            $logDesc = "حذف حركة استرداد غير مرتبطة بتمويل بمبلغ " . number_format($amount, 2) . " ر.س للمهمة #" . ($taskId ?? '-') . " بواسطة {$currentUser->name}";

            // 1. Activity Log
            ActivityLog::create([
                'user_id' => $currentUser->id,
                'action' => 'حذف استرداد غير مرتبط',
                'table_name' => 'investor_wallet_transactions',
                'record_id' => (string) $tx->id,
                'old_values' => ['amount' => $amount, 'task_id' => $taskId, 'source_type' => $tx->source_type],
                'new_values' => ['deleted' => true, 'log' => $logDesc],
                'ip_address' => request()->ip(),
            ]);

            // Delete transaction
            $tx->delete();

            if ($taskId) {
                Task_History::create([
                    'action_type' => 'updated',
                    'description' => $logDesc,
                    'task_id' => $taskId,
                    'user_id' => $currentUser->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'success' => "تم حذف حركة الاسترداد (المهمة #" . ($taskId ?? '-') . ") بمبلغ " . number_format($amount, 2) . " ر.س بنجاح وتحديث رصيد المحفظة.",
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'error' => 'حدث خطأ أثناء حذف الحركة: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate audit figures and return structured array.
     */
    protected function calculateAuditData()
    {
        $investors = User::where('investor', true)->orderBy('id')->get();

        $investorsData = [];
        $totalDeposited = 0;
        $totalWithdrawn = 0;
        $totalStoredBalance = 0;
        $totalCalculatedBalance = 0;
        $totalFundingDebits = 0;
        $totalSettlementCredits = 0;
        $totalLockedInTasksLedger = 0;
        $totalUnsettledTasksSum = 0;
        $totalUnsettledTasksCount = 0;

        foreach ($investors as $investor) {
            $wallet = InvestorWallet::where('user_id', $investor->id)->first();
            $walletId = $wallet ? $wallet->id : null;

            if ($wallet) {
                $storedBalance = floatval($wallet->balance);

                $deposited = floatval(
                    InvestorWalletTransaction::where('investor_wallet_id', $walletId)
                        ->where('transaction_type', 'credit')
                        ->whereIn('source_type', ['capital', 'hyperpay'])
                        ->sum('amount')
                );

                $withdrawn = floatval(
                    InvestorWalletTransaction::where('investor_wallet_id', $walletId)
                        ->where('transaction_type', 'debit')
                        ->whereNull('task_id')
                        ->sum('amount')
                );

                $fundingDebits = floatval(
                    InvestorWalletTransaction::where('investor_wallet_id', $walletId)
                        ->where('transaction_type', 'debit')
                        ->whereNotNull('task_id')
                        ->sum('amount')
                );

                $settlementCredits = floatval(
                    InvestorWalletTransaction::where('investor_wallet_id', $walletId)
                        ->where('transaction_type', 'credit')
                        ->whereIn('source_type', ['refund', 'capital_return'])
                        ->whereNotNull('task_id')
                        ->sum('amount')
                );

                $allCredits = floatval(
                    InvestorWalletTransaction::where('investor_wallet_id', $walletId)
                        ->where('transaction_type', 'credit')
                        ->sum('amount')
                );
                $allDebits = floatval(
                    InvestorWalletTransaction::where('investor_wallet_id', $walletId)
                        ->where('transaction_type', 'debit')
                        ->sum('amount')
                );

                $calculatedBalance = $allCredits - $allDebits;
                $lockedInTasks = $fundingDebits - $settlementCredits;
                $netBaseCapital = $deposited - $withdrawn;

                $fundedTaskIds = InvestorWalletTransaction::where('investor_wallet_id', $walletId)
                    ->where('transaction_type', 'debit')
                    ->whereNotNull('task_id')
                    ->pluck('task_id')
                    ->unique();

                $refundedTaskIds = InvestorWalletTransaction::where('investor_wallet_id', $walletId)
                    ->where('transaction_type', 'credit')
                    ->whereIn('source_type', ['refund', 'capital_return'])
                    ->whereNotNull('task_id')
                    ->pluck('task_id')
                    ->unique();

                $unsettledTaskIds = $fundedTaskIds->diff($refundedTaskIds);
                $unsettledCount = $unsettledTaskIds->count();
                $unsettledSum = floatval(Task::whereIn('id', $unsettledTaskIds)->sum('total_price'));

                $balanceLedgerDiff = $storedBalance - $calculatedBalance;
                $accountingDiff = $netBaseCapital - ($calculatedBalance + $lockedInTasks);

                $investorsData[] = [
                    'id' => $investor->id,
                    'name' => $investor->name,
                    'email' => $investor->email,
                    'phone' => $investor->phone ?? $investor->mobile,
                    'contract_type' => $investor->contract_type ?? 'task_investment',
                    'wallet_id' => $walletId,
                    'deposited_capital' => $deposited,
                    'withdrawn_capital' => $withdrawn,
                    'net_base_capital' => $netBaseCapital,
                    'stored_balance' => $storedBalance,
                    'calculated_balance' => $calculatedBalance,
                    'balance_ledger_diff' => $balanceLedgerDiff,
                    'funding_debits' => $fundingDebits,
                    'settlement_credits' => $settlementCredits,
                    'locked_in_tasks_ledger' => $lockedInTasks,
                    'unsettled_tasks_count' => $unsettledCount,
                    'unsettled_tasks_sum' => $unsettledSum,
                    'unsettled_vs_locked_diff' => $lockedInTasks - $unsettledSum,
                    'accounting_diff' => $accountingDiff,
                ];

                $totalDeposited += $deposited;
                $totalWithdrawn += $withdrawn;
                $totalStoredBalance += $storedBalance;
                $totalCalculatedBalance += $calculatedBalance;
                $totalFundingDebits += $fundingDebits;
                $totalSettlementCredits += $settlementCredits;
                $totalLockedInTasksLedger += $lockedInTasks;
                $totalUnsettledTasksSum += $unsettledSum;
                $totalUnsettledTasksCount += $unsettledCount;
            }
        }

        $totalNetBaseCapital = $totalDeposited - $totalWithdrawn;
        $totalDiscrepancy = $totalNetBaseCapital - ($totalCalculatedBalance + $totalLockedInTasksLedger);

        $totals = [
            'investors_count' => count($investorsData),
            'total_deposited_capital' => $totalDeposited,
            'total_withdrawn_capital' => $totalWithdrawn,
            'total_net_base_capital' => $totalNetBaseCapital,
            'total_wallet_balance_stored' => $totalStoredBalance,
            'total_wallet_balance_calculated' => $totalCalculatedBalance,
            'total_task_funding_debits' => $totalFundingDebits,
            'total_task_settlement_credits' => $totalSettlementCredits,
            'total_locked_in_tasks_ledger' => $totalLockedInTasksLedger,
            'total_unsettled_tasks_amount' => $totalUnsettledTasksSum,
            'total_unsettled_tasks_count' => $totalUnsettledTasksCount,
            'total_discrepancy' => $totalDiscrepancy,
            'unsettled_vs_locked_diff' => $totalLockedInTasksLedger - $totalUnsettledTasksSum,
        ];

        $problematicTasks = $this->getProblematicTasks();

        return [
            'totals' => $totals,
            'investorsData' => $investorsData,
            'problematicTasks' => $problematicTasks,
        ];
    }

    /**
     * Get list of problematic tasks with all audit metadata.
     */
    public function getProblematicTasks()
    {
        $taskIdsWithTransactions = InvestorWalletTransaction::whereNotNull('task_id')
            ->pluck('task_id')
            ->unique();

        $problematicTasks = [];

        foreach ($taskIdsWithTransactions as $taskId) {
            $task = Task::with(['investor', 'customer'])->find($taskId);
            $txs = InvestorWalletTransaction::where('task_id', $taskId)
                ->with('wallet.investor')
                ->orderBy('created_at', 'asc')
                ->get();

            $debits = $txs->where('transaction_type', 'debit');
            $credits = $txs->where('transaction_type', 'credit');

            $totalDebited = floatval($debits->sum('amount'));
            $totalCredited = floatval($credits->sum('amount'));
            $taskPrice = $task ? floatval($task->total_price) : 0;

            $hasIssue = false;
            $issueType = '';
            $issueBadgeClass = 'bg-label-warning';
            $issueReasons = [];
            $canEditFunding = false;
            $canDeleteRefund = false;

            // Case 1: Settled task with difference between debited and credited
            if ($credits->count() > 0 && abs($totalDebited - $totalCredited) > 0.01) {
                $hasIssue = true;
                if ($totalDebited == 0) {
                    $issueType = 'استرداد بدون خصم مسبق';
                    $issueBadgeClass = 'bg-label-danger';
                    $issueReasons[] = "تم تسجيل استرداد للمحفظة بمبلغ ({$totalCredited} ر.س) دون وجود حركة خصم تمويل سابقة.";
                    $canDeleteRefund = true;
                } elseif ($totalDebited > $totalCredited) {
                    $diff = $totalDebited - $totalCredited;
                    $issueType = 'تسوية ناقصة بعد تعديل السعر';
                    $issueBadgeClass = 'bg-label-warning';
                    $issueReasons[] = "خُصم تمويل بمبلغ ({$totalDebited} ر.س) وعند التسوية استُرد ({$totalCredited} ر.س)، فبقي فارق ({$diff} ر.س) لم يُرد للمستثمر.";
                    $canEditFunding = true;
                } else {
                    $diff = $totalCredited - $totalDebited;
                    $issueType = 'استرداد أعلى من التمويل';
                    $issueBadgeClass = 'bg-label-info';
                    $issueReasons[] = "المبلغ المسترد ({$totalCredited} ر.س) أكبر من المبلغ المخصوم ({$totalDebited} ر.س) بفارق ({$diff} ر.س).";
                    $canEditFunding = true;
                }
            }

            // Case 2: Open/unsettled task where debited amount != current task price
            if ($credits->count() == 0 && abs($totalDebited - $taskPrice) > 0.01 && $task) {
                $hasIssue = true;
                $diff = $totalDebited - $taskPrice;
                $issueType = 'تعديل السعر بعد التمويل';
                $issueBadgeClass = 'bg-label-primary';
                if ($diff > 0) {
                    $issueReasons[] = "خُصم من المستثمر ({$totalDebited} ر.س) بينما سعر المهمة الحالي ({$taskPrice} ر.س). يوجد فارق محجوز بالزيادة ({$diff} ر.س).";
                } else {
                    $diffAbs = abs($diff);
                    $issueReasons[] = "خُصم من المستثمر ({$totalDebited} ر.س) بينما سعر المهمة الحالي ارتفع إلى ({$taskPrice} ر.س) بفارق ({$diffAbs} ر.س).";
                }
                $canEditFunding = true;
            }

            // Case 3: Multiple debits for the same task
            if ($debits->count() > 1) {
                $hasIssue = true;
                if (empty($issueType)) {
                    $issueType = 'تمويل مكرر';
                    $issueBadgeClass = 'bg-label-danger';
                }
                $issueReasons[] = "تم تسجيل أكثر من حركة خصم تمويل لنفس المهمة (عدد {$debits->count()} حركات).";
                $canEditFunding = true;
            }

            if ($hasIssue) {
                $histories = Task_History::where('task_id', $taskId)
                    ->with('user')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $priceChanges = [];
                foreach ($histories as $h) {
                    $userName = $h->user ? $h->user->name : 'نظام / غير محدد';
                    $desc = $h->description ?? '';
                    $dateStr = $h->created_at ? $h->created_at->format('Y-m-d H:i') : '';

                    if (stripos($desc, 'pricing') !== false || stripos($desc, 'price') !== false || stripos($desc, 'سعر') !== false) {
                        $priceChanges[] = [
                            'user' => $userName,
                            'description' => $desc,
                            'date' => $dateStr,
                        ];
                    }
                }

                $cleanHistories = [];
                foreach ($histories as $h) {
                    $uName = $h->user ? $h->user->name : 'نظام / غير محدد';
                    $cleanHistories[] = [
                        'user' => $uName,
                        'action' => $h->action_type,
                        'description' => $h->description ?? '',
                        'date' => $h->created_at ? $h->created_at->format('Y-m-d H:i') : '',
                    ];
                }

                $funders = [];
                foreach ($debits as $d) {
                    $u = User::find($d->performed_by);
                    $funders[] = [
                        'user' => $u ? $u->name : "مستخدم #{$d->performed_by}",
                        'amount' => floatval($d->amount),
                        'date' => $d->created_at ? $d->created_at->format('Y-m-d H:i') : '',
                        'description' => $d->description,
                    ];
                }

                $settlers = [];
                foreach ($credits as $c) {
                    $u = User::find($c->performed_by);
                    $settlers[] = [
                        'user' => $u ? $u->name : "مستخدم #{$c->performed_by}",
                        'amount' => floatval($c->amount),
                        'date' => $c->created_at ? $c->created_at->format('Y-m-d H:i') : '',
                        'description' => $c->description,
                    ];
                }

                $cleanTransactions = [];
                $firstCreditTxId = null;
                foreach ($txs as $tx) {
                    $perfUser = User::find($tx->performed_by);
                    if ($tx->transaction_type === 'credit' && !$firstCreditTxId) {
                        $firstCreditTxId = $tx->id;
                    }
                    $cleanTransactions[] = [
                        'id' => $tx->id,
                        'transaction_type' => $tx->transaction_type,
                        'amount' => floatval($tx->amount),
                        'performed_by' => $perfUser ? $perfUser->name : "مستخدم #{$tx->performed_by}",
                        'description' => $tx->description,
                        'date' => $tx->created_at ? $tx->created_at->format('Y-m-d H:i') : '',
                    ];
                }

                $investor = $task && $task->investor ? $task->investor : ($txs->first()->wallet->investor ?? null);
                $netLocked = $totalDebited - $totalCredited;

                $problematicTasks[] = [
                    'task_id' => $taskId,
                    'task_status' => $task ? $task->status : 'محذوفة/غير موجودة',
                    'investor_id' => $investor ? $investor->id : null,
                    'investor_name' => $investor ? $investor->name : 'غير محدد',
                    'customer_name' => $task && $task->customer ? $task->customer->name : 'غير محدد',
                    'task_price' => $taskPrice,
                    'total_debited' => $totalDebited,
                    'total_credited' => $totalCredited,
                    'net_locked' => $netLocked,
                    'diff' => $totalDebited - ($credits->count() > 0 ? $totalCredited : $taskPrice),
                    'issue_type' => $issueType,
                    'issue_badge_class' => $issueBadgeClass,
                    'issue_reasons' => $issueReasons,
                    'can_edit_funding' => $canEditFunding,
                    'can_delete_refund' => $canDeleteRefund,
                    'credit_tx_id' => $firstCreditTxId,
                    'funders' => $funders,
                    'settlers' => $settlers,
                    'price_changes' => $priceChanges,
                    'all_histories' => $cleanHistories,
                    'transactions' => $cleanTransactions,
                ];
            }
        }

        usort($problematicTasks, function ($a, $b) {
            return abs($b['net_locked']) <=> abs($a['net_locked']);
        });

        return $problematicTasks;
    }
}
