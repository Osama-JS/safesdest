<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskClaimRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DriverAvailableTasksController extends Controller
{
    /**
     * Get available tasks for drivers (unassigned, in_progress)
     * Sorted by distance from driver's location to pickup point
     */
    public function index(Request $request)
    {
        try {
            $driver = $request->user();
            $perPage = $request->input('per_page', 15);

            // Driver coordinates (altitude stores latitude in this DB)
            $driverLat = $driver->altitude;
            $driverLng = $driver->longitude;

            $query = Task::query()
                ->with(['customer', 'pickup', 'delivery', 'vehicle_size'])
                ->where('status', 'in_progress')
                ->whereNull('driver_id');
            
            if (!$driver->is_guest) {
                $query->where('vehicle_size_id', $driver->vehicle_size_id);
            }
                // ->where('is_broadcast', true);

            // Important: We need to select all tasks columns first
            $query->select('tasks.*');

            // If driver has location, sort by distance to pickup point
            if ($driverLat && $driverLng) {
                $query->join('tasks_points', function ($join) {
                        $join->on('tasks.id', '=', 'tasks_points.task_id')
                            ->where('tasks_points.type', '=', 'pickup');
                    });

                // Haversine formula
                $distanceSql = "(6371 * acos(cos(radians(?)) * cos(radians(tasks_points.latitude)) * cos(radians(tasks_points.longitude) - radians(?)) + sin(radians(?)) * sin(radians(tasks_points.latitude))))";

                $query->selectRaw("{$distanceSql} AS distance", [$driverLat, $driverLng, $driverLat])
                    ->orderBy('distance', 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $tasks = $query->paginate($perPage);

            Log::info("Driver available tasks - Page: {$tasks->currentPage()}, Total: {$tasks->total()}, Count: " . count($tasks->items()));
            Log::debug('Driver available tasks data: ' . json_encode($tasks->items()));

            // Check if driver has already claimed any of these tasks
            $taskIds = collect($tasks->items())->pluck('id')->toArray();
            $myClaims = TaskClaimRequest::where('driver_id', $driver->id)
                ->whereIn('task_id', $taskIds)
                ->pluck('status', 'task_id')
                ->toArray();

            $Avtasks = $tasks->map(function ($task) use ($myClaims) {
                        return [
                            'id' => $task->id,
                            'customer_task_number' => $task->customer_task_number,
                            'total_price' => round($task->total_price - ($task->commission ?? 0), 2),
                            'customer_name' => $task->customer->name ?? 'Unknown',
                            'pickup_address' => $task->pickup->address ?? 'N/A',
                            'pickup_date' => $task->pickup->scheduled_time ?? null,
                            'delivery_address' => $task->delivery->address ?? 'N/A',
                            'delivery_date' => $task->delivery->scheduled_time ?? null,
                            'distance' => isset($task->distance) ? round($task->distance, 2) : null,
                            'status' => $task->status,
                            'created_at' => $task->created_at,
                            'claim_status' => $myClaims[$task->id] ?? null,
                            'vehicle_size' => $task->vehicle_size->type->vehicle->name . ' ' . $task->vehicle_size->type->name . ' ' . $task->vehicle_size->name  ?? 'N/A',
                            'additional_data' => $task->driver_visible_additional_data,
                            'conditions' => $task->conditions,
                        ];
                    });
                Log::debug('Driver available tasks data: ' . json_encode($Avtasks));
            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $Avtasks,
                    'pagination' => [
                        'total' => $tasks->total(),
                        'per_page' => $tasks->perPage(),
                        'current_page' => $tasks->currentPage(),
                        'last_page' => $tasks->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get available tasks error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available tasks'
            ], 500);
        }
    }

    /**
     * Get details of an available task
     */
    public function show(Request $request, $id)
    {
        try {
            $driver = $request->user();
            $query = Task::with(['customer', 'pickup', 'delivery', 'vehicle_size', 'formTemplate'])
                ->where('status', 'in_progress')
                ->whereNull('driver_id');
            
            if (!$driver->is_guest) {
                $query->where('vehicle_size_id', $driver->vehicle_size_id);
            }
                
            $task = $query->findOrFail($id);

            $claim = TaskClaimRequest::where('driver_id', $driver->id)
                ->where('task_id', $task->id)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'task' => [
                        'id' => $task->id,
                        'customer_task_number' => $task->customer_task_number,
                        'total_price' => round($task->total_price - ($task->commission ?? 0), 2),
                        'customer' => [
                            'name' => $task->customer->name ?? 'Unknown',
                            'phone' => $task->customer->phone ?? null,
                        ],
                        'pickup_point' => [
                            'address' => $task->pickup->address ?? 'N/A',
                            'scheduled_time' => $task->pickup->scheduled_time ?? null,
                            'latitude' => $task->pickup->latitude ?? null,
                            'longitude' => $task->pickup->longitude ?? null,
                        ],
                        'delivery_point' => [
                            'address' => $task->delivery->address ?? 'N/A',
                            'scheduled_time' => $task->delivery->scheduled_time ?? null,
                            'latitude' => $task->delivery->latitude ?? null,
                            'longitude' => $task->delivery->longitude ?? null,
                        ],
                        'vehicle_size' => $task->vehicle_size->name ?? 'N/A',
                        'additional_data' => $task->driver_visible_additional_data,
                        'conditions' => $task->conditions,
                        'claim_status' => $claim->status ?? null,
                        'claim_note' => $claim->driver_note ?? null,
                        'admin_note' => $claim->admin_note ?? null,
                        'created_at' => $task->created_at,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found or unavailable'
            ], 404);
        }
    }

    /**
     * Claim a task
     */
    public function claim(Request $request, $id)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'note' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($driver->is_guest) {
                return response()->json([
                    'success' => false,
                    'is_guest' => true,
                    'message' => 'Please complete your profile first.'
                ], 403);
            }

            $task = Task::where('status', 'in_progress')
                ->whereNull('driver_id')
                ->where('vehicle_size_id', $driver->vehicle_size_id)
                ->findOrFail($id);

            // Check if already claimed
            $existing = TaskClaimRequest::where('driver_id', $driver->id)
                ->where('task_id', $task->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already requested this task'
                ], 400);
            }

            $claim = TaskClaimRequest::create([
                'task_id' => $task->id,
                'driver_id' => $driver->id,
                'status' => 'pending',
                'driver_note' => $request->note
            ]);

            // Notify Admin/Team (conceptual)
            // app(NotificationService::class)->notifyTeamUsers($task, 'طلب منح جديد', "قام السائق {$driver->name} بطلب العمل على المهمة #{$task->id}");

            return response()->json([
                'success' => true,
                'message' => 'Your request has been submitted successfully',
                'claim' => $claim
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get driver's claim history
     */
    public function myClaims(Request $request)
    {
        try {
            $driver = $request->user();
            $perPage = $request->input('per_page', 15);

            $claims = TaskClaimRequest::with(['task.customer', 'task.pickup', 'task.delivery'])
                ->where('driver_id', $driver->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'claims' => $claims->map(function ($claim) {
                        return [
                            'id' => $claim->id,
                            'task_id' => $claim->task_id,
                            'total_price' => round($claim->task->total_price - ($claim->task->commission ?? 0), 2),
                            'customer_task_number' => $claim->task->customer_task_number ?? 'N/A',
                            'customer_name' => $claim->task->customer->name ?? 'Unknown',
                            'status' => $claim->status,
                            'driver_note' => $claim->driver_note,
                            'admin_note' => $claim->admin_note,
                            'created_at' => $claim->created_at,
                            'pickup_address' => $claim->task->pickup->address ?? 'N/A',
                            'pickup_date' => $claim->task->pickup->scheduled_time ?? null,
                            'delivery_address' => $claim->task->delivery->address ?? 'N/A',
                            'delivery_date' => $claim->task->delivery->scheduled_time ?? null,
                        ];
                    }),
                    'pagination' => [
                        'total' => $claims->total(),
                        'current_page' => $claims->currentPage(),
                        'last_page' => $claims->lastPage(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch claims history'
            ], 500);
        }
    }

    /**
     * Update a claim request
     */
    public function updateClaim(Request $request, $id)
    {
        try {
            $driver = $request->user();
            $claim = TaskClaimRequest::where('driver_id', $driver->id)
                ->where('status', 'pending')
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'note' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $claim->update([
                'driver_note' => $request->note
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Claim request updated successfully',
                'claim' => $claim
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a claim request
     */
    public function cancelClaim(Request $request, $id)
    {
        try {
            $driver = $request->user();
            $claim = TaskClaimRequest::where('driver_id', $driver->id)
                ->where('status', 'pending')
                ->findOrFail($id);

            $claim->delete();

            return response()->json([
                'success' => true,
                'message' => 'Claim request cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel claim: ' . $e->getMessage()
            ], 500);
        }
    }
}
