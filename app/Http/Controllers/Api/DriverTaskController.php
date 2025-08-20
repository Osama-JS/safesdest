<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DriverTaskController extends Controller
{
    /**
     * Get driver's tasks
     */
    public function index(Request $request)
    {
        try {
            $driver = $request->user();

            // Validate query parameters
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:pending,accepted,in_progress,completed,cancelled',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $perPage = $request->get('per_page', 10);
            $status = $request->get('status');

            // Build query
            $query = Task::with(['customer', 'pickup', 'delivery']);
            // Filter by status if provided
            if ($status) {
                if ($status === 'pending') {
                    $query->where('pending_driver_id', $driver->id)
                          ->whereNull('driver_id');
                } else {
                    $query->where('driver_id', $driver->id);

                    switch ($status) {
                        case 'accepted':
                            $query->where('status', 'accepted');
                            break;
                        case 'in_progress':
                            $query->whereIn('status', ['picked_up', 'in_transit']);
                            break;
                        case 'completed':
                            $query->where('status', 'delivered');
                            break;
                        case 'cancelled':
                            $query->where('status', 'cancelled');
                            break;
                    }
                }
            }

            $tasks = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
                'data' => [
                    'tasks' => $tasks->items(),
                    'pagination' => [
                        'current_page' => $tasks->currentPage(),
                        'last_page' => $tasks->lastPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                        'from' => $tasks->firstItem(),
                        'to' => $tasks->lastItem()
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get driver tasks error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific task details
     */
    public function show(Request $request, $taskId)
    {
        try {
            $driver = $request->user();

            $task = Task::with([
                'customer',
                'pickup_point',
                'delivery_point',
                'task_points',
                'driver',
                'team'
            ])->where(function ($q) use ($driver) {
                $q->where('driver_id', $driver->id)
                  ->orWhere('pending_driver_id', $driver->id);
            })->find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task details retrieved successfully',
                'data' => [
                    'task' => [
                    'id' => $task->id,
                    'customer' => [
                        'name' => $task->customer->name ?? 'Unknown',
                        'phone' => $task->customer->phone ?? null,
                        'email' => $task->customer->email ?? null
                    ],
                    'pickup_point' => $task->pickup_point ? [
                        'address' => $task->pickup_point->address,
                        'latitude' => $task->pickup_point->latitude,
                        'longitude' => $task->pickup_point->longitude,
                        'contact_name' => $task->pickup_point->contact_name,
                        'contact_phone' => $task->pickup_point->contact_phone
                    ] : null,
                    'delivery_point' => $task->delivery_point ? [
                        'address' => $task->delivery_point->address,
                        'latitude' => $task->delivery_point->latitude,
                        'longitude' => $task->delivery_point->longitude,
                        'contact_name' => $task->delivery_point->contact_name,
                        'contact_phone' => $task->delivery_point->contact_phone
                    ] : null,
                    'total_price' => $task->total_price,
                    'commission' => $task->commission,
                    'status' => $task->status,
                    'notes' => $task->notes,
                    'created_at' => $task->created_at,
                    'accepted_at' => $task->accepted_at,
                    'completed_at' => $task->completed_at,
                    'payment_method' => $task->payment_method,
                    'payment_status' => $task->payment_status,
                    'items' => $task->additional_data['items'] ?? [],
                    'special_instructions' => $task->additional_data['special_instructions'] ?? null
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get task details error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get task details'
            ], 500);
        }
    }

    /**
     * Accept a task
     */
    public function accept(Request $request, $taskId)
    {
        try {
            $driver = $request->user();

            DB::beginTransaction();

            $task = Task::where('pending_driver_id', $driver->id)
                       ->whereNull('driver_id')
                       ->find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or already assigned'
                ], 404);
            }

            // Check if driver is available
            if (!$driver->free || !$driver->online) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver is not available'
                ], 400);
            }

            // Accept the task
            $task->update([
                'driver_id' => $driver->id,
                'status' => 'accepted',
                'accepted_at' => now(),
                'pending_driver_id' => null
            ]);

            // Update driver status
            $driver->update([
                'free' => false,
                'last_activity_at' => now()
            ]);

            DB::commit();

            Log::info('Task accepted by driver', [
                'task_id' => $taskId,
                'driver_id' => $driver->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task accepted successfully',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'accepted_at' => $task->accepted_at
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Accept task error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept task'
            ], 500);
        }
    }

    /**
     * Reject a task
     */
    public function reject(Request $request, $taskId)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = Task::where('pending_driver_id', $driver->id)
                       ->whereNull('driver_id')
                       ->find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Remove driver from pending
            $task->update([
                'pending_driver_id' => null,
                'distribution_attempts' => $task->distribution_attempts + 1
            ]);

            // Log rejection reason if provided
            if ($request->reason) {
                Log::info('Task rejected by driver', [
                    'task_id' => $taskId,
                    'driver_id' => $driver->id,
                    'reason' => $request->reason
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task rejected successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Reject task error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject task'
            ], 500);
        }
    }

    /**
     * Update task status
     */
    public function updateStatus(Request $request, $taskId)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:picked_up,in_transit,delivered',
                'notes' => 'nullable|string|max:1000',
                'location' => 'nullable|array',
                'location.latitude' => 'nullable|numeric|between:-90,90',
                'location.longitude' => 'nullable|numeric|between:-180,180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $task = Task::where('driver_id', $driver->id)
                       ->whereIn('status', ['accepted', 'picked_up', 'in_transit'])
                       ->find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or cannot be updated'
                ], 404);
            }

            $updateData = [
                'status' => $request->status,
                'last_activity_at' => now()
            ];

            if ($request->notes) {
                $updateData['notes'] = $request->notes;
            }

            // Set completion time if delivered
            if ($request->status === 'delivered') {
                $updateData['completed_at'] = now();

                // Free up the driver
                $driver->update([
                    'free' => true,
                    'last_activity_at' => now()
                ]);
            }

            $task->update($updateData);

            // Update driver location if provided
            if ($request->has('location') && $request->location) {
                $driver->update([
                    'longitude' => $request->location['longitude'],
                    'altitude' => $request->location['latitude'],
                    'last_seen_at' => now()
                ]);
            }

            DB::commit();

            Log::info('Task status updated', [
                'task_id' => $taskId,
                'driver_id' => $driver->id,
                'new_status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'notes' => $task->notes,
                    'completed_at' => $task->completed_at
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Update task status error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status'
            ], 500);
        }
    }

    /**
     * Get task history
     */
    public function history(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $perPage = $request->get('per_page', 10);

            $query = Task::with(['customer', 'pickup_point', 'delivery_point'])
                ->where('driver_id', $driver->id)
                ->where('status', 'delivered');

            // Date range filter
            if ($request->from) {
                $query->whereDate('completed_at', '>=', $request->from);
            }
            if ($request->to) {
                $query->whereDate('completed_at', '<=', $request->to);
            }

            $tasks = $query->orderBy('completed_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'tasks' => $tasks->items(),
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get task history error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get task history'
            ], 500);
        }
    }

    /**
     * Get pending task assigned to driver
     */
    public function getPendingTask(Request $request)
    {
        try {
            $driver = $request->user();

            $pendingTask = Task::with(['customer', 'pickup', 'delivery'])
                ->where('pending_driver_id', $driver->id)
                ->whereNull('driver_id')
                ->where('status', 'pending')
                ->first();

            if (!$pendingTask) {
                return response()->json([
                    'success' => true,
                    'message' => 'No pending task found',
                    'task' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pending task retrieved successfully',
                'task' => [
                    'id' => $pendingTask->id,
                    'type' => $pendingTask->type,
                    'status' => $pendingTask->status,
                    'pickup_address' => $pendingTask->pickup_address,
                    'delivery_address' => $pendingTask->delivery_address,
                    'amount' => $pendingTask->amount,
                    'notes' => $pendingTask->notes,
                    'created_at' => $pendingTask->created_at,
                    'customer' => $pendingTask->customer ? [
                        'id' => $pendingTask->customer->id,
                        'name' => $pendingTask->customer->name,
                        'phone' => $pendingTask->customer->phone,
                    ] : null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get pending task error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error occurred'
            ], 500);
        }
    }

    /**
     * Accept pending task
     */
    public function acceptTask(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'task_id' => 'required|integer|exists:tasks,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = Task::where('id', $request->task_id)
                ->where('pending_driver_id', $driver->id)
                ->whereNull('driver_id')
                ->where('status', 'pending')
                ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or already assigned'
                ], 404);
            }

            // Accept the task
            $task->update([
                'driver_id' => $driver->id,
                'pending_driver_id' => null,
                'status' => 'accepted',
                'accepted_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task accepted successfully',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'accepted_at' => $task->accepted_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Accept task error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown',
                'task_id' => $request->task_id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error occurred'
            ], 500);
        }
    }

    /**
     * Reject pending task
     */
    public function rejectTask(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'task_id' => 'required|integer|exists:tasks,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = Task::where('id', $request->task_id)
                ->where('pending_driver_id', $driver->id)
                ->whereNull('driver_id')
                ->where('status', 'pending')
                ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or already assigned'
                ], 404);
            }

            // Clear pending driver and find next driver
            $task->update([
                'pending_driver_id' => null
            ]);

            // TODO: Implement logic to assign to next nearest driver
            // This would involve finding the next closest available driver
            // and setting them as pending_driver_id

            return response()->json([
                'success' => true,
                'message' => 'Task rejected successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Reject task error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown',
                'task_id' => $request->task_id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error occurred'
            ], 500);
        }
    }
}
