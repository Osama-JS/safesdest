<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Task;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class CustomerAdsController extends Controller
{
    public function getData(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 8);
            $page = $request->get('page', 1);
            $search = $request->get('search', '');
            $status = $request->get('status', '');
            $price = $request->get('price', '');

            $taskIds = Task::where('customer_id', Auth::user()->id)->pluck('id');
            $query = Task_Ad::with(['task.customer', 'task.user', 'task.pickup', 'task.delivery'])
                ->whereIn('task_id', $taskIds);

            // Apply search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'LIKE', "%{$search}%")
                        ->orWhereHas('task.pickup', function ($pickup) use ($search) {
                            $pickup->where('address', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('task.delivery', function ($delivery) use ($search) {
                            $delivery->where('address', 'LIKE', "%{$search}%");
                        });
                });
            }

            // Apply status filter
            if (!empty($status)) {
                $query->where('status', $status);
            }

            // Apply price filter
            if (!empty($price)) {
                if ($price === '0-100') {
                    $query->where(function ($q) {
                        $q->where('lowest_price', '<=', 100)
                            ->orWhere('highest_price', '<=', 100);
                    });
                } elseif ($price === '100-500') {
                    $query->where(function ($q) {
                        $q->whereBetween('lowest_price', [100, 500])
                            ->orWhereBetween('highest_price', [100, 500]);
                    });
                } elseif ($price === '500+') {
                    $query->where(function ($q) {
                        $q->where('lowest_price', '>=', 500)
                            ->orWhere('highest_price', '>=', 500);
                    });
                }
            }

            // Order by latest first
            $query->orderBy('id', 'DESC');

            // Get paginated results
            $products = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform data for enhanced display
            $products->getCollection()->transform(function ($ad) {
                return [
                    'id' => $ad->id,
                    'task_id' => $ad->task_id,
                    'low_price' => $ad->final_lowest_price,
                    'high_price' => $ad->final_highest_price,
                    'note' => $ad->description,
                    'status' => $ad->status,
                    'from_address' => $ad->task->pickup->address,
                    'to_address' => $ad->task->delivery->address,
                    'from_lng' => $ad->task->pickup->longitude,
                    'from_lat' => $ad->task->pickup->latitude,
                    'to_lat' => $ad->task->delivery->latitude,
                    'to_lng' => $ad->task->delivery->longitude,
                ];
            });

            // Transform pagination data
            $pagination = [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ];

            // Return enhanced response with pagination
            return response()->json([
                'status' => 200,
                'data' => [
                    'data' => $products->items(),
                    'pagination' => $pagination
                ],
                'count' => $products->total()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get ads data',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $ad = Task_Ad::with('task.pickup', 'task.delivery', 'task.customer', 'task.user')->findOrFail($id);
            $task = $ad->task;

            // Check if user has permission to view this ad
            if ($task->customer_id !== Auth::user()->id) {
                return response()->json([
                    'status' => 2,
                    'error' => 'You do not have permission to view this ad'
                ]);
            }

            $transformedAd = [
                'id' => $ad->id,
                'task_id' => $ad->task_id,
                'low_price' => $ad->final_lowest_price,
                'high_price' => $ad->final_highest_price,
                'note' => $ad->description,
                'status' => $ad->status,
                'included' => $ad->included,
                'service_commission_type' => $ad->service_commission_type,
                'service_commission' => $ad->service_commission,
                'vat_commission' => $ad->vat_commission,
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'total_price' => $task->total_price,
                    'conditions' => $task->conditions,
                    'pickup' => $task->pickup,
                    'delivery' => $task->delivery,
                    'additional_data' => $task->additional_data,
                ],
                'customer' => [
                    'owner'  => $task->owner,
                    'id'     => $task->owner == "customer" ? optional($task->customer)->id : optional($task->user)->id,
                    'name'   => $task->owner == "customer" ? optional($task->customer)->name : optional($task->user)->name,
                    'phone'  => $task->owner == "customer" ? optional($task->customer)->phone : optional($task->user)->phone,
                    'email'  => $task->owner == "customer" ? optional($task->customer)->email : optional($task->user)->email,
                    'image'  => $task->owner == "customer" ? optional($task->customer)->image : optional($task->user)->image,
                ],
            ];

            return response()->json([
                'status' => 200,
                'data' => $transformedAd
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get ad details',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getOffers(Request $req)
    {
        try {
            $offers = Task_Offire::where('task_ad_id', $req->id)->with('driver')->get();

            $transformed = $offers->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'driver_name' => $offer->driver->name,
                    'driver_image' => $offer->driver->image ? url($offer->driver->image) : null,

                    'driver_id' => $offer->driver_id,
                    'price' => $offer->price,
                    'accepted' => $offer->accepted,
                    'description' => $offer->description,
                ];
            });

            return response()->json([
                'status' => 200,
                'data' => $transformed,
                'count' => $transformed->count(),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get offers',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function acceptOffer($id)
    {
        try {
            $offer = Task_Offire::with('ad.task')->findOrFail($id);

            if ($offer->ad && $offer->ad->task && $offer->ad->task->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 2,
                    'message' => 'You do not have the right permission to do this action'
                ]);
            }

            if ($offer->ad->status !== 'running') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This Task ad is already closed'
                ]);
            }

            if ($offer->accepted) {
                return response()->json([
                    'status' => 400,
                    'message' => 'This offer is already accepted'
                ]);
            }

            // Set all other offers for this ad to not accepted
            Task_Offire::where('task_ad_id', $offer->ad_id)->update(['accepted' => false]);

            // Accept this offer
            $offer->accepted = true;
            $offer->save();

            // Send notification to driver
            app(\App\Services\NotificationService::class)->send(
                'driver',
                [$offer->driver_id],
                '🎉 تم قبول عرضك!',
                "تم قبول عرضك للمهمة رقم #{$offer->ad->task_id} من قبل العميل. يرجى الدخول للتأكيد.",
                '/images/admin-icon.png',
                '/images/banner.png',
                "/task-ads/{$offer->task_ad_id}",
                'offer_accepted'
            );

            return response()->json([
                'status' => 200,
                'message' => __('The Offer accepted successfully')
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to accept offer',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function retractOffer($id)
    {
        try {
            $offer = Task_Offire::with('ad.task')->findOrFail($id);

            if ($offer->ad && $offer->ad->task && $offer->ad->task->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You do not have the right permission to do this action'
                ]);
            }

            if ($offer->ad->status !== 'running') {
                return response()->json([
                    'status' => 400,
                    'message' => 'This Task ad is already closed'
                ]);
            }

            if (!$offer->accepted) {
                return response()->json([
                    'status' => 400,
                    'message' => 'This offer is already retracted'
                ]);
            }

            // Retract this offer
            $offer->accepted = false;
            $offer->save();

            // Send notification to driver
            app(\App\Services\NotificationService::class)->send(
                'driver',
                [$offer->driver_id],
                '⚠️ تم التراجع عن قبول عرضك',
                "قام العميل بالتراجع عن قبول عرضك للمهمة رقم #{$offer->ad->task_id}.",
                '/images/admin-icon.png',
                '/images/banner.png',
                "/task-ads/{$offer->task_ad_id}",
                'offer_retracted'
            );

            return response()->json([
                'status' => 200,
                'message' => __('The Offer retracted successfully')
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to retract offer',
                'error' => $e->getMessage()
            ]);
        }
    }

}
