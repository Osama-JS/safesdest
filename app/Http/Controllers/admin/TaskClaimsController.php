<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskClaimRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskClaimsController extends Controller
{
    /**
     * Display the list of task claims
     */
    public function index()
    {
        return view('admin.task-claims.index');
    }

    /**
     * Get data for DataTables
     */
    public function getData(Request $request)
    {
        $query = TaskClaimRequest::with(['task.customer', 'driver', 'reviewer'])
            ->select('task_claim_requests.*');

        // Total count before filtering
        $totalData = $query->count();

        // Search logic if needed (optional since JS might handle filtering if not large)
        if ($request->search['value']) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('driver', function($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('task', function($tq) use ($search) {
                      $tq->where('customer_task_number', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                  });
            });
        }

        $totalFiltered = $query->count();

        // Order logic
        $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 WHEN status = 'approved' THEN 2 ELSE 3 END ASC")
            ->orderBy('created_at', 'desc');

        // Pagination
        $limit = $request->input('length') ?? 10;
        $start = $request->input('start') ?? 0;

        $claims = $query->offset($start)
            ->limit($limit)
            ->get();

        $data = [];
        foreach ($claims as $claim) {
            $statusClass = [
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger'
            ][$claim->status] ?? 'secondary';

            $actions = '';
            if ($claim->status === 'pending') {
                $actions = '
                    <button class="btn btn-sm btn-success approve-claim" data-id="' . $claim->id . '">Approve</button>
                    <button class="btn btn-sm btn-danger reject-claim" data-id="' . $claim->id . '">Reject</button>
                ';
            } else {
                $actions = '-';
            }

            $data[] = [
                'id' => '', // Placeholder for control column
                'task_number' => $claim->task?->customer_task_number ?? "#{$claim->task_id}",
                'driver_name' => $claim->driver?->name ?? 'N/A',
                'customer_name' => $claim->task?->customer?->name ?? 'N/A',
                'status' => '<span class="badge bg-label-' . $statusClass . '">' . ucfirst($claim->status) . '</span>',
                'created_at' => $claim->created_at->toIso8601String(),
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Approve a task claim
     */
    public function approve(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $claim = TaskClaimRequest::findOrFail($id);

            if ($claim->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Claim is not pending'], 400);
            }

            $task = $claim->task;

            // Check if task is still available
            if ($task->driver_id !== null) {
                return response()->json(['success' => false, 'message' => 'Task is already assigned to another driver'], 400);
            }

            // Assign driver to task
            $task->update([
                'driver_id' => $claim->driver_id,
            ]);

            // Update claim status
            $claim->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_note' => $request->note
            ]);

            // Automatically reject other pending claims for this task
            TaskClaimRequest::where('task_id', $task->id)
                ->where('id', '!=', $claim->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'admin_note' => 'Automatically rejected as another driver was approved',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now()
                ]);

            DB::commit();

            // Notify Driver
            try {
                $notificationService = new NotificationService();
                $notificationService->sendNewTaskNotificationToDriver($claim->driver, $task);
            } catch (\Exception $e) {
                Log::error('Notification failed after claim approval: ' . $e->getMessage());
            }

            return response()->json(['success' => true, 'message' => 'Claim approved successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve claim error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to approve claim'], 500);
        }
    }

    /**
     * Reject a task claim
     */
    public function reject(Request $request, $id)
    {
        try {
            $claim = TaskClaimRequest::findOrFail($id);

            if ($claim->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Claim is not pending'], 400);
            }

            $claim->update([
                'status' => 'rejected',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_note' => $request->note
            ]);

            // Notify Driver
            try {
                $notificationService = new NotificationService();
                $title = "تم رفض طلبك";
                $body = "نعتذر، تم رفض طلبك للحصول على المهمة #" . ($claim->task->customer_task_number ?? $claim->task_id);
                $notificationService->send('driver', [$claim->driver_id], $title, $body, null, null, null, 'claim_rejected');
            } catch (\Exception $e) {
                Log::error('Notification failed after claim rejection: ' . $e->getMessage());
            }

            return response()->json(['success' => true, 'message' => 'Claim rejected successfully']);

        } catch (\Exception $e) {
            Log::error('Reject claim error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to reject claim'], 500);
        }
    }
}
