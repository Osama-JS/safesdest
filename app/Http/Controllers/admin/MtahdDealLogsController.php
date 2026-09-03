<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MtahdDealLog;
use App\Services\MtahdService;
use Carbon\Carbon;
use Exception;

class MtahdDealLogsController extends Controller
{
    protected MtahdService $mtahdService;

    public function __construct(MtahdService $mtahdService)
    {
        $this->middleware('permission:view_mtahd_deal_logs')->only(['index', 'data', 'show']);
        $this->middleware('permission:manage_mtahd_deals')->only(['releaseDeal', 'cancelDeal', 'checkDealStatus']);
        $this->mtahdService = $mtahdService;
    }

    /**
     * Display Mtahd Deal Logs Page with Statistics
     */
    public function index(Request $request)
    {
        $totalOperations = MtahdDealLog::count();
        $successfulOperations = MtahdDealLog::where('status', 'success')->count();
        $failedOperations = MtahdDealLog::where('status', 'failed')->count();
        $releasedFundsCount = MtahdDealLog::where('action', 'release_funds')->where('status', 'success')->count();
        $cancelledDealsCount = MtahdDealLog::where('action', 'cancel_deal')->where('status', 'success')->count();
        $totalAmount = MtahdDealLog::where('action', 'create_deal')->where('status', 'success')->sum('amount');

        return view('admin.mtahd.logs', compact(
            'totalOperations',
            'successfulOperations',
            'failedOperations',
            'releasedFundsCount',
            'cancelledDealsCount',
            'totalAmount'
        ));
    }

    /**
     * Get Server-Side Data for DataTables
     */
    public function data(Request $request)
    {
        $query = MtahdDealLog::with(['task', 'performedBy'])->latest('id');

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Action
        if ($request->filled('action_filter')) {
            $query->where('action', $request->action_filter);
        }

        // Filter by Date Range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate = Carbon::parse($request->to_date)->endOfDay();
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        // Global Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('deal_number', 'like', "%{$search}%")
                  ->orWhere('deal_id', 'like', "%{$search}%")
                  ->orWhere('task_id', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('buyer_info', 'like', "%{$search}%")
                  ->orWhere('seller_info', 'like', "%{$search}%")
                  ->orWhere('error_message', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $totalRecords = MtahdDealLog::count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $logs = $query->skip($start)->take($length)->get();

        $data = [];
        foreach ($logs as $log) {
            $data[] = [
                'id'              => $log->id,
                'created_at'      => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
                'created_at_human'=> $log->created_at ? $log->created_at->diffForHumans() : '-',
                'deal_number'     => $log->deal_number ?: '-',
                'deal_id'         => $log->deal_id ?: '-',
                'task_id'         => $log->task_id ? "#{$log->task_id}" : '-',
                'action'          => $log->action,
                'action_label'    => $log->action_label,
                'status'          => $log->status,
                'status_badge'    => $log->status_badge,
                'amount'          => $log->amount ? number_format($log->amount, 2) . ' ' . $log->currency : '-',
                'http_status'     => $log->http_status ?: '-',
                'buyer_info'      => $log->buyer_info ?: '-',
                'seller_info'     => $log->seller_info ?: '-',
                'error_message'   => $log->error_message ?: null,
                'performed_by'    => $log->performedBy ? $log->performedBy->name : 'النظام (System)',
                'ip_address'      => $log->ip_address ?: '-',
                'notes'           => $log->notes ?: '-',
            ];
        }

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Get Log Details with Request & Response JSON payloads
     */
    public function show($id)
    {
        $log = MtahdDealLog::with(['task', 'performedBy'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'log'    => [
                'id'               => $log->id,
                'deal_number'      => $log->deal_number,
                'deal_id'          => $log->deal_id,
                'task_id'          => $log->task_id,
                'action'           => $log->action,
                'action_label'     => $log->action_label,
                'status'           => $log->status,
                'status_badge'     => $log->status_badge,
                'amount'           => $log->amount,
                'currency'         => $log->currency,
                'buyer_info'       => $log->buyer_info,
                'seller_info'      => $log->seller_info,
                'http_status'      => $log->http_status,
                'error_message'    => $log->error_message,
                'request_payload'  => $log->request_payload,
                'response_payload' => $log->response_payload,
                'ip_address'       => $log->ip_address,
                'performed_by'     => $log->performedBy ? $log->performedBy->name : 'النظام (System)',
                'notes'            => $log->notes,
                'created_at'       => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
            ]
        ]);
    }

    /**
     * Trigger Live Release of Funds for a Deal
     */
    public function releaseDeal(Request $request, $dealNumber)
    {
        $taskId = $request->input('task_id');
        $result = $this->mtahdService->releaseFunds($dealNumber, $request->except(['_token', 'task_id']), $taskId);

        return response()->json($result);
    }

    /**
     * Trigger Live Cancellation of a Deal
     */
    public function cancelDeal(Request $request, $dealNumber)
    {
        $reason = $request->input('reason', 'إلغاء بواسطة مدير النظام');
        $taskId = $request->input('task_id');
        $result = $this->mtahdService->cancelDeal($dealNumber, $reason, $taskId);

        return response()->json($result);
    }

    /**
     * Sync and Query Live Deal Status from Amnn API
     */
    public function checkDealStatus(Request $request, $dealNumber)
    {
        $result = $this->mtahdService->getDealDetails($dealNumber, true);

        return response()->json($result);
    }

    /**
     * Create a Mtahd Escrow Deal for a specific Task directly from Admin
     */
    public function createDealForTask(Request $request, $taskId)
    {
        try {
            if (!\App\Services\MtahdService::isServiceEnabled()) {
                return response()->json([
                    'status' => false,
                    'error'  => 'خدمة الضمان المالي (متعهد) معطلة حالياً في إعدادات النظام'
                ], 400);
            }

            $task = \App\Models\Task::findOrFail($taskId);
            $escrowService = app(\App\Services\MtahdEscrowTaskService::class);
            $result = $escrowService->createEscrowDealForTask($task);

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error'  => $e->getMessage()
            ], 500);
        }
    }
}
