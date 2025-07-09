<?php

namespace App\Http\Controllers\admin;

use App\Helpers\IpHelper;
use Exception;
use App\Models\Task;
use App\Models\Task_Ad;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Task_Offire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TasksAdsController extends Controller
{
  public function index()
  {

    return view('admin.ads.index');
  }


  public function getData(Request $request)
  {
    $query = Task_Ad::with(['task.customer', 'task.user', 'task.pickup', 'task.delivery']);

    // Search filter
    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->whereHas('task', function ($q) use ($search) {
        $q->whereHas('customer', function ($customerQuery) use ($search) {
          $customerQuery->where('name', 'ILIKE', '%' . $search . '%');
        })->orWhereHas('user', function ($userQuery) use ($search) {
          $userQuery->where('name', 'ILIKE', '%' . $search . '%');
        })->orWhereHas('pickup', function ($pickupQuery) use ($search) {
          $pickupQuery->where('address', 'ILIKE', '%' . $search . '%');
        })->orWhereHas('delivery', function ($deliveryQuery) use ($search) {
          $deliveryQuery->where('address', 'ILIKE', '%' . $search . '%');
        });
      })->orWhere('description', 'ILIKE', '%' . $search . '%');
    }

    // Status filter
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }

    // Price range filter
    if ($request->has('price_range') && !empty($request->price_range)) {
      $priceRange = $request->price_range;
      switch ($priceRange) {
        case '0-100':
          $query->where(function ($q) {
            $q->whereBetween('lowest_price', [0, 100])
              ->orWhereBetween('highest_price', [0, 100]);
          });
          break;
        case '100-500':
          $query->where(function ($q) {
            $q->whereBetween('lowest_price', [100, 500])
              ->orWhereBetween('highest_price', [100, 500]);
          });
          break;
        case '500-1000':
          $query->where(function ($q) {
            $q->whereBetween('lowest_price', [500, 1000])
              ->orWhereBetween('highest_price', [500, 1000]);
          });
          break;
        case '1000+':
          $query->where(function ($q) {
            $q->where('lowest_price', '>=', 1000)
              ->orWhere('highest_price', '>=', 1000);
          });
          break;
      }
    }

    // Date filter
    if ($request->has('date') && !empty($request->date)) {
      $dateFilter = $request->date;
      switch ($dateFilter) {
        case 'today':
          $query->whereDate('created_at', today());
          break;
        case 'week':
          $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
          break;
        case 'month':
          $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
          break;
      }
    }

    // Owner filter
    if ($request->has('owner') && !empty($request->owner)) {
      $ownerType = $request->owner;
      if ($ownerType === 'customer') {
        $query->whereHas('task', function ($q) {
          $q->whereNotNull('customer_id');
        });
      } elseif ($ownerType === 'admin') {
        $query->whereHas('task', function ($q) {
          $q->whereNotNull('user_id');
        });
      }
    }

    // Sorting
    $sort = $request->get('sort', 'newest');
    switch ($sort) {
      case 'oldest':
        $query->orderBy('created_at', 'ASC');
        break;
      case 'price_high':
        $query->orderBy('highest_price', 'DESC');
        break;
      case 'price_low':
        $query->orderBy('lowest_price', 'ASC');
        break;
      default: // newest
        $query->orderBy('created_at', 'DESC');
        break;
    }

    // Pagination
    $perPage = $request->get('per_page', 9);
    $products = $query->paginate($perPage);

    // Calculate stats
    $stats = $this->calculateStats();

    // إضافة المعالجة المخصصة داخل صفحة البيانات
    $products->getCollection()->transform(function ($ad) {
      return [
        'id' => $ad->id,
        'task_id' => $ad->task_id,
        'low_price' => $ad->lowest_price,
        'high_price' => $ad->highest_price,
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

    // إرجاع النتيجة مع التعداد (count) و pagination
    return response()->json([
      'data' => $products,
      'count' => $products->total(),
      'stats' => $stats
    ]);
  }

  /**
   * Calculate statistics for ads
   */
  private function calculateStats()
  {
    $totalAds = Task_Ad::count();
    $runningAds = Task_Ad::where('status', 'running')->count();
    $closedAds = Task_Ad::where('status', 'closed')->count();

    // Calculate average price
    $avgPrice = Task_Ad::whereNotNull('lowest_price')
      ->whereNotNull('highest_price')
      ->selectRaw('AVG((lowest_price + highest_price) / 2) as avg_price')
      ->value('avg_price');

    return [
      'total' => $totalAds,
      'running' => $runningAds,
      'closed' => $closedAds,
      'avg_price' => round($avgPrice ?: 0, 0)
    ];
  }

  public function show($id)
  {
    $ad = Task_Ad::with('task')->findOrFail($id);
    $task = $ad->task;
    $offer = Task_Offire::where('task_ad_id', $id)->where('driver_id', Auth::user()->id)->first();
    return view('admin.ads.show', compact('ad', 'task', 'offer'));
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
    if ($offer->ad && $offer->ad->task && $offer->ad->task->user_id !== Auth::id()) {
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
    if ($offer->ad && $offer->ad->task && $offer->ad->task->user_id !== Auth::id()) {
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




  public function editByTask($id)
  {
    try {
      $task = Task::findOrFail($id);
      if ($task->ad) {
        $data = $task->ad;
        return response()->json(['status' => 1, 'data' => $data]);
      }
      return response()->json(['status' => 2, 'error' => 'There is no ad for this task']);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function edit($id)
  {
    try {
      $data = Task_Ad::findOrFail($id);
      return response()->json(['status' => 1, 'data' => $data]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function update(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'min_price' => 'required|numeric|min:0',
      'max_price' => 'required|numeric|gt:min_price',
      'note_price' => 'nullable|string|max:400',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    try {

      $find = Task_Ad::findOrFail($req->id);

      $done = $find->update([
        'lowest_price' => $req->min_price,
        'highest_price' => $req->max_price,
        'description' => $req->note_price,
      ]);

      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Tag')]);
      }
      return response()->json(['status' => 1, 'success' => __('Tag saved successfully')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
