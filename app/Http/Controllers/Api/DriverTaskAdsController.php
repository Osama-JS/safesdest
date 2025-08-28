<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class DriverTaskAdsController extends Controller
{
    /**
     * Get available task ads for driver
     */
    public function index(Request $request)
    {
        try {
            $driver = Auth::user();
            $size_id = $driver->vehicle_size_id;
            $driver_id = $driver->id;

            // Validation
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'status' => 'nullable|string|in:running,closed,all'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $statusFilter = $request->get('status', 'running');

            // Build query
            $query = Task_Ad::with(['task.customer', 'task.user', 'task.pickup', 'task.delivery'])
                ->whereHas('task', function ($q) use ($size_id) {
                    $q->where('vehicle_size_id', $size_id);
                });

            // Apply status filter
            if ($statusFilter === 'running') {
                $query->where('status', 'running');
            } elseif ($statusFilter === 'closed') {
                $query->where('status', 'closed')
                      ->whereHas('offers', function ($offerQ) use ($driver_id) {
                          $offerQ->where('driver_id', $driver_id);
                      });
            } elseif ($statusFilter === 'all') {
                $query->where(function ($q) use ($driver_id) {
                    $q->where('status', 'running')
                      ->orWhere(function ($subQ) use ($driver_id) {
                          $subQ->where('status', 'closed')
                               ->whereHas('offers', function ($offerQ) use ($driver_id) {
                                   $offerQ->where('driver_id', $driver_id);
                               });
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            // Paginate
            $ads = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform data
            $transformedAds = $ads->getCollection()->map(function ($ad) use ($driver_id) {
                return $this->transformTaskAd($ad, $driver_id);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $transformedAds,
                    'pagination' => [
                        'current_page' => $ads->currentPage(),
                        'last_page' => $ads->lastPage(),
                        'per_page' => $ads->perPage(),
                        'total' => $ads->total(),
                        'from' => $ads->firstItem(),
                        'to' => $ads->lastItem(),
                        'has_more_pages' => $ads->hasMorePages()
                    ]
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Driver Task Ads Index Error', [
                'error' => $e->getMessage(),
                'driver_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch task ads'
            ], 500);
        }
    }

    /**
     * Get specific task ad details
     */
    public function show($id)
    {
        try {
            $driver = Auth::user();
            $driver_id = $driver->id;

            $ad = Task_Ad::with(['task.customer', 'task.user', 'task.pickup', 'task.delivery'])
                         ->findOrFail($id);

            // Check if driver can view this ad
            $acceptedOffer = Task_Offire::where('task_ad_id', $id)
                                       ->where('accepted', true)
                                       ->first();

            if (!$this->canViewDetails($ad, $driver_id, $acceptedOffer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this advertisement'
                ], 403);
            }

            $transformedAd = $this->transformTaskAdDetails($ad, $driver_id);

            return response()->json([
                'success' => true,
                'data' => $transformedAd
            ], 200);

        } catch (Exception $e) {
            Log::error('Driver Task Ad Show Error', [
                'error' => $e->getMessage(),
                'ad_id' => $id,
                'driver_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch task ad details'
            ], 500);
        }
    }

    /**
     * Submit offer on task ad
     */
    public function submitOffer(Request $request, $adId)
    {
        try {
            $driver = Auth::user();
            $driver_id = $driver->id;

            // Validation
            $validator = Validator::make($request->all(), [
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:400'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if ad exists and is valid
            $ad = Task_Ad::findOrFail($adId);

            if ($ad->status !== 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'This advertisement is no longer accepting offers'
                ], 400);
            }

            // Check if there's already an accepted offer
            $acceptedOffer = Task_Offire::where('task_ad_id', $adId)
                                       ->where('accepted', true)
                                       ->first();

            if ($acceptedOffer) {
                return response()->json([
                    'success' => false,
                    'message' => 'This advertisement already has an accepted offer'
                ], 400);
            }

            // Check if driver already has an offer
            $existingOffer = Task_Offire::where('task_ad_id', $adId)
                                       ->where('driver_id', $driver_id)
                                       ->first();

            if ($existingOffer) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already submitted an offer for this advertisement'
                ], 400);
            }

            // Create new offer
            $offer = Task_Offire::create([
                'task_ad_id' => $adId,
                'driver_id' => $driver_id,
                'price' => $request->price,
                'description' => $request->description,
                'accepted' => false
            ]);

            Log::info('Driver submitted offer', [
                'driver_id' => $driver_id,
                'ad_id' => $adId,
                'offer_id' => $offer->id,
                'price' => $request->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Offer submitted successfully',
                'data' => [
                    'offer_id' => $offer->id,
                    'price' => $offer->price,
                    'description' => $offer->description,
                    'created_at' => $offer->created_at
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error('Driver Submit Offer Error', [
                'error' => $e->getMessage(),
                'ad_id' => $adId,
                'driver_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit offer'
            ], 500);
        }
    }

    /**
     * Update existing offer
     */
    public function updateOffer(Request $request, $offerId)
    {
        try {
            $driver = Auth::user();
            $driver_id = $driver->id;

            // Validation
            $validator = Validator::make($request->all(), [
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:400'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find offer
            $offer = Task_Offire::where('id', $offerId)
                               ->where('driver_id', $driver_id)
                               ->firstOrFail();

            // Check if offer is already accepted
            if ($offer->accepted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update an accepted offer'
                ], 400);
            }

            // Check if ad is still running
            $ad = $offer->ad;
            if ($ad->status !== 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update offer on closed advertisement'
                ], 400);
            }

            // Update offer
            $offer->update([
                'price' => $request->price,
                'description' => $request->description
            ]);

            Log::info('Driver updated offer', [
                'driver_id' => $driver_id,
                'offer_id' => $offerId,
                'new_price' => $request->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Offer updated successfully',
                'data' => [
                    'offer_id' => $offer->id,
                    'price' => $offer->price,
                    'description' => $offer->description,
                    'updated_at' => $offer->updated_at
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Driver Update Offer Error', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
                'driver_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update offer'
            ], 500);
        }
    }

    /**
     * Accept task after offer is approved
     */
    public function acceptTask(Request $request, $offerId)
    {
        try {
            $driver = Auth::user();
            $driver_id = $driver->id;

            // Find the accepted offer
            $offer = Task_Offire::where('id', $offerId)
                               ->where('driver_id', $driver_id)
                               ->where('accepted', true)
                               ->firstOrFail();

            $ad = $offer->ad;
            $task = $ad->task;

            // Check if task is still available
            if ($task->driver_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task has already been assigned to another driver'
                ], 400);
            }

            // Assign task to driver
            $task->update([
                'driver_id' => $driver_id,
                'status' => 'assigned',
                'total_price' => $offer->price
            ]);

            // Close the advertisement
            $ad->update([
                'status' => 'closed',
                'closed_at' => now()
            ]);

            // Add task history
            $task->history()->create([
                'action_type' => 'assigned',
                'description' => "Task assigned to driver through advertisement offer (Price: {$offer->price})",
                'ip' => $request->ip()
            ]);

            Log::info('Driver accepted task from offer', [
                'driver_id' => $driver_id,
                'task_id' => $task->id,
                'offer_id' => $offerId,
                'price' => $offer->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task accepted successfully',
                'data' => [
                    'task_id' => $task->id,
                    'price' => $offer->price,
                    'status' => $task->status
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Driver Accept Task Error', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
                'driver_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept task'
            ], 500);
        }
    }

    /**
     * Get driver's submitted offers
     */
    public function myOffers(Request $request)
    {
        try {
            $driver = Auth::user();
            $driver_id = $driver->id;

            // Validation
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'status' => 'nullable|string|in:pending,accepted,rejected'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $statusFilter = $request->get('status');

            // Build query
            $query = Task_Offire::with(['ad.task.pickup', 'ad.task.delivery'])
                               ->where('driver_id', $driver_id);

            // Apply status filter
            if ($statusFilter === 'accepted') {
                $query->where('accepted', true);
            } elseif ($statusFilter === 'rejected') {
                $query->where('accepted', false)
                      ->whereHas('ad', function ($q) {
                          $q->where('status', 'closed');
                      });
            } elseif ($statusFilter === 'pending') {
                $query->where('accepted', false)
                      ->whereHas('ad', function ($q) {
                          $q->where('status', 'running');
                      });
            }

            $query->orderBy('created_at', 'DESC');

            // Paginate
            $offers = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform data
            $transformedOffers = $offers->getCollection()->map(function ($offer) {
                return $this->transformOffer($offer);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $transformedOffers,
                    'pagination' => [
                        'current_page' => $offers->currentPage(),
                        'last_page' => $offers->lastPage(),
                        'per_page' => $offers->perPage(),
                        'total' => $offers->total(),
                        'from' => $offers->firstItem(),
                        'to' => $offers->lastItem(),
                        'has_more_pages' => $offers->hasMorePages()
                    ]
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Driver My Offers Error', [
                'error' => $e->getMessage(),
                'driver_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch your offers'
            ], 500);
        }
    }

    /**
     * Transform task ad for list view
     */
    private function transformTaskAd($ad, $driver_id)
    {
        // Get driver's offer for this ad
        $driverOffer = Task_Offire::where('task_ad_id', $ad->id)
                                 ->where('driver_id', $driver_id)
                                 ->first();

        // Check if there's an accepted offer
        $acceptedOffer = Task_Offire::where('task_ad_id', $ad->id)
                                   ->where('accepted', true)
                                   ->first();

        return [
            'id' => $ad->id,
            'task_id' => $ad->task_id,
            'description' => $ad->description,
            'status' => $ad->status,
            'lowest_price' => (float) $ad->lowest_price,
            'highest_price' => (float) $ad->highest_price,
            'included' => (bool) $ad->included,
            'created_at' => $ad->created_at,
            'task' => [
                'pickup' => [
                    'address' => $ad->task->pickup->address ?? '',
                    'latitude' => $ad->task->pickup->latitude ?? null,
                    'longitude' => $ad->task->pickup->longitude ?? null,
                    'contact_name' => $ad->task->pickup->contact_name ?? '',
                    'contact_phone' => $ad->task->pickup->contact_phone ?? ''
                ],
                'delivery' => [
                    'address' => $ad->task->delivery->address ?? '',
                    'latitude' => $ad->task->delivery->latitude ?? null,
                    'longitude' => $ad->task->delivery->longitude ?? null,
                    'contact_name' => $ad->task->delivery->contact_name ?? '',
                    'contact_phone' => $ad->task->delivery->contact_phone ?? ''
                ],
                'vehicle_size' => $ad->task->vehicle_size->name ?? ''
            ],
            'my_offer' => $driverOffer ? [
                'id' => $driverOffer->id,
                'price' => (float) $driverOffer->price,
                'description' => $driverOffer->description,
                'accepted' => (bool) $driverOffer->accepted,
                'created_at' => $driverOffer->created_at
            ] : null,
            'offers_count' => $ad->offers()->count(),
            'has_accepted_offer' => $acceptedOffer ? true : false,
            'accepted_driver_id' => $acceptedOffer ? $acceptedOffer->driver_id : null,
            'can_submit_offer' => $this->canSubmitOffer($ad, $driverOffer, $acceptedOffer),
            'can_view_details' => $this->canViewDetails($ad, $driver_id, $acceptedOffer)
        ];
    }

    /**
     * Transform task ad for detail view
     */
    private function transformTaskAdDetails($ad, $driver_id)
    {
        $baseData = $this->transformTaskAd($ad, $driver_id);

        // Add additional details
        $baseData['task']['customer'] = [
            'owner' => $ad->task->owner,
            'name' => $ad->task->owner === 'customer'
                ? optional($ad->task->customer)->name
                : optional($ad->task->user)->name,
            'phone' => $ad->task->owner === 'customer'
                ? optional($ad->task->customer)->phone
                : optional($ad->task->user)->phone
        ];

        $baseData['commission'] = [
            'service_commission' => (float) $ad->service_commission,
            'service_commission_type' => $ad->service_commission_type ? 'fixed' : 'percentage',
            'vat_commission' => (float) $ad->vat_commission
        ];

        return $baseData;
    }

    /**
     * Transform offer for my offers list
     */
    private function transformOffer($offer)
    {
        $ad = $offer->ad;
        $task = $ad->task;

        // Determine offer status
        $status = 'pending';
        if ($offer->accepted) {
            $status = 'accepted';
        } elseif ($ad->status === 'closed' && !$offer->accepted) {
            $status = 'rejected';
        }

        return [
            'id' => $offer->id,
            'price' => (float) $offer->price,
            'description' => $offer->description,
            'status' => $status,
            'accepted' => (bool) $offer->accepted,
            'created_at' => $offer->created_at,
            'updated_at' => $offer->updated_at,
            'ad' => [
                'id' => $ad->id,
                'task_id' => $ad->task_id,
                'description' => $ad->description,
                'status' => $ad->status,
                'lowest_price' => (float) $ad->lowest_price,
                'highest_price' => (float) $ad->highest_price,
                'task' => [
                    'pickup' => [
                        'address' => $task->pickup->address ?? '',
                        'contact_name' => $task->pickup->contact_name ?? ''
                    ],
                    'delivery' => [
                        'address' => $task->delivery->address ?? '',
                        'contact_name' => $task->delivery->contact_name ?? ''
                    ]
                ]
            ]
        ];
    }

    /**
     * Check if driver can submit offer
     */
    private function canSubmitOffer($ad, $driverOffer, $acceptedOffer)
    {
        // Cannot submit if ad is closed
        if ($ad->status !== 'running') {
            return false;
        }

        // Cannot submit if there's already an accepted offer
        if ($acceptedOffer) {
            return false;
        }

        // Cannot submit if driver already has an offer
        if ($driverOffer) {
            return false;
        }

        return true;
    }

    /**
     * Check if driver can view ad details
     */
    private function canViewDetails($ad, $driver_id, $acceptedOffer)
    {
        // Can always view running ads
        if ($ad->status === 'running') {
            return true;
        }

        // For closed ads, can only view if driver submitted an offer
        if ($ad->status === 'closed') {
            $driverOffer = Task_Offire::where('task_ad_id', $ad->id)
                                     ->where('driver_id', $driver_id)
                                     ->first();
            return $driverOffer ? true : false;
        }

        return false;
    }
}
