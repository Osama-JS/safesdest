<?php

namespace App\Http\Controllers\customer;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdsController extends Controller
{
  public function index()
  {
    return view('customers.ads.index');
  }

  public function getData(Request $request)
  {
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
        'user' => Auth::user()->id,
        'customer' => [
          'owner'  => $ad->task->owner,
          'id'     => $ad->task->owner == "customer" ? optional($ad->task->customer)->id : optional($ad->task->user)->id,
          'name'   => $ad->task->owner == "customer" ? optional($ad->task->customer)->name : optional($ad->task->user)->name,
          'phone'  => $ad->task->owner == "customer" ? optional($ad->task->customer)->phone : optional($ad->task->user)->phone,
          'email'  => $ad->task->owner == "customer" ? optional($ad->task->customer)->email : optional($ad->task->user)->email,
          'image'  => $ad->task->owner == "customer" ? optional($ad->task->customer)->image : optional($ad->task->user)->image,
        ],
        'from_address' => $ad->task->pickup->address,
        'to_address' => $ad->task->delivery->address,
        'from_location' => [$ad->task->pickup->longitude, $ad->task->pickup->latitude],
        'to_location' => [$ad->task->delivery->longitude, $ad->task->delivery->latitude],
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
      'has_more_pages' => $products->hasMorePages()
    ];

    // Return enhanced response with pagination
    return response()->json([
      'data' => [
        'data' => $products->items(),
        'pagination' => $pagination
      ],
      'count' => $products->total()
    ]);
  }

  public function show($id)
  {
    $ad = Task_Ad::with('task')->findOrFail($id);
    $task = $ad->task;
    $offer = Task_Offire::where('task_ad_id', $id)->where('driver_id', Auth::user()->id)->first();
    return view('customers.ads.show', compact('ad', 'task', 'offer'));
  }

  public function getOffers(Request $req)
  {
    $offers = Task_Offire::where('task_ad_id', $req->id)->get();

    $transformed = $offers->map(function ($offer) {
      return [
        'id' => $offer->id,
        'driver' => $offer->driver,
        'driver_id' => $offer->driver_id,
        'price' => $offer->price,
        'accepted' => $offer->accepted,
        'description' => $offer->description,
        'ad_status' =>  $offer->ad->status
      ];
    });

    return response()->json([
      'data' => $transformed,
      'count' => $transformed->count(),
    ]);
  }



  public function acceptOffer($id)
  {
    $offer = Task_Offire::with('ad.task')->findOrFail($id);
    if ($offer->ad && $offer->ad->task && $offer->ad->task->customer_id !== Auth::id()) {
      return response()->json([
        'status' => 2,
        'error' => 'You do not have the right permission to do this action'
      ]);
    }
    if ($offer->ad->status !== 'running') {
      return response()->json(['status' => 2, 'error' => 'This Task ad is already closed']);
    }
    if ($offer->accepted) {
      return response()->json(['status' => 2, 'error' => 'This offer is already accepted']);
    }

    Task_Offire::where('task_ad_id', $offer->ad_id)->update(['accepted' => false]);

    $offer->accepted = true;
    $offer->save();
    return response()->json(['status' => 1, 'success' => __('The Offer accepted successfully')]);

    return response()->json(['message' => 'Offer accepted successfully.', 'offer' => $offer]);
  }

  public function retractOffer($id)
  {
    $offer = Task_Offire::with('ad.task')->findOrFail($id);
    if ($offer->ad && $offer->ad->task && $offer->ad->task->customer_id !== Auth::id()) {
      return response()->json([
        'status' => 2,
        'error' => 'You do not have the right permission to do this action'
      ]);
    }

    if ($offer->ad->status !== 'running') {
      return response()->json(['status' => 2, 'error' => 'This Task ad is already closed']);
    }
    if (!$offer->accepted) {
      return response()->json(['status' => 2, 'error' => 'This offer is already Retracted']);
    }


    Task_Offire::where('task_ad_id', $offer->ad_id)->update(['accepted' => true]);

    $offer->accepted = false;
    $offer->save();
    return response()->json(['status' => 1, 'success' => __('The Offer accepted successfully')]);

    return response()->json(['message' => 'Offer accepted successfully.', 'offer' => $offer]);
  }
}
