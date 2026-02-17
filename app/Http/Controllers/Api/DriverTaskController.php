<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Driver;
use App\Models\Task_History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\PdfService;
use App\Services\MapboxService;
use App\Models\TaskClaimRequest;

class DriverTaskController extends Controller
{
    protected $pdfService;
    protected $mapboxService;

    public function __construct(PdfService $pdfService, MapboxService $mapboxService)
    {
        $this->pdfService = $pdfService;
        $this->mapboxService = $mapboxService;
    }

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
            Log::alert('status: '.$status);
            // Build query
            $query = Task::with(['customer', 'pickup', 'delivery']);
            // Filter by status if provided
            if ($status) {
                if ($status === 'pending') {
                    // Available tasks: tasks that are pending for this driver
                    $query->where('pending_driver_id', $driver->id)
                          ->whereNull('driver_id')
                          ->where('status', 'in_progress'); // Tasks that are still available
                    Log::alert('Fetching pending tasks for driver: ' . $driver->id);
                } else {
                    $query->where('driver_id', $driver->id);
                    switch ($status) {
                        case 'accepted':
                            $query->where('status', 'accepted');
                            break;
                        case 'in_progress':
                            $query->whereIn('status', ['assign','assigned','accepted', 'started', 'in pickup point', 'loading', 'in the way', 'in delivery point','unloading']);
                            break;
                        case 'completed':
                            $query->whereIn('status', ['completed', 'delivered', 'invoiced']);
                            break;
                        case 'cancelled':
                            $query->whereIn('status', ['cancelled', 'refund']);
                            break;
                        default:
                            break;
                    }
                }
            } else {
                // Default: get all tasks assigned to this driver
                $query->where('driver_id', $driver->id);
            }


            $tasks = $query->orderBy('created_at', 'desc')->paginate($perPage);

            Log::alert($tasks);

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
                'data' => [
                    'tasks' => $tasks->map(function ($task) {
                        return [
                          'id' => $task->id,
                          'total_price' => $task->total_price,
                          'commission'  => $task->commission,
                          'customer_name' => $task->customer->name,
                          'pickup_address' => $task->pickup->address,
                          'delivery_address' => $task->delivery->address,
                          'status' => $task->status,
                          'driver_id' => $task->driver_id,
                          'pending_driver_id' => $task->pending_driver_id,
                          'created_at' => $task->created_at,
                          'pickup_point' => $task->pickup,
                          'delivery_point' => $task->delivery,
                          'driver_cancel' => (bool)$task->driver_cancel,
                          'driver_cancel_reason' => $task->driver_cancel_reason

                        ];
                    }),
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
     * Get driver's completed tasks history
     */
    public function history(Request $request)
    {
        try {
            $driver = $request->user();

            // Validate query parameters
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
            $from = $request->get('from');
            $to = $request->get('to');

            // Build query for completed tasks - include all completed statuses
            $query = Task::with(['customer', 'pickup', 'delivery'])
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['completed', 'delivered', 'invoiced']);

            // Apply date filters if provided
            if ($from && $to) {
                $query->whereBetween('updated_at', [$from, $to]);
            } elseif ($from) {
                $query->whereDate('updated_at', '>=', $from);
            } elseif ($to) {
                $query->whereDate('updated_at', '<=', $to);
            }

            $tasks = $query->orderBy('updated_at', 'desc')->paginate($perPage);

            Log::alert('Task history retrieved', [
                'driver_id' => $driver->id,
                'total_tasks' => $tasks->total(),
                'current_page' => $tasks->currentPage()
            ]);

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
     * Get specific task details
     */
    public function show(Request $request, $taskId)
    {
        try {
            Log::alert('Get task details attempt', [
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);
            $driver = $request->user();

            $task = Task::with([
                'customer',
                'pickup',
                'delivery',
                'points',
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
            Log::alert($task->pickup);
            Log::alert($task->delivery);

            return response()->json([
                'success' => true,
                'message' => 'Task details retrieved successfully',
                'data' => [
                    'task' => [
                    'id' => $task->id,
                    'customer' => [
                        'name' => $task->customer->name ?? 'Unknown',
                        'phone' => $task->customer->phone ?? null,
                        'email' => $task->customer->email ?? null,
                        'policy_file_name' => $task->customer->policy_file_name ?? null
                    ],
                    'pickup_point' => $task->pickup ? [
                        'address' => $task->pickup->address,
                        'latitude' => $task->pickup->latitude,
                        'longitude' => $task->pickup->longitude,
                        'contact_name' => $task->pickup->contact_name,
                        'contact_phone' => $task->pickup->contact_phone
                    ] : null,
                    'delivery_point' => $task->delivery ? [
                        'address' => $task->delivery->address,
                        'latitude' => $task->delivery->latitude,
                        'longitude' => $task->delivery->longitude,
                        'contact_name' => $task->delivery->contact_name,
                        'contact_phone' => $task->delivery->contact_phone
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
                    'special_instructions' => $task->additional_data['special_instructions'] ?? null,
                    'additional_data' => $task->driver_visible_additional_data,
                    'driver_cancel' => (bool)$task->driver_cancel,
                    'driver_cancel_reason' => $task->driver_cancel_reason,
                    'distances' => $this->calculateTaskDistances($task, $driver)
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

            $task = Task::whereNull('driver_id')
                       ->where(function($q) use ($driver) {
                           $q->where('pending_driver_id', $driver->id)
                             ->orWhere(function($q2) use ($driver) {
                                 $q2->where('is_broadcast', true)
                                    ->whereHas('attempts', function($q3) use ($driver) {
                                        $q3->where('driver_id', $driver->id);
                                    });
                             });
                       })
                       ->lockForUpdate()
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

            // Auto-reject all pending claim requests for this task
            TaskClaimRequest::rejectAllPending($taskId, 'تم رفض الطلب تلقائياً - قام سائق آخر بقبول المهمة');

            Log::info('Task accepted by driver', [
                'task_id' => $taskId,
                'driver_id' => $driver->id
            ]);

            // Load task with relationships for complete response
            $task->load(['customer', 'pickup', 'delivery']);

            return response()->json([
                'success' => true,
                'message' => 'Task accepted successfully',
                'task' => [
                    'id' => $task->id,
                    'total_price' => $task->total_price,
                    'commission' => $task->commission,
                    'customer_name' => $task->customer->name ?? null,
                    'pickup_address' => $task->pickup->address ?? null,
                    'delivery_address' => $task->delivery_point->address ?? null,
                    'status' => $task->status,
                    'driver_id' => $task->driver_id,
                    'pending_driver_id' => $task->pending_driver_id,
                    'accepted_at' => $task->accepted_at,
                    'created_at' => $task->created_at,
                    'pickup_point' => $task->pickup,
                    'delivery_point' => $task->delivery,
                    'customer' => $task->customer
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

            Log::alert('Task status update attempt', [
                'driver_id' => $driver->id,
                'task_id'   => $taskId,
                'request'   => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:started,in pickup point,loading,in the way,in delivery point,unloading,completed',
                'notes' => 'nullable|string|max:1000',
                'location' => 'nullable|array',
                'location.latitude' => 'nullable|numeric|between:-90,90',
                'location.longitude' => 'nullable|numeric|between:-180,180'
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed on task status update', [
                    'errors' => $validator->errors(),
                    'task_id' => $taskId,
                    'driver_id' => $driver->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $task = Task::where('driver_id', $driver->id)->find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or not assigned to you'
                ], 404);
            }

            if ($task->driver_cancel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update status while cancellation request is pending.'
                ], 400);
            }

            // ترتيب الحالات
            $statuses = [
                'assign',
                'started',
                'in pickup point',
                'loading',
                'in the way',
                'in delivery point',
                'unloading',
                'completed',
            ];

            $currentIndex   = array_search($task->status, $statuses);
            $requestedIndex = array_search($request->status, $statuses);

            // تحقق من صلاحية الانتقال
            if ($requestedIndex === false || $requestedIndex !== $currentIndex + 1) {
                Log::warning('Invalid status change attempt', [
                      'currentIndex' => $currentIndex,
                      'requestedIndex' => $requestedIndex,
                      'status' => $request->status,
                      'task status' => $task->status
                  ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status change.'
                ], 400);
            }

            if ($task->closed) {
                return response()->json([
                    'success' => false,
                    'message' => 'This task is already closed.'
                ], 400);
            }

            // تحديث البيانات
            $task->status = $request->status;

            if ($request->notes) {
                $task->notes = $request->notes;
            }
            if ($request->status === 'completed') {
                $task->completed_at = now();
            }
            $task->save();

            // إضافة إلى سجل التاريخ
            Task_History::create([
                'task_id' => $task->id,
                'action_type' => $request->status,
                'description' => "Driver changed status to '{$request->status}'",
                'driver_id' => $driver->id,
            ]);

            // تحديث موقع السائق إن وجد
            if ($request->has('location') && $request->location) {
                $driver->update([
                    'longitude' => $request->location['longitude'],
                    'altitude' => $request->location['latitude'],
                ]);
            }

            DB::commit();

            Log::info('Task status updated successfully', [
                'task_id' => $taskId,
                'driver_id' => $driver->id,
                'new_status' => $request->status
            ]);

            // Notify Customer and User
            $notiMessages = [
                'customer' => [
                    'title' => 'تحديث حالة طلبك',
                    'msg'   => "السائق قام بتحديث حالة المهمة رقم #{$task->id} إلى '{$request->status}'."
                ],
                'user' => [
                    'title' => 'تحديث في تنفيذ المهمة',
                    'msg'   => "المهمة رقم #{$task->id} أصبحت الآن في حالة '{$request->status}'."
                ],
            ];

            $recipients = [
                'customer' => [$task->customer_id],
                'user'     => [$task->user_id],
            ];

            foreach ($recipients as $type => $ids) {
                $ids = array_filter($ids);
                if (!empty($ids) && isset($notiMessages[$type])) {
                    app(\App\Services\NotificationService::class)->send(
                        $type,
                        $ids,
                        $notiMessages[$type]['title'],
                        $notiMessages[$type]['msg'],
                        '/images/admin-icon.png',
                        '/images/banner.png',
                        "/tasks/{$task->id}",
                        'task_status_update'
                    );
                }
            }

            // Notify Team Users
            app(\App\Services\NotificationService::class)->notifyTeamUsers(
                $task,
                'تحديث مهمة من السائق',
                "قام السائق {$driver->name} بتحديث المهمة رقم #{$task->id} إلى: " . __($request->status),
                "/tasks/{$task->id}"
            );

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
     * Cancel a task
     */
    public function cancelTask(Request $request, $taskId)
    {
        try {
            $driver = $request->user();
            Log::alert('Driver Cancel Task Attempt', [
                'driver_id' => $driver->id,
                'task_id' => $taskId,
                'reason' => $request->reason
            ]);

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find the task assigned to this driver
            $task = Task::where('driver_id', $driver->id)->find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or not assigned to you'
                ], 404);
            }

            // Check if task can be cancelled (must be active but not completed)
            if ($task->closed || in_array($task->status, ['completed', 'cancelled', 'refund'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This task cannot be cancelled in its current state.'
                ], 400);
            }

            if ($task->driver_cancel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request already submitted.'
                ], 400);
            }

            DB::beginTransaction();

            $task->update([
                'driver_cancel' => true,
                'driver_cancel_reason' => $request->reason
            ]);

            // Add to history
            Task_History::create([
                'task_id' => $task->id,
                'action_type' => 'driver_cancel',
                'description' => 'Driver requested cancellation. Reason: ' . $request->reason,
                'driver_id' => $driver->id,
            ]);

            DB::commit();

            Log::info('Task cancelled by driver successfully', [
                'task_id' => $taskId,
                'driver_id' => $driver->id
            ]);

            // Notify Customer and admin
            $notiMessages = [
                'customer' => [
                    'title' => 'تنبيه: اعتذار السائق',
                    'msg'   => "اعتذر السائق عن تنفيذ المهمة رقم #{$task->id}. جاري إعادة تعيين سائق آخر."
                ],
                'user' => [
                    'title' => 'تنبيه: اعتذار السائق',
                    'msg'   => "قام السائق {$driver->name} بالاعتذار عن المهمة رقم #{$task->id}."
                ],
            ];

            $recipients = [
                'customer' => [$task->customer_id],
                'user'     => [$task->user_id],
            ];

            foreach ($recipients as $type => $ids) {
                $ids = array_filter($ids);
                if (!empty($ids) && isset($notiMessages[$type])) {
                    app(\App\Services\NotificationService::class)->send(
                        $type,
                        $ids,
                        $notiMessages[$type]['title'],
                        $notiMessages[$type]['msg'],
                        '/images/admin-icon.png',
                        '/images/banner.png',
                        "/tasks/{$task->id}",
                        'driver_cancel'
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cancellation request submitted successfully',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Driver cancel task error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel task'
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

            // Auto-reject all pending claim requests for this task
            TaskClaimRequest::rejectAllPending($task->id, 'تم رفض الطلب تلقائياً - قام السائق بقبول المهمة الموزعة آلياً');

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

    /**
     * Get task logs/history
     */
    public function getLogs(Request $request, $taskId)
    {
        try {
            $driver = $request->user();
            Log::alert('get task History');

            // Check if task belongs to driver
            $task = Task::where(function ($q) use ($driver) {
                $q->where('driver_id', $driver->id)
                  ->orWhere('pending_driver_id', $driver->id);
            })->find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Get task history/logs
            $logs = DB::table('task_histories')
                ->where('task_id', $taskId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    $fileName = null;
                    if ($log->file_path) {
                        $fileName = basename($log->file_path);
                    }

                    return [
                        'id' => $log->id,
                        'status' => $log->action_type,
                        'note' => $log->description,
                        'file_name' => $fileName,
                        'file_path' => $log->file_path,
                'type' => $log->driver_id ? 'driver_note' : 'status_change'
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Task logs retrieved successfully',
                'data' => [
                    'logs' => $logs
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get task logs error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get task logs'
            ], 500);
        }
    }

    /**
     * Download waybill
     */
          public function downloadWaybill(Request $request, $taskId)
    {
        try {
            // Allow token in query string for launchUrl compatibility
            if (!$request->user() && $request->has('token')) {
                $token = $request->token;
                if (strpos($token, '|') !== false) {
                    $token = explode('|', $token)[1];
                }

                $accessToken = DB::table('personal_access_tokens')
                    ->where('token', hash('sha256', $token))
                    ->first();

                if ($accessToken && $accessToken->tokenable_type === 'App\Models\Driver') {
                    $driver = Driver::find($accessToken->tokenable_id);
                    if ($driver) {
                        auth()->login($driver);
                    }
                }
            }

            $driver = $request->user();
            if (!$driver) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

            $task = Task::with(['customer', 'pickup', 'delivery', 'driver', 'team'])
                ->where('driver_id', $driver->id)
                ->findOrFail($taskId);

            return $this->pdfService->generate('admin.tasks.report_pdf', ['task' => $task], "Waybill-{$task->id}.pdf", true);
        } catch (\Exception $e) {
            Log::error('Download waybill error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download waybill'
            ], 500);
        }
    }

    /**
     * Download custom customer policy
     */
    public function downloadCustomerPolicy(Request $request, $taskId)
    {
        try {
            // Allow token in query string for launchUrl compatibility
            if (!$request->user() && $request->has('token')) {
                $token = $request->token;
                if (strpos($token, '|') !== false) {
                    $token = explode('|', $token)[1];
                }

                $accessToken = DB::table('personal_access_tokens')
                    ->where('token', hash('sha256', $token))
                    ->first();

                if ($accessToken && $accessToken->tokenable_type === 'App\Models\Driver') {
                    $driver = Driver::find($accessToken->tokenable_id);
                    if ($driver) {
                        auth()->login($driver);
                    }
                }
            }

            $driver = $request->user();
            if (!$driver) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

            $task = Task::with(['customer', 'pickup', 'delivery', 'driver', 'team'])
                ->where('driver_id', $driver->id)
                ->findOrFail($taskId);

            return $this->pdfService->generate('admin.tasks.policy_pdf_custom', ['task' => $task], "Policy-{$task->id}.pdf", true);
        } catch (\Exception $e) {
            Log::error('Download customer policy error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download customer policy'
            ], 500);
        }
    }

    /**
     * Add note to task
     */
    public function addNote(Request $request, $taskId)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'note' => 'required|string|max:1000',
                'type' => 'nullable|string|in:driver_note,status_change',
                'file' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if task belongs to driver
            $task = Task::where('driver_id', $driver->id)->find($taskId);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or not assigned to you'
                ], 404);
            }

            DB::beginTransaction();

            $filePath = null;

            // Handle file upload if present
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('task_files/' . $taskId, $fileName, 'public');
            }

            // Add to task history
            DB::table('task_histories')->insert([
                'task_id' => $task->id,
                'action_type' => $request->get('type', 'driver_note'),
                'description' => $request->note,
                'driver_id' => $driver->id,
                'file_path' => $filePath,
                'file_type' => $request->hasFile('file') ? $request->file('file')->getClientMimeType() : null,
                'ip' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Note added to task', [
                'task_id' => $taskId,
                'driver_id' => $driver->id,
                'has_file' => $filePath !== null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Note added successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Add task note error', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add note'
            ], 500);
        }
    }

    /**
     * Calculate task related road distances
     */
    private function calculateTaskDistances($task, $driver)
    {
        $distances = [
            'pickup_to_delivery' => null,
            'driver_to_pickup' => null,
            'driver_to_delivery' => null,
        ];

        try {
            // 1. Pickup to Delivery
            if ($task->pickup && $task->delivery) {
                $route = $this->mapboxService->calculateRoute(
                    [$task->pickup->latitude, $task->pickup->longitude],
                    [$task->delivery->latitude, $task->delivery->longitude]
                );
                $distances['pickup_to_delivery'] = $route['distance_km'] ?? null;
            }

            // Driver location
            $driverLat = $driver->altitude; // Using altitude as latitude as per project convention
            $driverLng = $driver->longitude;

            if ($driverLat && $driverLng) {
                // 2. Driver to Pickup
                if ($task->pickup) {
                    $route = $this->mapboxService->calculateRoute(
                        [$driverLat, $driverLng],
                        [$task->pickup->latitude, $task->pickup->longitude]
                    );
                    $distances['driver_to_pickup'] = $route['distance_km'] ?? null;
                }

                // 3. Driver to Delivery
                if ($task->delivery) {
                    $route = $this->mapboxService->calculateRoute(
                        [$driverLat, $driverLng],
                        [$task->delivery->latitude, $task->delivery->longitude]
                    );
                    $distances['driver_to_delivery'] = $route['distance_km'] ?? null;
                }
            }
        } catch (\Exception $e) {
            Log::error('Distance calculation error: ' . $e->getMessage());
        }

        return $distances;
    }
}
