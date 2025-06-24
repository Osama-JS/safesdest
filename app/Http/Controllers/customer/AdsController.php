<?php

namespace App\Http\Controllers\customer;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Task_Ad;
use App\Models\Task_Ad_Offer;
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
    $columns = [
      1 => 'id',
      2 => 'task_id',
      3 => 'pickup_address',
      4 => 'delivery_address',
      5 => 'lowest_price',
      6 => 'highest_price',
      7 => 'offers_count',
      8 => 'status',
      9 => 'created_at'
    ];

    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');
    $search    = $request->input('search');
    $status    = $request->input('status');

    $query = Task_Ad::query();
    $query->whereHas('task', function ($q) {
      $q->where('customer_id', Auth::user()->id);
    });

    $totalData = $query->count();

    if ($fromDate && $toDate) {
      $query->whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
      ]);
    }

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('description', 'LIKE', "%{$search}%")
          ->orWhereHas('task', function ($task) use ($search) {
            $task->where('id', 'LIKE', "%{$search}%")
              ->orWhereHas('pickup', function ($pickup) use ($search) {
                $pickup->where('address', 'LIKE', "%{$search}%");
              })
              ->orWhereHas('delivery', function ($delivery) use ($search) {
                $delivery->where('address', 'LIKE', "%{$search}%");
              });
          });
      });
    }

    if (!empty($status)) {
      $query->whereHas('task', function ($q) use ($status) {
        $q->where('status', $status);
      });
    }

    $totalFiltered = $query->count();

    $limit = $request->input('length');
    $start = $request->input('start');
    $order = $columns[$request->input('order.0.column')] ?? 'id';
    $dir   = $request->input('order.0.dir') ?? 'desc';

    $ads = $query
      ->with([
        'task.pickup',
        'task.delivery',
        'task.vehicle_size.type.vehicle',
        'offers' => function ($q) {
          $q->where('status', 'pending');
        }
      ])
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    $fakeId = $start;

    foreach ($ads as $ad) {
      $data[] = [
        'id' => $ad->id,
        'fake_id' => ++$fakeId,
        'task_id' => $ad->task->id,
        'pickup_address' => $ad->task->pickup ? $ad->task->pickup->address : 'N/A',
        'delivery_address' => $ad->task->delivery ? $ad->task->delivery->address : 'N/A',
        'vehicle_info' => $ad->task->vehicle_size ? 
          $ad->task->vehicle_size->type->vehicle->name . ' - ' . $ad->task->vehicle_size->type->name . ' - ' . $ad->task->vehicle_size->name 
          : 'N/A',
        'lowest_price' => number_format($ad->lowest_price, 2) . ' SAR',
        'highest_price' => number_format($ad->highest_price, 2) . ' SAR',
        'offers_count' => $ad->offers->count(),
        'description' => $ad->description ?? 'N/A',
        'status' => $ad->task->status,
        'task_closed' => $ad->task->closed,
        'created_at' => $ad->created_at->format('Y-m-d H:i'),
      ];
    }

    return response()->json([
      'draw'            => intval($request->input('draw')),
      'recordsTotal'    => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code'            => 200,
      'data'            => $data,
    ]);
  }

  public function show($id)
  {
    $ad = Task_Ad::with([
      'task.pickup',
      'task.delivery',
      'task.customer',
      'task.vehicle_size.type.vehicle',
      'task.formTemplate.fields',
      'offers.driver',
      'offers' => function ($q) {
        $q->orderBy('price', 'asc');
      }
    ])->whereHas('task', function ($q) {
      $q->where('customer_id', Auth::user()->id);
    })->findOrFail($id);

    return view('customers.ads.show', compact('ad'));
  }

  public function acceptOffer(Request $request, $adId, $offerId)
  {
    $request->validate([
      'offer_id' => 'required|exists:task_ad_offers,id'
    ]);

    DB::beginTransaction();
    try {
      $ad = Task_Ad::with('task')->whereHas('task', function ($q) {
        $q->where('customer_id', Auth::user()->id);
      })->findOrFail($adId);

      $offer = Task_Ad_Offer::where('id', $offerId)
        ->where('task_ad_id', $adId)
        ->where('status', 'pending')
        ->firstOrFail();

      // Check if task is still advertised
      if ($ad->task->status !== 'advertised' || $ad->task->closed) {
        return response()->json([
          'status' => 2,
          'error' => 'This task is no longer available for offers.'
        ]);
      }

      // Update task
      $ad->task->update([
        'driver_id' => $offer->driver_id,
        'total_price' => $offer->price,
        'status' => 'assign',
        'pricing_type' => 'manual'
      ]);

      // Accept the offer
      $offer->update(['status' => 'accepted']);

      // Reject all other offers
      Task_Ad_Offer::where('task_ad_id', $adId)
        ->where('id', '!=', $offerId)
        ->update(['status' => 'rejected']);

      // Add history
      $ad->task->history()->create([
        'action_type' => 'assign',
        'description' => 'Driver assigned through ad offer acceptance',
        'customer_id' => Auth::user()->id,
        'driver_id' => $offer->driver_id,
      ]);

      DB::commit();

      return response()->json([
        'status' => 1,
        'success' => 'Offer accepted successfully! Driver has been assigned to the task.'
      ]);

    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error' => 'Failed to accept offer. Please try again.'
      ]);
    }
  }

  public function rejectOffer(Request $request, $adId, $offerId)
  {
    try {
      $ad = Task_Ad::whereHas('task', function ($q) {
        $q->where('customer_id', Auth::user()->id);
      })->findOrFail($adId);

      $offer = Task_Ad_Offer::where('id', $offerId)
        ->where('task_ad_id', $adId)
        ->where('status', 'pending')
        ->firstOrFail();

      $offer->update(['status' => 'rejected']);

      return response()->json([
        'status' => 1,
        'success' => 'Offer rejected successfully.'
      ]);

    } catch (\Exception $e) {
      return response()->json([
        'status' => 2,
        'error' => 'Failed to reject offer. Please try again.'
      ]);
    }
  }
}
