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
use Yajra\DataTables\Facades\DataTables;

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
        $claims = TaskClaimRequest::with(['task.customer', 'driver', 'reviewer'])
            ->select('task_claim_requests.*');

        return DataTables::of($claims)
            ->addColumn('task_number', function ($claim) {
                return $claim->task->customer_task_number ?? "#{$claim->task_id}";
            })
            ->addColumn('driver_name', function ($claim) {
                return $claim->driver->name ?? 'N/A';
            })
            ->addColumn('customer_name', function ($claim) {
                return $claim->task->customer->name ?? 'N/A';
            })
            ->editColumn('status', function ($claim) {
                $class = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger'
                ][$claim->status] ?? 'secondary';

                return '<span class="badge badge-' . $class . '">' . ucfirst($claim->status) . '</span>';
            })
            ->addColumn('actions', function ($claim) {
                if ($claim->status === 'pending') {
                    return '
                        <button class="btn btn-sm btn-success approve-claim" data-id="' . $claim->id . '">Approve</button>
                        <button class="btn btn-sm btn-danger reject-claim" data-id="' . $claim->id . '">Reject</button>
                    ';
                }
                return '-';
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
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
