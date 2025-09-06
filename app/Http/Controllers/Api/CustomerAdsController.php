<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Task;
use App\Models\Task_Offer;
use App\Models\Customs_Clearance;
use App\Models\Customs_Clearance_Offer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CustomerAdsController extends Controller
{
    /**
     * Get available task ads for drivers
     */
    public function getTaskAds(Request $request)
    {
        try {
            $query = Task::where('status', 'advertised');

            // Apply filters
            if ($request->filled('task_type')) {
                $query->where('task_type', $request->task_type);
            }

            if ($request->filled('vehicle_type')) {
                $query->where('vehicle_type', $request->vehicle_type);
            }

            if ($request->filled('from_city')) {
                $query->where('from_city', $request->from_city);
            }

            if ($request->filled('to_city')) {
                $query->where('to_city', $request->to_city);
            }

            if ($request->filled('price_min')) {
                $query->where('price', '>=', $request->price_min);
            }

            if ($request->filled('price_max')) {
                $query->where('price', '<=', $request->price_max);
            }

            if ($request->filled('pickup_date_from')) {
                $query->whereDate('pickup_time', '>=', $request->pickup_date_from);
            }

            if ($request->filled('pickup_date_to')) {
                $query->whereDate('pickup_time', '<=', $request->pickup_date_to);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('from_address', 'like', "%{$search}%")
                      ->orWhere('to_address', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $tasks = $query->with(['customer'])->paginate($perPage);

            $tasksData = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'task_type' => $task->task_type,
                    'vehicle_type' => $task->vehicle_type,
                    'from_address' => $task->from_address,
                    'to_address' => $task->to_address,
                    'from_city' => $task->from_city,
                    'to_city' => $task->to_city,
                    'pickup_time' => $task->pickup_time,
                    'delivery_time' => $task->delivery_time,
                    'price' => $task->price,
                    'currency' => 'SAR',
                    'description' => $task->description,
                    'distance' => $task->distance,
                    'estimated_duration' => $task->estimated_duration,
                    'customer' => [
                        'name' => $task->customer->name,
                        'company_name' => $task->customer->company_name,
                        'rating' => $task->customer->rating ?? 0,
                        'phone' => $task->customer->phone,
                    ],
                    'offers_count' => Task_Offer::where('task_id', $task->id)->count(),
                    'created_at' => $task->created_at,
                    'expires_at' => $task->created_at->addHours(24), // Ads expire after 24 hours
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'ads' => $tasksData,
                    'pagination' => [
                        'current_page' => $tasks->currentPage(),
                        'last_page' => $tasks->lastPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                        'from' => $tasks->firstItem(),
                        'to' => $tasks->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get task ads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task ad details
     */
    public function getTaskAdDetails(Request $request, $id)
    {
        try {
            $task = Task::where('id', $id)
                       ->where('status', 'advertised')
                       ->with(['customer'])
                       ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task ad not found'
                ], 404);
            }

            $taskData = [
                'id' => $task->id,
                'task_type' => $task->task_type,
                'vehicle_type' => $task->vehicle_type,
                'from_address' => $task->from_address,
                'to_address' => $task->to_address,
                'from_city' => $task->from_city,
                'to_city' => $task->to_city,
                'from_lat' => $task->from_lat,
                'from_lng' => $task->from_lng,
                'to_lat' => $task->to_lat,
                'to_lng' => $task->to_lng,
                'pickup_time' => $task->pickup_time,
                'delivery_time' => $task->delivery_time,
                'price' => $task->price,
                'currency' => 'SAR',
                'description' => $task->description,
                'distance' => $task->distance,
                'estimated_duration' => $task->estimated_duration,
                'special_requirements' => $task->special_requirements,
                'customer' => [
                    'id' => $task->customer->id,
                    'name' => $task->customer->name,
                    'company_name' => $task->customer->company_name,
                    'rating' => $task->customer->rating ?? 0,
                    'phone' => $task->customer->phone,
                    'total_tasks' => Task::where('customer_id', $task->customer->id)->count(),
                ],
                'offers_count' => Task_Offer::where('task_id', $task->id)->count(),
                'created_at' => $task->created_at,
                'expires_at' => $task->created_at->addHours(24),
            ];

            return response()->json([
                'success' => true,
                'data' => $taskData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get task ad details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit offer for task
     */
    public function submitTaskOffer(Request $request, $id)
    {
        try {
            $customer = $request->user();

            // Check if customer is a driver
            if (!$customer->is_driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only drivers can submit offers'
                ], 403);
            }

            $task = Task::where('id', $id)
                       ->where('status', 'advertised')
                       ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or not available for offers'
                ], 404);
            }

            // Check if driver already submitted an offer
            $existingOffer = Task_Offer::where('task_id', $task->id)
                                      ->where('driver_id', $customer->id)
                                      ->first();

            if ($existingOffer) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already submitted an offer for this task'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'offered_price' => 'required|numeric|min:1',
                'message' => 'nullable|string|max:500',
                'estimated_pickup_time' => 'nullable|date|after:now',
                'estimated_delivery_time' => 'nullable|date|after:estimated_pickup_time',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Create offer
            $offer = Task_Offer::create([
                'task_id' => $task->id,
                'driver_id' => $customer->id,
                'offered_price' => $request->offered_price,
                'message' => $request->message,
                'estimated_pickup_time' => $request->estimated_pickup_time,
                'estimated_delivery_time' => $request->estimated_delivery_time,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offer submitted successfully',
                'data' => [
                    'offer_id' => $offer->id,
                    'offered_price' => $offer->offered_price,
                    'status' => $offer->status,
                    'created_at' => $offer->created_at,
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit offer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get my submitted offers
     */
    public function getMyOffers(Request $request)
    {
        try {
            $customer = $request->user();

            $query = Task_Offer::where('driver_id', $customer->id);

            // Apply filters
            if ($request->filled('status')) {
                $statuses = is_array($request->status) ? $request->status : [$request->status];
                $query->whereIn('status', $statuses);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $offers = $query->with(['task', 'task.customer'])->paginate($perPage);

            $offersData = $offers->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'offered_price' => $offer->offered_price,
                    'message' => $offer->message,
                    'status' => $offer->status,
                    'estimated_pickup_time' => $offer->estimated_pickup_time,
                    'estimated_delivery_time' => $offer->estimated_delivery_time,
                    'task' => [
                        'id' => $offer->task->id,
                        'task_type' => $offer->task->task_type,
                        'from_address' => $offer->task->from_address,
                        'to_address' => $offer->task->to_address,
                        'pickup_time' => $offer->task->pickup_time,
                        'original_price' => $offer->task->price,
                        'customer' => [
                            'name' => $offer->task->customer->name,
                            'company_name' => $offer->task->customer->company_name,
                            'rating' => $offer->task->customer->rating ?? 0,
                        ],
                    ],
                    'created_at' => $offer->created_at,
                    'updated_at' => $offer->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'offers' => $offersData,
                    'pagination' => [
                        'current_page' => $offers->currentPage(),
                        'last_page' => $offers->lastPage(),
                        'per_page' => $offers->perPage(),
                        'total' => $offers->total(),
                        'from' => $offers->firstItem(),
                        'to' => $offers->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get offers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get offers received for my tasks
     */
    public function getReceivedOffers(Request $request)
    {
        try {
            $customer = $request->user();

            $query = Task_Offer::whereHas('task', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            });

            // Apply filters
            if ($request->filled('task_id')) {
                $query->where('task_id', $request->task_id);
            }

            if ($request->filled('status')) {
                $statuses = is_array($request->status) ? $request->status : [$request->status];
                $query->whereIn('status', $statuses);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $offers = $query->with(['task', 'driver'])->paginate($perPage);

            $offersData = $offers->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'offered_price' => $offer->offered_price,
                    'message' => $offer->message,
                    'status' => $offer->status,
                    'estimated_pickup_time' => $offer->estimated_pickup_time,
                    'estimated_delivery_time' => $offer->estimated_delivery_time,
                    'task' => [
                        'id' => $offer->task->id,
                        'task_type' => $offer->task->task_type,
                        'from_address' => $offer->task->from_address,
                        'to_address' => $offer->task->to_address,
                        'original_price' => $offer->task->price,
                    ],
                    'driver' => [
                        'id' => $offer->driver->id,
                        'name' => $offer->driver->name,
                        'phone' => $offer->driver->phone,
                        'rating' => $offer->driver->rating ?? 0,
                        'vehicle_type' => $offer->driver->vehicle_type,
                        'vehicle_plate' => $offer->driver->vehicle_plate,
                        'total_tasks' => Task::where('driver_id', $offer->driver->id)
                                            ->where('status', 'completed')
                                            ->count(),
                    ],
                    'created_at' => $offer->created_at,
                    'updated_at' => $offer->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'offers' => $offersData,
                    'pagination' => [
                        'current_page' => $offers->currentPage(),
                        'last_page' => $offers->lastPage(),
                        'per_page' => $offers->perPage(),
                        'total' => $offers->total(),
                        'from' => $offers->firstItem(),
                        'to' => $offers->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get received offers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept an offer
     */
    public function acceptOffer(Request $request, $offerId)
    {
        try {
            $customer = $request->user();

            $offer = Task_Offer::with(['task', 'driver'])
                              ->whereHas('task', function ($q) use ($customer) {
                                  $q->where('customer_id', $customer->id);
                              })
                              ->where('id', $offerId)
                              ->first();

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer not found'
                ], 404);
            }

            if ($offer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer is no longer available'
                ], 422);
            }

            if ($offer->task->status !== 'advertised') {
                return response()->json([
                    'success' => false,
                    'message' => 'Task is no longer available'
                ], 422);
            }

            DB::beginTransaction();

            // Accept the offer
            $offer->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            // Update task
            $offer->task->update([
                'status' => 'assign',
                'driver_id' => $offer->driver_id,
                'price' => $offer->offered_price,
                'assigned_at' => now(),
            ]);

            // Reject all other offers for this task
            Task_Offer::where('task_id', $offer->task_id)
                      ->where('id', '!=', $offer->id)
                      ->where('status', 'pending')
                      ->update([
                          'status' => 'rejected',
                          'rejected_at' => now(),
                      ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offer accepted successfully',
                'data' => [
                    'task_id' => $offer->task->id,
                    'driver' => [
                        'id' => $offer->driver->id,
                        'name' => $offer->driver->name,
                        'phone' => $offer->driver->phone,
                        'rating' => $offer->driver->rating ?? 0,
                    ],
                    'final_price' => $offer->offered_price,
                ]
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept offer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject an offer
     */
    public function rejectOffer(Request $request, $offerId)
    {
        try {
            $customer = $request->user();

            $offer = Task_Offer::whereHas('task', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
                            ->where('id', $offerId)
                            ->first();

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer not found'
                ], 404);
            }

            if ($offer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer cannot be rejected in current status'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $offer->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
                'rejected_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Offer rejected successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject offer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel my submitted offer
     */
    public function cancelMyOffer(Request $request, $offerId)
    {
        try {
            $customer = $request->user();

            $offer = Task_Offer::where('id', $offerId)
                              ->where('driver_id', $customer->id)
                              ->first();

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer not found'
                ], 404);
            }

            if ($offer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer cannot be canceled in current status'
                ], 422);
            }

            $offer->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Offer canceled successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel offer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customs clearance ads
     */
    public function getCustomsClearanceAds(Request $request)
    {
        try {
            $query = Customs_Clearance::where('status', 'advertised');

            // Apply filters
            if ($request->filled('clearance_type')) {
                $query->where('clearance_type', $request->clearance_type);
            }

            if ($request->filled('origin_country')) {
                $query->where('origin_country', $request->origin_country);
            }

            if ($request->filled('destination_port')) {
                $query->where('destination_port', $request->destination_port);
            }

            if ($request->filled('value_min')) {
                $query->where('goods_value', '>=', $request->value_min);
            }

            if ($request->filled('value_max')) {
                $query->where('goods_value', '<=', $request->value_max);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $clearances = $query->with(['customer'])->paginate($perPage);

            $clearancesData = $clearances->map(function ($clearance) {
                return [
                    'id' => $clearance->id,
                    'clearance_type' => $clearance->clearance_type,
                    'goods_description' => $clearance->goods_description,
                    'goods_value' => $clearance->goods_value,
                    'goods_weight' => $clearance->goods_weight,
                    'origin_country' => $clearance->origin_country,
                    'destination_port' => $clearance->destination_port,
                    'expected_arrival' => $clearance->expected_arrival,
                    'customer' => [
                        'name' => $clearance->customer->name,
                        'company_name' => $clearance->customer->company_name,
                        'rating' => $clearance->customer->rating ?? 0,
                    ],
                    'offers_count' => Customs_Clearance_Offer::where('clearance_id', $clearance->id)->count(),
                    'created_at' => $clearance->created_at,
                    'expires_at' => $clearance->created_at->addHours(48), // Clearance ads expire after 48 hours
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'ads' => $clearancesData,
                    'pagination' => [
                        'current_page' => $clearances->currentPage(),
                        'last_page' => $clearances->lastPage(),
                        'per_page' => $clearances->perPage(),
                        'total' => $clearances->total(),
                        'from' => $clearances->firstItem(),
                        'to' => $clearances->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get customs clearance ads',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
