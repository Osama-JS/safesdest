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
use Illuminate\Support\Str;
use Carbon\Carbon;

class TaskClaimsController extends Controller
{
    /**
     * Display the list of task claims
     */
    public function index()
    {
        $stats = [
            'total' => TaskClaimRequest::count(),
            'pending' => TaskClaimRequest::where('status', 'pending')->count(),
            'approved' => TaskClaimRequest::where('status', 'approved')->count(),
            'rejected' => TaskClaimRequest::where('status', 'rejected')->count(),
            'today' => TaskClaimRequest::whereDate('created_at', today())->count(),
            'today_pending' => TaskClaimRequest::where('status', 'pending')->whereDate('created_at', today())->count(),
        ];

        return view('admin.task-claims.index', compact('stats'));
    }

    /**
     * Get data for DataTables
     */
    public function getData(Request $request)
    {
        $query = TaskClaimRequest::with(['task.customer', 'driver', 'reviewer'])
            ->select('task_claim_requests.*');

        // Status filter
        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        // Total count before filtering
        $totalData = TaskClaimRequest::count();

        // Search logic
        if (!empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('driver', function($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('task', function($tq) use ($search) {
                      $tq->where('customer_task_number', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('task.customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
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

            $statusIcon = [
                'pending' => 'ti-clock',
                'approved' => 'ti-check',
                'rejected' => 'ti-x'
            ][$claim->status] ?? 'ti-minus';

            $statusLabel = [
                'pending' => __('Pending'),
                'approved' => __('Approved'),
                'rejected' => __('Rejected')
            ][$claim->status] ?? ucfirst($claim->status);

            $actions = '';
            if ($claim->status === 'pending') {
                $actions = '
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-icon btn-label-success approve-claim" data-id="' . $claim->id . '" data-bs-toggle="tooltip" title="' . __('Approve') . '">
                            <i class="ti ti-check ti-sm"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-label-danger reject-claim" data-id="' . $claim->id . '" data-bs-toggle="tooltip" title="' . __('Reject') . '">
                            <i class="ti ti-x ti-sm"></i>
                        </button>
                    </div>
                ';
            } else {
                $reviewerName = $claim->reviewer?->name ?? '-';
                $actions = '<span class="text-muted small"><i class="ti ti-user-check me-1"></i>' . $reviewerName . '</span>';
            }

            // Driver info with avatar
            $driverName = $claim->driver?->name ?? 'N/A';
            $driverImage = $claim->driver?->image ? asset('storage/' . $claim->driver->image) : asset('assets/img/person.png');
            $driverHtml = '
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-2">
                        <img src="' . $driverImage . '" alt="' . e($driverName) . '" class="rounded-circle" onerror="this.src=\'' . asset('assets/img/person.png') . '\'">
                    </div>
                    <span class="fw-medium">' . e($driverName) . '</span>
                </div>
            ';

            $data[] = [
                'id' => '',
                'task_number' => '<span class="fw-medium text-primary">' . e($claim->task?->customer_task_number ?? "#{$claim->task_id}") . '</span>',
                'driver_name' => $driverHtml,
                'customer_name' => e($claim->task?->customer?->name ?? 'N/A'),
                'note' => $claim->note
                    ? '<span class="text-truncate d-inline-block" style="max-width: 150px;" data-bs-toggle="tooltip" title="' . e($claim->note) . '">' . e(Str::limit($claim->note, 30)) . '</span>'
                    : '<span class="text-muted">-</span>',
                'status' => '<span class="badge bg-label-' . $statusClass . '"><i class="ti ' . $statusIcon . ' me-1 ti-xs"></i>' . $statusLabel . '</span>',
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
                'status' => 'assign',
                'pending_driver_id' => null,
            ]);

            // Log assignment in task history
            $task->history()->create([
                'action_type' => 'assign',
                'description' => 'تم إسناد المهمة عبر الموافقة على طلب الحصول - السائق: ' . ($claim->driver?->name ?? 'N/A'),
                'user_id' => Auth::id(),
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
