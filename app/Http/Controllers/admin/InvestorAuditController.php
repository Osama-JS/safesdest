<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\InvestorWallet;
use App\Models\InvestorWalletTransaction;
use App\Models\UserWalletTransaction;
use App\Models\InvestmentContract;
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
        $this->middleware('permission:view_investors_audit', ['only' => [
            'index',
            'exportExcel',
            'getProblematicTasksData',
            'getCanceledTasksData',
            'getCommissionAuditTableData',
        ]]);
        $this->middleware('permission:edit_investors_audit_funding', ['only' => ['updateFundingAmount']]);
        $this->middleware('permission:delete_investors_audit_transaction', ['only' => ['deleteOrphanedTransaction']]);
        $this->middleware('permission:settle_investors_audit_canceled_task', ['only' => ['settleCanceledTask', 'batchSettleCanceledTasks']]);
        $this->middleware('permission:settle_investors_audit_commission', ['only' => ['settleCommission', 'batchSettleCommissions']]);
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
            'customersUnsettled' => $data['customersUnsettled'],
            'problematicTasks' => $data['problematicTasks'],
            'canceledTasksAudit' => $data['canceledTasksAudit'],
            'commissionAudit' => $data['commissionAudit'],
            'problematicCount' => count($data['problematicTasks']),
            'canceledAuditCount' => $data['canceledTasksAudit']['count'],
            'canceledTotalLocked' => $data['canceledTasksAudit']['total_locked_capital'],
            'canceledTotalUnreversedComm' => $data['canceledTasksAudit']['total_unreversed_commission'],
            'commissionSummary' => $data['commissionAudit']['summary'],
        ]);
    }

    /**
     * AJAX: Paginated and filtered problematic tasks data.
     */
    public function getProblematicTasksData(Request $request)
    {
        $problematicTasks = $this->getProblematicTasks();

        $search = strtolower(trim($request->input('search', '')));
        $issueType = trim($request->input('issue_type', ''));
        $page = max(1, intval($request->input('page', 1)));
        $perPage = intval($request->input('per_page', 10));

        // Filter
        $filtered = array_filter($problematicTasks, function($item) use ($search, $issueType) {
            if ($issueType !== '' && strtolower($item['issue_type']) !== strtolower($issueType)) {
                return false;
            }
            if ($search !== '') {
                $searchContent = strtolower(
                    $item['task_id'] . ' ' .
                    $item['investor_name'] . ' ' .
                    $item['customer_name'] . ' ' .
                    $item['issue_type'] . ' ' .
                    implode(' ', array_column($item['funders'], 'user')) . ' ' .
                    implode(' ', array_column($item['settlers'], 'user'))
                );
                if (!str_contains($searchContent, $search)) {
                    return false;
                }
            }
            return true;
        });

        $filtered = array_values($filtered);
        $total = count($filtered);
        $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;
        $pagedItems = $perPage > 0 ? array_slice($filtered, $offset, $perPage) : $filtered;

        return response()->json([
            'status' => 1,
            'data' => $pagedItems,
            'all_data' => $problematicTasks, // used by client for details modal
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ]
        ]);
    }

    /**
     * AJAX: Paginated and filtered canceled tasks data.
     */
    public function getCanceledTasksData(Request $request)
    {
        $canceledAudit = $this->getCanceledUnsettledTasks();
        $tasks = $canceledAudit['tasks'];

        $search = strtolower(trim($request->input('search', '')));
        $commStatus = trim($request->input('comm_status', ''));
        $page = max(1, intval($request->input('page', 1)));
        $perPage = intval($request->input('per_page', 10));

        // Filter
        $filtered = array_filter($tasks, function($cTask) use ($search, $commStatus) {
            if ($commStatus === 'unreversed' && !$cTask['has_unreversed_commission']) {
                return false;
            }
            if ($commStatus === 'zero' && $cTask['has_unreversed_commission']) {
                return false;
            }
            if ($search !== '') {
                $searchContent = strtolower(
                    $cTask['task_id'] . ' ' .
                    $cTask['investor_name'] . ' ' .
                    $cTask['customer_name'] . ' ' .
                    $cTask['canceled_by'] . ' ' .
                    $cTask['cancel_description']
                );
                if (!str_contains($searchContent, $search)) {
                    return false;
                }
            }
            return true;
        });

        $filtered = array_values($filtered);
        $total = count($filtered);
        $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;
        $pagedItems = $perPage > 0 ? array_slice($filtered, $offset, $perPage) : $filtered;

        return response()->json([
            'status' => 1,
            'data' => $pagedItems,
            'summary' => [
                'count' => $canceledAudit['count'],
                'total_locked_capital' => $canceledAudit['total_locked_capital'],
                'total_unreversed_commission' => $canceledAudit['total_unreversed_commission'],
                'filtered_locked_capital' => array_sum(array_column($filtered, 'net_locked_capital')),
                'filtered_unreversed_commission' => array_sum(array_column($filtered, 'net_commission')),
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ]
        ]);
    }

    /**
     * AJAX: Paginated and filtered commission audit table data.
     */
    public function getCommissionAuditTableData(Request $request)
    {
        $audit = $this->getCommissionAuditData();
        $tasks = $audit['tasks'];

        $search = strtolower(trim($request->input('search', '')));
        $statusFilter = trim($request->input('status', 'discrepant'));
        $investorFilter = trim($request->input('investor', ''));
        $page = max(1, intval($request->input('page', 1)));
        $perPage = intval($request->input('per_page', 10));

        // Filter
        $filtered = array_filter($tasks, function($item) use ($search, $statusFilter, $investorFilter) {
            $isDiscrepant = in_array($item['status'], ['overpaid', 'underpaid', 'uncredited']);

            if ($statusFilter === 'discrepant' && !$isDiscrepant) {
                return false;
            } elseif ($statusFilter === 'matched' && $item['status'] !== 'matched') {
                return false;
            } elseif (in_array($statusFilter, ['overpaid', 'underpaid', 'uncredited']) && $item['status'] !== $statusFilter) {
                return false;
            }

            if ($investorFilter !== '' && $item['investor_name'] !== $investorFilter) {
                return false;
            }

            if ($search !== '') {
                $searchContent = strtolower(
                    $item['task_id'] . ' ' .
                    $item['investor_name'] . ' ' .
                    $item['customer_name'] . ' ' .
                    $item['status_label']
                );
                if (!str_contains($searchContent, $search)) {
                    return false;
                }
            }

            return true;
        });

        $filtered = array_values($filtered);
        $total = count($filtered);
        $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;
        $pagedItems = $perPage > 0 ? array_slice($filtered, $offset, $perPage) : $filtered;

        return response()->json([
            'status' => 1,
            'data' => $pagedItems,
            'summary' => $audit['summary'],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ]
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
     * Settle a canceled task: refund remaining capital to investor wallet and reverse commissions in personal wallet.
     */
    public function settleCanceledTask(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
        ]);

        $taskId = $request->task_id;
        $currentUser = Auth::user();

        try {
            $task = Task::findOrFail($taskId);

            if (!in_array($task->status, ['canceled', 'cancelled'])) {
                return response()->json([
                    'status' => 0,
                    'error' => 'هذه المهمة ليست ملغاة (حالتها الحالية: ' . $task->status . '). لا يمكن تسويتها كمهمة ملغاة.',
                ]);
            }

            $result = app(\App\Services\InvestorPaymentService::class)->reverseAndRefundCanceledTask(
                $task, 
                $currentUser, 
                'تسوية مهمة ملغاة من صفحة التدقيق'
            );

            if (!empty($result['already_settled'])) {
                return response()->json([
                    'status' => 0,
                    'error' => 'تمت تسوية هذه المهمة الملغاة مسبقاً بالكامل (رأس المال مسترد والعمولات معكوسة بالفعل).',
                ]);
            }

            $refundedCapital = (float) ($result['refunded_capital'] ?? 0);
            $reversedCommission = (float) ($result['reversed_commission_total'] ?? 0);

            // Activity Log
            ActivityLog::create([
                'user_id' => $currentUser->id,
                'action' => 'تسوية مهمة ملغاة وفصلها من المستثمر',
                'table_name' => 'tasks',
                'record_id' => (string) $task->id,
                'new_values' => [
                    'investor_id' => null,
                    'investor_payment_status' => 'none',
                    'refunded_capital' => $refundedCapital,
                    'capital_tx_id' => $result['capital_tx_id'] ?? null,
                    'reversed_commission' => $reversedCommission,
                ],
                'ip_address' => request()->ip(),
            ]);

            return response()->json([
                'status' => 1,
                'success' => "تم تسوية المهمة الملغاة #{$task->id} وفصلها من المستثمر بنجاح! تم استرداد " . number_format($refundedCapital, 2) . " ر.س لمحفظة الاستثمار" . ($reversedCommission > 0 ? " وخصم عكسي " . number_format($reversedCommission, 2) . " ر.س من العمولات." : "."),
                'task_id' => $task->id,
                'refunded_capital' => $refundedCapital,
                'reversed_commission' => $reversedCommission,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'error' => 'حدث خطأ أثناء تسوية المهمة: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Batch settle multiple canceled tasks.
     */
    public function batchSettleCanceledTasks(Request $request)
    {
        $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'integer|exists:tasks,id',
        ], [
            'task_ids.required' => 'يجب تحديد مهمة واحدة على الأقل للتسوية.',
            'task_ids.array' => 'معرفات المهام غير صالحة.',
        ]);

        $taskIds = array_values(array_unique($request->task_ids));
        $currentUser = Auth::user();

        $settledCount = 0;
        $totalCapitalRefunded = 0;
        $totalCommissionsReversed = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($taskIds as $taskId) {
            try {
                $task = Task::find($taskId);

                if (!$task || !in_array($task->status, ['canceled', 'cancelled'])) {
                    $skippedCount++;
                    continue;
                }

                $result = app(\App\Services\InvestorPaymentService::class)->reverseAndRefundCanceledTask(
                    $task,
                    $currentUser,
                    'تسوية جماعية للمهام الملغاة من صفحة التدقيق'
                );

                if (!empty($result['already_settled'])) {
                    $skippedCount++;
                    continue;
                }

                $refundedCapital = (float) ($result['refunded_capital'] ?? 0);
                $reversedCommission = (float) ($result['reversed_commission_total'] ?? 0);

                $totalCapitalRefunded += $refundedCapital;
                $totalCommissionsReversed += $reversedCommission;
                $settledCount++;

                // Activity Log
                ActivityLog::create([
                    'user_id' => $currentUser->id,
                    'action' => 'تسوية مهمة ملغاة وفصلها من المستثمر جماعية',
                    'table_name' => 'tasks',
                    'record_id' => (string) $task->id,
                    'new_values' => [
                        'investor_id' => null,
                        'investor_payment_status' => 'none',
                        'refunded_capital' => $refundedCapital,
                        'capital_tx_id' => $result['capital_tx_id'] ?? null,
                        'reversed_commission' => $reversedCommission,
                    ],
                    'ip_address' => request()->ip(),
                ]);

            } catch (Exception $e) {
                $errors[] = "المهمة #{$taskId}: " . $e->getMessage();
            }
        }

        $message = "تم تسوية {$settledCount} مهمة ملغاة بنجاح بإجمالي رأس مال مسترد " . number_format($totalCapitalRefunded, 2) . " ر.س وعمولات معكوسة " . number_format($totalCommissionsReversed, 2) . " ر.س.";
        if ($skippedCount > 0) {
            $message .= " (تم تجاوز {$skippedCount} مهمة مسواة مسبقاً أو غير ملغاة).";
        }
        if (count($errors) > 0) {
            $message .= " واجهت بعض المهام أخطاء: " . implode(' | ', $errors);
        }

        return response()->json([
            'status' => $settledCount > 0 ? 1 : 0,
            'success' => $message,
            'error' => $settledCount === 0 && count($errors) > 0 ? implode(' | ', $errors) : null,
            'settled_count' => $settledCount,
            'skipped_count' => $skippedCount,
            'total_capital_refunded' => $totalCapitalRefunded,
            'total_commissions_reversed' => $totalCommissionsReversed,
            'errors' => $errors,
        ]);
    }

    /**
     * Settle single commission discrepancy for a task.
     */
    public function settleCommission(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'reason' => 'nullable|string|max:500',
        ], [
            'task_id.required' => 'رقم المهمة مطلوب.',
            'task_id.exists' => 'المهمة غير موجودة.',
        ]);

        $taskId = $request->task_id;
        $reason = trim($request->reason ?? '');
        $currentUser = Auth::user();

        try {
            DB::beginTransaction();

            $task = Task::with(['customer.userCommissions.user', 'brokers', 'driver.brokers', 'ad', 'investor'])
                ->lockForUpdate()
                ->findOrFail($taskId);

            if (in_array(strtolower($task->status), ['canceled', 'cancelled', 'canceld', 'refund', 'refound', 'refunded'])) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'error' => 'هذه المهمة ملغاة أو مستردة، تسويتها تتم من خلال جدول المهام الملغاة وليس تسوية العمولات.',
                ]);
            }

            $investor = $task->investor;
            if (!$investor) {
                // Try from investor wallet transaction
                $firstDebit = InvestorWalletTransaction::where('task_id', $taskId)
                    ->where('transaction_type', 'debit')
                    ->with('wallet.investor')
                    ->first();
                $investor = $firstDebit?->wallet?->investor;
            }

            if (!$investor) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'error' => 'تعذر تحديد المستثمر المرتبط بهذه المهمة.',
                ]);
            }

            // Find or get contract (using user_id which is the investor foreign key on investment_contracts)
            $contract = InvestmentContract::with(['brokers'])
                ->where('user_id', $investor->id)
                ->where('status', 'active')
                ->first();

            if (!$contract) {
                $contract = InvestmentContract::with(['brokers'])
                    ->where('user_id', $investor->id)
                    ->latest()
                    ->first();
            }

            if (!$contract) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'error' => 'تعذر تحديد عقد الاستثمار لهذا المستثمر.',
                ]);
            }

            $calc = $task->calculateInvestorCommission($contract);
            $expectedComm = (float) $calc['net_investor_commission'];

            // بناء تفاصيل الخصومات للوصف
            $descNotes = [];
            if ($calc['user_commissions_total'] > 0) {
                $descNotes[] = "مستخدمين: -{$calc['user_commissions_total']}";
            }
            if ($calc['task_brokers_total'] > 0) {
                $descNotes[] = "وسطاء: -{$calc['task_brokers_total']}";
            }
            if ($calc['contract_task_brokers_total'] > 0 || $calc['contract_investor_brokers_total'] > 0) {
                $descNotes[] = "وسطاء عقد";
            }
            $deductionNote = !empty($descNotes) ? " (بعد خصم " . implode(' و ', $descNotes) . ")" : "";

            // Lock and get investor personal wallet
            $userWallet = \App\Models\UserWallet::lockForUpdate()->firstOrCreate(
                ['user_id' => $investor->id],
                ['user_type' => 'investor', 'status' => true]
            );

            // Fetch actual net commission currently credited for this task
            $uTxs = UserWalletTransaction::where('user_wallet_id', $userWallet->id)
                ->where('task_id', $taskId)
                ->get();
            $actualCredited = (float) $uTxs->where('transaction_type', 'credit')->sum('amount');
            $actualDebited = (float) $uTxs->where('transaction_type', 'debit')->sum('amount');
            $actualNet = $actualCredited - $actualDebited;

            $diff = $actualNet - $expectedComm;

            if (abs($diff) <= 0.05) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'error' => 'العمولة مطابقة تماماً للعقد والخصومات (الفارق 0.00 ر.س). لا يتطلب الأمر أي تسوية.',
                ]);
            }

            // 1. Check existing primary credit transaction for this task in investor wallet
            $primaryCreditTx = $uTxs->where('transaction_type', 'credit')->first();
            $oldAmount = $primaryCreditTx ? floatval($primaryCreditTx->amount) : 0;
            $oldDescription = $primaryCreditTx ? $primaryCreditTx->description : '';

            $txRecordId = null;
            $actionLabel = '';

            if ($primaryCreditTx) {
                // Adjust the existing transaction directly
                $actionLabel = 'تعديل حركة العمولة الأصلية';
                $oldAmountFormatted = number_format($oldAmount, 2);
                $newAmountFormatted = number_format($expectedComm, 2);

                $updatedDesc = $primaryCreditTx->description;
                $noteSuffix = " [تم تسوية العمولة وتعديلها من {$oldAmountFormatted} إلى {$newAmountFormatted} ر.س بناءً على العقد{$deductionNote}" . ($reason ? " - السبب: {$reason}" : "") . " - بواسطة {$currentUser->name}]";
                
                // Append or replace notes in description cleanly
                if (!str_contains($updatedDesc, '[تم تسوية العمولة')) {
                    $updatedDesc = trim($updatedDesc) . $noteSuffix;
                } else {
                    $updatedDesc = preg_replace('/\[تم تسوية العمولة.*?\]/', trim($noteSuffix), $updatedDesc);
                }

                $primaryCreditTx->amount = $expectedComm;
                $primaryCreditTx->description = $updatedDesc;
                $primaryCreditTx->save();

                // If there were any subsequent extra credit/debit adjustment transactions for this task in the wallet, remove them to keep only the single clean adjusted transaction
                $extraTxs = $uTxs->where('id', '!=', $primaryCreditTx->id);
                foreach ($extraTxs as $extraTx) {
                    $extraTx->delete();
                }

                $txRecordId = (string) $primaryCreditTx->id;

            } else {
                // If no credit transaction ever existed (uncredited task), create the primary commission credit transaction directly
                $actionLabel = 'تسجيل عمولة المهمة';
                $baseDesc = "عمولة المهمة #{$taskId}{$deductionNote}";
                if ($reason) {
                    $baseDesc .= " [تسوية عمولة بناءً على العقد - السبب: {$reason} - بواسطة {$currentUser->name}]";
                }

                $newTx = UserWalletTransaction::create([
                    'user_wallet_id'   => $userWallet->id,
                    'user_id'          => $currentUser->id,
                    'task_id'          => $taskId,
                    'transaction_type' => 'credit',
                    'amount'           => $expectedComm,
                    'description'      => $baseDesc,
                    'status'           => true,
                ]);

                $txRecordId = (string) $newTx->id;
            }

            $logDesc = "تمت تسوية وتعديل عمولة المهمة #{$taskId} في نفس الحركة بنجاح للمستثمر ({$investor->name}): " .
                "تم ضبط العمولة إلى " . number_format($expectedComm, 2) . " ر.س (المبلغ السابق: " . number_format($actualNet, 2) . " ر.س). " .
                ($reason ? "السبب: {$reason} - " : "") . "بواسطة: {$currentUser->name}";

            // Task History
            Task_History::create([
                'action_type' => 'updated',
                'description' => $logDesc,
                'task_id' => $taskId,
                'user_id' => $currentUser->id,
            ]);

            // Activity Log
            ActivityLog::create([
                'user_id' => $currentUser->id,
                'action' => $actionLabel,
                'table_name' => 'user_wallet_transactions',
                'record_id' => $txRecordId,
                'old_values' => [
                    'actual_net_commission' => $actualNet,
                    'expected_commission' => $expectedComm,
                    'description' => $oldDescription,
                ],
                'new_values' => [
                    'adjusted_amount' => $expectedComm,
                    'reason' => $reason,
                    'log' => $logDesc,
                ],
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 1,
                'success' => "تم تسوية وتعديل حركة عمولة المهمة #{$taskId} بنجاح إلى " . number_format($expectedComm, 2) . " ر.س وتحديث وصف الحركة بالملاحظة.",
                'task_id' => $taskId,
                'adjusted_amount' => $expectedComm,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'error' => 'حدث خطأ أثناء تسوية العمولة: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Batch settle commissions for multiple tasks.
     */
    public function batchSettleCommissions(Request $request)
    {
        $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'integer|exists:tasks,id',
            'reason' => 'nullable|string|max:500',
        ], [
            'task_ids.required' => 'يجب تحديد مهمة واحدة على الأقل لتسوية عمولتها.',
            'task_ids.array' => 'معرفات المهام غير صالحة.',
        ]);

        $taskIds = array_values(array_unique($request->task_ids));
        $reason = trim($request->reason ?? '');
        $currentUser = Auth::user();

        $settledCount = 0;
        $totalCredited = 0;
        $totalDebited = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($taskIds as $taskId) {
            try {
                DB::beginTransaction();

                $task = Task::with(['customer.userCommissions.user', 'brokers', 'driver.brokers', 'ad', 'investor'])
                    ->lockForUpdate()
                    ->find($taskId);

                if (!$task || in_array(strtolower($task->status), ['canceled', 'cancelled', 'canceld', 'refund', 'refound', 'refunded'])) {
                    DB::rollBack();
                    $skippedCount++;
                    continue;
                }

                $investor = $task->investor;
                if (!$investor) {
                    $firstDebit = InvestorWalletTransaction::where('task_id', $taskId)
                        ->where('transaction_type', 'debit')
                        ->with('wallet.investor')
                        ->first();
                    $investor = $firstDebit?->wallet?->investor;
                }

                if (!$investor) {
                    DB::rollBack();
                    $errors[] = "المهمة #{$taskId}: تعذر تحديد المستثمر.";
                    continue;
                }

                $contract = InvestmentContract::with(['brokers'])
                    ->where('user_id', $investor->id)
                    ->where('status', 'active')
                    ->first();

                if (!$contract) {
                    $contract = InvestmentContract::with(['brokers'])
                        ->where('user_id', $investor->id)
                        ->latest()
                        ->first();
                }

                if (!$contract) {
                    DB::rollBack();
                    $errors[] = "المهمة #{$taskId}: تعذر تحديد عقد الاستثمار.";
                    continue;
                }

                $calc = $task->calculateInvestorCommission($contract);
                $expectedComm = (float) $calc['net_investor_commission'];

                $descNotes = [];
                if ($calc['user_commissions_total'] > 0) {
                    $descNotes[] = "مستخدمين: -{$calc['user_commissions_total']}";
                }
                if ($calc['task_brokers_total'] > 0) {
                    $descNotes[] = "وسطاء: -{$calc['task_brokers_total']}";
                }
                if ($calc['contract_task_brokers_total'] > 0 || $calc['contract_investor_brokers_total'] > 0) {
                    $descNotes[] = "وسطاء عقد";
                }
                $deductionNote = !empty($descNotes) ? " (بعد خصم " . implode(' و ', $descNotes) . ")" : "";

                $userWallet = \App\Models\UserWallet::lockForUpdate()->firstOrCreate(
                    ['user_id' => $investor->id],
                    ['user_type' => 'investor', 'status' => true]
                );

                $uTxs = UserWalletTransaction::where('user_wallet_id', $userWallet->id)
                    ->where('task_id', $taskId)
                    ->get();
                $actualCredited = (float) $uTxs->where('transaction_type', 'credit')->sum('amount');
                $actualDebited = (float) $uTxs->where('transaction_type', 'debit')->sum('amount');
                $actualNet = $actualCredited - $actualDebited;

                $diff = $actualNet - $expectedComm;

                if (abs($diff) <= 0.05) {
                    DB::rollBack();
                    $skippedCount++;
                    continue;
                }

                // 1. Check existing primary credit transaction for this task in investor wallet
                $primaryCreditTx = $uTxs->where('transaction_type', 'credit')->first();
                $oldAmount = $primaryCreditTx ? floatval($primaryCreditTx->amount) : 0;
                $oldDescription = $primaryCreditTx ? $primaryCreditTx->description : '';

                $txRecordId = null;
                $actionLabel = '';

                if ($primaryCreditTx) {
                    // Adjust the existing transaction directly
                    $actionLabel = 'تعديل حركة العمولة الأصلية جماعية';
                    $oldAmountFormatted = number_format($oldAmount, 2);
                    $newAmountFormatted = number_format($expectedComm, 2);

                    $updatedDesc = $primaryCreditTx->description;
                    $noteSuffix = " [تم تسوية العمولة وتعديلها من {$oldAmountFormatted} إلى {$newAmountFormatted} ر.س بناءً على العقد{$deductionNote}" . ($reason ? " - السبب: {$reason}" : "") . " - بواسطة {$currentUser->name}]";
                    
                    if (!str_contains($updatedDesc, '[تم تسوية العمولة')) {
                        $updatedDesc = trim($updatedDesc) . $noteSuffix;
                    } else {
                        $updatedDesc = preg_replace('/\[تم تسوية العمولة.*?\]/', trim($noteSuffix), $updatedDesc);
                    }

                    $primaryCreditTx->amount = $expectedComm;
                    $primaryCreditTx->description = $updatedDesc;
                    $primaryCreditTx->save();

                    // Remove any extra adjustment transactions for this task in the wallet
                    $extraTxs = $uTxs->where('id', '!=', $primaryCreditTx->id);
                    foreach ($extraTxs as $extraTx) {
                        $extraTx->delete();
                    }

                    $txRecordId = (string) $primaryCreditTx->id;

                    if ($expectedComm > $actualNet) {
                        $totalCredited += ($expectedComm - $actualNet);
                    } else {
                        $totalDebited += ($actualNet - $expectedComm);
                    }

                } else {
                    // Create primary credit transaction if none ever existed
                    $actionLabel = 'تسجيل عمولة المهمة جماعية';
                    $baseDesc = "عمولة المهمة #{$taskId}{$deductionNote}";
                    if ($reason) {
                        $baseDesc .= " [تسوية عمولة بناءً على العقد - السبب: {$reason} - بواسطة {$currentUser->name}]";
                    }

                    $newTx = UserWalletTransaction::create([
                        'user_wallet_id'   => $userWallet->id,
                        'user_id'          => $currentUser->id,
                        'task_id'          => $taskId,
                        'transaction_type' => 'credit',
                        'amount'           => $expectedComm,
                        'description'      => $baseDesc,
                        'status'           => true,
                    ]);

                    $txRecordId = (string) $newTx->id;
                    $totalCredited += $expectedComm;
                }

                $logDesc = "تمت تسوية وتعديل عمولة المهمة #{$taskId} في نفس الحركة بنجاح (تسوية جماعية) للمستثمر ({$investor->name}): " .
                    "تم ضبط العمولة إلى " . number_format($expectedComm, 2) . " ر.س (المبلغ السابق: " . number_format($actualNet, 2) . " ر.س). " .
                    ($reason ? "السبب: {$reason} - " : "") . "بواسطة: {$currentUser->name}";

                Task_History::create([
                    'action_type' => 'updated',
                    'description' => $logDesc,
                    'task_id' => $taskId,
                    'user_id' => $currentUser->id,
                ]);

                ActivityLog::create([
                    'user_id' => $currentUser->id,
                    'action' => $actionLabel,
                    'table_name' => 'user_wallet_transactions',
                    'record_id' => $txRecordId,
                    'old_values' => [
                        'actual_net_commission' => $actualNet,
                        'expected_commission' => $expectedComm,
                        'description' => $oldDescription,
                    ],
                    'new_values' => [
                        'adjusted_amount' => $expectedComm,
                        'reason' => $reason,
                        'log' => $logDesc,
                    ],
                    'ip_address' => request()->ip(),
                ]);

                DB::commit();
                $settledCount++;

            } catch (Exception $e) {
                DB::rollBack();
                $errors[] = "المهمة #{$taskId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'status' => 1,
            'success' => "تم تسوية عمولات {$settledCount} مهمة بنجاح (إجمالي إيداعات: " . number_format($totalCredited, 2) . " ر.س | إجمالي خصومات: " . number_format($totalDebited, 2) . " ر.س)." . ($skippedCount > 0 ? " (تم تجاوز {$skippedCount} مهمة مطابقة مسبقاً)" : ""),
            'settled_count' => $settledCount,
            'skipped_count' => $skippedCount,
            'total_credited' => $totalCredited,
            'total_debited' => $totalDebited,
            'errors' => $errors,
        ]);
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

        // Unsettled tasks distribution by customer
        $allFundedTxs = InvestorWalletTransaction::whereNotNull('task_id')
            ->where('transaction_type', 'debit')
            ->get(['task_id', 'amount', 'investor_wallet_id', 'created_at']);

        $allRefundedTaskIds = InvestorWalletTransaction::whereNotNull('task_id')
            ->where('transaction_type', 'credit')
            ->whereIn('source_type', ['refund', 'capital_return'])
            ->pluck('task_id')
            ->unique()
            ->toArray();

        $unsettledFundingMap = [];
        foreach ($allFundedTxs as $f) {
            if (!in_array($f->task_id, $allRefundedTaskIds)) {
                if (!isset($unsettledFundingMap[$f->task_id])) {
                    $unsettledFundingMap[$f->task_id] = [
                        'task_id' => $f->task_id,
                        'funded_amount' => 0,
                        'wallet_id' => $f->investor_wallet_id,
                    ];
                }
                $unsettledFundingMap[$f->task_id]['funded_amount'] += floatval($f->amount);
            }
        }

        $allUnsettledTaskIds = array_keys($unsettledFundingMap);
        $unsettledTasksModels = Task::with(['customer', 'investor'])
            ->whereIn('id', $allUnsettledTaskIds)
            ->orderBy('id', 'desc')
            ->get();

        $customersUnsettled = [];
        foreach ($unsettledTasksModels as $task) {
            $cId = $task->customer_id ?? 0;
            $cName = $task->customer ? $task->customer->name : 'عميل غير محدد / نقدي';

            if (!isset($customersUnsettled[$cId])) {
                $customersUnsettled[$cId] = [
                    'customer_id' => $cId,
                    'customer_name' => $cName,
                    'customer_phone' => $task->customer ? ($task->customer->phone ?? ($task->customer->mobile ?? null)) : null,
                    'tasks_count' => 0,
                    'total_price' => 0,
                    'total_funded' => 0,
                    'investors' => [],
                    'statuses' => [],
                    'task_ids' => [],
                    'tasks' => [],
                ];
            }

            $fundedAmt = $unsettledFundingMap[$task->id]['funded_amount'] ?? floatval($task->total_price);
            $invName = $task->investor ? $task->investor->name : 'غير محدد';
            $taskPrice = floatval($task->total_price);

            $customersUnsettled[$cId]['tasks_count']++;
            $customersUnsettled[$cId]['total_price'] += $taskPrice;
            $customersUnsettled[$cId]['total_funded'] += $fundedAmt;
            if (!isset($customersUnsettled[$cId]['investors'][$invName])) {
                $customersUnsettled[$cId]['investors'][$invName] = [
                    'count' => 0,
                    'total_price' => 0,
                    'total_funded' => 0,
                ];
            }
            $customersUnsettled[$cId]['investors'][$invName]['count']++;
            $customersUnsettled[$cId]['investors'][$invName]['total_price'] += $taskPrice;
            $customersUnsettled[$cId]['investors'][$invName]['total_funded'] += $fundedAmt;

            $customersUnsettled[$cId]['statuses'][$task->status] = ($customersUnsettled[$cId]['statuses'][$task->status] ?? 0) + 1;
            $customersUnsettled[$cId]['task_ids'][] = $task->id;

            $customersUnsettled[$cId]['tasks'][] = [
                'id' => $task->id,
                'status' => $task->status,
                'total_price' => $taskPrice,
                'funded_amount' => $fundedAmt,
                'investor_name' => $invName,
                'date' => $task->created_at ? $task->created_at->format('Y-m-d') : '-',
            ];
        }

        uasort($customersUnsettled, function ($a, $b) {
            return $b['total_price'] <=> $a['total_price'];
        });

        $problematicTasks = $this->getProblematicTasks();

        return [
            'totals' => $totals,
            'investorsData' => $investorsData,
            'problematicTasks' => $problematicTasks,
            'customersUnsettled' => array_values($customersUnsettled),
            'canceledTasksAudit' => $this->getCanceledUnsettledTasks(),
            'commissionAudit' => $this->getCommissionAuditData(),
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

        $allTxs = InvestorWalletTransaction::whereIn('task_id', $taskIdsWithTransactions)
            ->with('wallet.investor')
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('task_id');

        $allTasks = Task::whereIn('id', $taskIdsWithTransactions)
            ->with(['investor', 'customer'])
            ->get()
            ->keyBy('id');

        // First pass: identify problematic task IDs
        $problematicCandidates = [];
        foreach ($taskIdsWithTransactions as $taskId) {
            $task = $allTasks->get($taskId);
            $txs = $allTxs->get($taskId, collect());

            $debits = $txs->where('transaction_type', 'debit');
            $credits = $txs->where('transaction_type', 'credit');

            $totalDebited = floatval($debits->sum('amount'));
            $totalCredited = floatval($credits->sum('amount'));
            $taskPrice = $task ? floatval($task->total_price) : 0;

            $hasIssue = false;
            if ($credits->count() > 0 && abs($totalDebited - $totalCredited) > 0.01) {
                $hasIssue = true;
            } elseif ($credits->count() == 0 && abs($totalDebited - $taskPrice) > 0.01 && $task) {
                $hasIssue = true;
            } elseif ($debits->count() > 1) {
                $hasIssue = true;
            }

            if ($hasIssue) {
                $problematicCandidates[] = $taskId;
            }
        }

        // Fetch histories ONLY for the problematic tasks in ONE query
        $historiesGrouped = Task_History::whereIn('task_id', $problematicCandidates)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('task_id');

        $problematicTasks = [];

        foreach ($problematicCandidates as $taskId) {
            $task = $allTasks->get($taskId);
            $txs = $allTxs->get($taskId, collect());

            $debits = $txs->where('transaction_type', 'debit');
            $credits = $txs->where('transaction_type', 'credit');

            $totalDebited = floatval($debits->sum('amount'));
            $totalCredited = floatval($credits->sum('amount'));
            $taskPrice = $task ? floatval($task->total_price) : 0;

            $issueType = '';
            $issueBadgeClass = 'bg-label-warning';
            $issueReasons = [];
            $canEditFunding = false;
            $canDeleteRefund = false;

            // Case 1: Settled task with difference between debited and credited
            if ($credits->count() > 0 && abs($totalDebited - $totalCredited) > 0.01) {
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
                if (empty($issueType)) {
                    $issueType = 'تمويل مكرر';
                    $issueBadgeClass = 'bg-label-danger';
                }
                $issueReasons[] = "تم تسجيل أكثر من حركة خصم تمويل لنفس المهمة (عدد {$debits->count()} حركات).";
                $canEditFunding = true;
            }

            $histories = $historiesGrouped->get($taskId, collect());

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
                $u = $d->wallet && $d->wallet->investor ? $d->wallet->investor : null;
                $funders[] = [
                    'user' => $u ? $u->name : "مستخدم #{$d->performed_by}",
                    'amount' => floatval($d->amount),
                    'date' => $d->created_at ? $d->created_at->format('Y-m-d H:i') : '',
                    'description' => $d->description,
                ];
            }

            $settlers = [];
            foreach ($credits as $c) {
                $u = $c->wallet && $c->wallet->investor ? $c->wallet->investor : null;
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
                if ($tx->transaction_type === 'credit' && !$firstCreditTxId) {
                    $firstCreditTxId = $tx->id;
                }
                $cleanTransactions[] = [
                    'id' => $tx->id,
                    'transaction_type' => $tx->transaction_type,
                    'amount' => floatval($tx->amount),
                    'performed_by' => $tx->performed_by ? "مستخدم #{$tx->performed_by}" : '-',
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

        usort($problematicTasks, function ($a, $b) {
            return abs($b['net_locked']) <=> abs($a['net_locked']);
        });

        return $problematicTasks;
    }

    /**
     * Get canceled tasks that still have locked capital in investor wallets or unreversed commissions.
     */
    public function getCanceledUnsettledTasks()
    {
        $fundedTaskIds = InvestorWalletTransaction::whereNotNull('task_id')
            ->where('transaction_type', 'debit')
            ->pluck('task_id')
            ->unique();

        $canceledTasks = Task::whereIn('id', $fundedTaskIds)
            ->whereIn('status', ['canceled', 'cancelled'])
            ->with(['investor', 'customer'])
            ->orderBy('id', 'desc')
            ->get();

        $list = [];
        $totalLockedCapital = 0;
        $totalUnreversedCommission = 0;

        foreach ($canceledTasks as $task) {
            // Capital check in investor_wallet_transactions
            $iTxs = InvestorWalletTransaction::where('task_id', $task->id)->get();
            $fundedAmount = floatval($iTxs->where('transaction_type', 'debit')->sum('amount'));
            $refundedAmount = floatval($iTxs->where('transaction_type', 'credit')->sum('amount'));
            $netLockedCapital = $fundedAmount - $refundedAmount;

            // Only consider tasks where netLockedCapital > 0 (i.e. not yet refunded to the investor)
            if ($netLockedCapital <= 0.001) {
                continue;
            }

            // Commission check in user_wallet_transactions
            $uTxs = UserWalletTransaction::where('task_id', $task->id)
                ->with('userWallet.user')
                ->get();

            $creditedCommission = floatval($uTxs->where('transaction_type', 'credit')->sum('amount'));
            $debitedCommission = floatval($uTxs->where('transaction_type', 'debit')->sum('amount'));
            $netCommission = $creditedCommission - $debitedCommission;

            // Group commissions by user
            $commissionBreakdown = [];
            foreach ($uTxs as $utx) {
                $user = $utx->userWallet?->user;
                $userId = $user ? $user->id : 0;
                $userName = $user ? $user->name : ('محفظة #' . $utx->user_wallet_id);
                $isInvestor = ($task->investor_id && $userId == $task->investor_id);

                if (!isset($commissionBreakdown[$userId])) {
                    $commissionBreakdown[$userId] = [
                        'user_id' => $userId,
                        'user_name' => $userName,
                        'is_investor' => $isInvestor,
                        'credited' => 0,
                        'debited' => 0,
                        'net' => 0,
                    ];
                }

                if ($utx->transaction_type === 'credit') {
                    $commissionBreakdown[$userId]['credited'] += floatval($utx->amount);
                } else {
                    $commissionBreakdown[$userId]['debited'] += floatval($utx->amount);
                }
                $commissionBreakdown[$userId]['net'] = $commissionBreakdown[$userId]['credited'] - $commissionBreakdown[$userId]['debited'];
            }

            // Cancellation details from Task_History
            $cancelHistory = Task_History::where('task_id', $task->id)
                ->where(function($q) {
                    $q->where('action_type', 'canceled')
                      ->orWhere('description', 'like', '%canceled%')
                      ->orWhere('description', 'like', '%إلغاء%');
                })
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->first();

            $canceledBy = $cancelHistory && $cancelHistory->user ? $cancelHistory->user->name : 'غير محدد';
            $canceledAt = $cancelHistory && $cancelHistory->created_at ? $cancelHistory->created_at->format('Y-m-d H:i') : ($task->updated_at ? $task->updated_at->format('Y-m-d H:i') : '-');
            $cancelDescription = $cancelHistory ? $cancelHistory->description : '';

            $totalLockedCapital += $netLockedCapital;
            $totalUnreversedCommission += max(0, $netCommission);

            $list[] = [
                'task_id' => $task->id,
                'task_price' => floatval($task->total_price),
                'investor_id' => $task->investor_id,
                'investor_name' => $task->investor ? $task->investor->name : 'غير محدد',
                'customer_id' => $task->customer_id,
                'customer_name' => $task->customer ? $task->customer->name : 'عميل نقدي / غير محدد',
                'funded_amount' => $fundedAmount,
                'refunded_amount' => $refundedAmount,
                'net_locked_capital' => $netLockedCapital,
                'credited_commission' => $creditedCommission,
                'debited_commission' => $debitedCommission,
                'net_commission' => $netCommission,
                'has_unreversed_commission' => ($netCommission > 0.001),
                'commission_breakdown' => array_values($commissionBreakdown),
                'canceled_by' => $canceledBy,
                'canceled_at' => $canceledAt,
                'cancel_description' => $cancelDescription,
                'status' => $task->status,
                'created_at' => $task->created_at ? $task->created_at->format('Y-m-d') : '-',
            ];
        }

        return [
            'tasks' => $list,
            'count' => count($list),
            'total_locked_capital' => $totalLockedCapital,
            'total_unreversed_commission' => $totalUnreversedCommission,
        ];
    }

    /**
     * Audit of tasks commissions received by investors vs contract entitlement.
     */
    public function getCommissionAuditData()
    {
        // 1. Get task IDs with investor involvement
        $taskIds = InvestorWalletTransaction::whereNotNull('task_id')
            ->where('transaction_type', 'debit')
            ->pluck('task_id')
            ->merge(Task::whereNotNull('investor_id')->pluck('id'))
            ->merge(UserWalletTransaction::whereNotNull('task_id')->pluck('task_id'))
            ->unique()
            ->values();

        $excludedStatuses = ['canceled', 'cancelled', 'canceld', 'refund', 'refound', 'refunded'];

        // 2. Preload tasks with ad, customer, userCommissions, brokers (excluding canceled & refunded tasks)
        $tasks = Task::whereIn('id', $taskIds)
            ->whereNotIn('status', $excludedStatuses)
            ->whereNotIn('payment_status', ['refund', 'canceled'])
            ->with(['investor.userWallet', 'ad', 'customer.userCommissions.user', 'brokers', 'driver.brokers'])
            ->get();

        // 3. Preload all contracts with brokers
        $contracts = InvestmentContract::with(['brokers'])->get();

        // 4. Preload all user wallet transactions for these tasks in ONE query
        $uWalletTxs = UserWalletTransaction::whereIn('task_id', $taskIds)
            ->select(['id', 'user_wallet_id', 'task_id', 'transaction_type', 'amount'])
            ->get()
            ->groupBy(fn($tx) => $tx->user_wallet_id . '_' . $tx->task_id);

        $matchedCount = 0;
        $overpaidCount = 0;
        $underpaidCount = 0;
        $uncreditedCount = 0;
        $noPlatformCommCount = 0;

        $totalExpected = 0;
        $totalActual = 0;
        $totalOverpaid = 0;
        $totalUnderpaid = 0;

        $items = [];

        foreach ($tasks as $task) {
            if (in_array(strtolower($task->status), $excludedStatuses) || in_array(strtolower($task->payment_status), ['refund', 'canceled'])) {
                continue;
            }

            $investor = $task->investor;
            if (!$investor) continue;

            $uWallet = $investor->userWallet;
            $walletId = $uWallet ? $uWallet->id : null;

            // Match contract
            $contract = $contracts->where('user_id', $investor->id)
                ->filter(fn($c) => $c->isTaskWithinContractPeriod($task->created_at))
                ->first() ?? $contracts->where('user_id', $investor->id)->first();

            $expectedComm = 0;
            $contractRate = 'بدون عقد';
            $platformCut = 0;
            $sourceDesc = '';

            if ($contract) {
                $contractRate = $contract->commission_type === 'percentage' 
                    ? "{$contract->commission_value}%" 
                    : "ثابت {$contract->commission_value} ر.س";

                $calc = $task->calculateInvestorCommission($contract);
                $expectedComm = (float) $calc['net_investor_commission'];
                $platformCut = (float) $calc['platform_gross'];

                // تفصيل مصدر العمولة والخصومات
                $baseDesc = '';
                if ($task->ad) {
                    if ($task->ad->service_commission_type == 1) {
                        $baseDesc = 'ثابت بالإعلان (' . number_format($platformCut, 2) . ' ر.س)';
                    } else {
                        $pct = (float) $task->ad->service_commission;
                        $baseDesc = "نسبة {$pct}% بالإعلان (" . number_format($platformCut, 2) . ' ر.س)';
                    }
                } else {
                    $baseDesc = 'يدوي بالمهمة (' . number_format($platformCut, 2) . ' ر.س)';
                }

                if ($calc['has_deductions']) {
                    $totalDeducted = $calc['total_deductions_from_task'] + $calc['contract_investor_brokers_total'];
                    $sourceDesc = $baseDesc . ' [خصم شركاء: -' . number_format($totalDeducted, 2) . ' | صافي الأساس: ' . number_format($calc['net_task_commission'], 2) . ' ر.س]';
                } else {
                    $sourceDesc = $baseDesc;
                }
            } else {
                if ($task->ad) {
                    if ($task->ad->service_commission_type == 1) {
                        $platformCut = (float) $task->ad->service_commission;
                        $sourceDesc = 'ثابت بالإعلان (' . number_format($platformCut, 2) . ' ر.س)';
                    } else {
                        $pct = (float) $task->ad->service_commission;
                        $platformCut = ($task->total_price * $pct) / 100;
                        $sourceDesc = "نسبة {$pct}% بالإعلان (" . number_format($platformCut, 2) . ' ر.س)';
                    }
                } else {
                    $platformCut = (float) $task->commission;
                    $sourceDesc = 'يدوي بالمهمة (' . number_format($platformCut, 2) . ' ر.س)';
                }
            }

            // Actual commission from preloaded group
            $actualNet = 0;
            $actualCredited = 0;
            $actualDebited = 0;
            if ($walletId) {
                $txs = $uWalletTxs->get($walletId . '_' . $task->id, collect());
                $actualCredited = (float) $txs->where('transaction_type', 'credit')->sum('amount');
                $actualDebited = (float) $txs->where('transaction_type', 'debit')->sum('amount');
                $actualNet = $actualCredited - $actualDebited;
            }

            $diff = $actualNet - $expectedComm;
            $totalExpected += $expectedComm;
            $totalActual += $actualNet;

            $status = 'matched';
            $statusBadge = 'bg-label-success';
            $statusLabel = 'مطابقة تماماً';

            if (!$contract || $platformCut <= 0) {
                $status = 'no_platform_commission';
                $statusBadge = 'bg-label-secondary';
                $statusLabel = 'لا توجد عمولة للمهمة';
                $noPlatformCommCount++;
            } elseif (abs($diff) <= 0.05) {
                $status = 'matched';
                $statusBadge = 'bg-label-success';
                $statusLabel = 'مطابقة تماماً';
                $matchedCount++;
            } elseif ($actualNet > $expectedComm) {
                $status = 'overpaid';
                $statusBadge = 'bg-label-danger';
                $statusLabel = 'عمولة زائدة عن العقد';
                $overpaidCount++;
                $totalOverpaid += $diff;
            } elseif ($actualNet < $expectedComm && $actualNet > 0) {
                $status = 'underpaid';
                $statusBadge = 'bg-label-warning';
                $statusLabel = 'عمولة ناقصة عن العقد';
                $underpaidCount++;
                $totalUnderpaid += abs($diff);
            } elseif ($expectedComm > 0 && $actualNet <= 0) {
                $status = 'uncredited';
                $statusBadge = 'bg-label-danger';
                $statusLabel = 'لم تُصرف عمولة';
                $uncreditedCount++;
                $totalUnderpaid += $expectedComm;
            }

            $items[] = [
                'task_id' => $task->id,
                'task_price' => (float)$task->total_price,
                'task_status' => $task->status,
                'investor_id' => $investor->id,
                'investor_name' => $investor->name,
                'customer_name' => $task->customer ? $task->customer->name : 'عميل نقدي / غير محدد',
                'platform_commission' => $platformCut,
                'platform_commission_source' => $sourceDesc,
                'contract_rate' => $contractRate,
                'expected_commission' => $expectedComm,
                'actual_commission' => $actualNet,
                'credited_amount' => $actualCredited,
                'debited_amount' => $actualDebited,
                'diff' => $diff,
                'status' => $status,
                'status_badge' => $statusBadge,
                'status_label' => $statusLabel,
            ];
        }

        // Sort: discrepant first (by absolute diff desc), then matched
        usort($items, function($a, $b) {
            $isADiscrepant = in_array($a['status'], ['overpaid', 'underpaid', 'uncredited']);
            $isBDiscrepant = in_array($b['status'], ['overpaid', 'underpaid', 'uncredited']);

            if ($isADiscrepant && !$isBDiscrepant) return -1;
            if (!$isADiscrepant && $isBDiscrepant) return 1;

            return abs($b['diff']) <=> abs($a['diff']);
        });

        $totalDiscrepancies = $overpaidCount + $underpaidCount + $uncreditedCount;

        return [
            'tasks' => $items,
            'summary' => [
                'total_audited' => count($items),
                'matched_count' => $matchedCount,
                'discrepant_count' => $totalDiscrepancies,
                'overpaid_count' => $overpaidCount,
                'underpaid_count' => $underpaidCount,
                'uncredited_count' => $uncreditedCount,
                'no_platform_comm_count' => $noPlatformCommCount,
                'total_expected' => $totalExpected,
                'total_actual' => $totalActual,
                'total_overpaid' => $totalOverpaid,
                'total_underpaid' => $totalUnderpaid,
                'net_diff' => $totalActual - $totalExpected,
            ]
        ];
    }
}
