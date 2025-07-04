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
    $taskIds = Task::where('customer_id', Auth::user()->id)->pluck('id');
    $query = Task_Ad::whereIn('task_id', $taskIds)->orderBy('id', 'DESC');

    // ترتيب البيانات حسب الـ id بشكل تنازلي
    $query->orderBy('id', 'DESC');

    // إضافة التصفية عن طريق pagination مباشرة
    $products = $query->paginate(9); // 9 منتجات لكل صفحة

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
    return response()->json(['data' => $products, 'count' => $products->total()]);
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
