<?php

namespace App\Http\Controllers\driver;

use App\Helpers\IpHelper;
use Exception;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TasksAdsController extends Controller
{
  public function index()
  {
    return view('drivers.ads.index');
  }

  public function getData(Request $request)
  {
    $size_id = auth()->user()->vehicle_size_id;
    $query = Task_Ad::where('status', 'running')
      ->whereHas('task', function ($q) use ($size_id) {
        $q->where('vehicle_size_id', $size_id);
      });


    $query->orderBy('id', 'DESC');

    // إضافة التصفية عن طريق pagination مباشرة
    $ads = $query->paginate(9); // 9 منتجات لكل صفحة

    // إضافة المعالجة المخصصة داخل صفحة البيانات
    $ads->getCollection()->transform(function ($ad) {
      return [
        'id' => $ad->id,
        'task_id' => $ad->task_id,
        'low_price' => $ad->lowest_price,
        'high_price' => $ad->highest_price,
        'note' => $ad->description,
        'status' => $ad->status,
        'customer' => [
          'owner'  => $ad->task->owner,
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

    return response()->json(['data' => $ads, 'count' => $ads->total()]);
  }

  public function show($id)
  {
    $ad = Task_Ad::with('task')->findOrFail($id);
    $task = $ad->task;
    $offer = Task_Offire::where('task_ad_id', $id)->where('driver_id', Auth::user()->id)->first();
    return view('drivers.ads.show', compact('ad', 'task', 'offer'));
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

  public function storeOffers(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'ad' => 'required|exists:tasks_ads,id',
      'price' => 'required|numeric',
      'description' => 'nullable|string|max:400',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    try {
      $data = [
        'price' => $req->price,
        'description' => $req->description,
      ];

      if ($req->filled('id')) {
        $find = Task_Offire::findOrFail($req->id);
        $done = $find->update($data);
      } else {
        $data['task_ad_id'] = $req->ad;
        $data['driver_id'] = Auth::user()->id;
        $done = Task_Offire::create($data);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Offer')]);
      }
      return response()->json(['status' => 1, 'success' => __('Offer saved successfully')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function assignTaskByOffer($id)
  {

    DB::beginTransaction();
    try {
      $data = Task_Offire::find($id);
      $user = auth()->user();
      if (!$user || $user->id !== $data->driver_id) {
        return response()->json([
          'status' => 2,
          'error' => __('You do not have permission to do actions to this record')
        ]);
      }
      if (!$data->accepted) {
        return response()->json([
          'status' => 2,
          'error' => __('This Offer is no accepted yet')
        ]);
      }

      $data_ad = $data->ad;
      if ($data_ad->status !== 'running') {
        return response()->json([
          'status' => 2,
          'error' => __('This Task Ad Already Closed')
        ]);
      }

      $data_task = $data->ad->task;
      if (!in_array($data_task->status, ['advertised'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This Task already assigned'),
        ]);
      }

      $userIp = IpHelper::getUserIpAddress();
      $history = [
        [
          'action_type' => 'assign',
          'description' => 'assign task By Task Ad Offers',
          'ip' => $userIp,
          'driver_id' => $user->id
        ]
      ];

      $data_task->driver_id = $user->id;
      $data_task->status = 'assign';
      $data_task->total_price = $data->price;

      $driver = Driver::findOrFail($user->id);

      if ($data_task->commission_type == 'dynamic') {
        $data_task->commission =  $driver->calculateCommission($data->price);
      }

      $data_task->history()->createMany($history);

      $data_task->save();

      $data_ad->status = 'closed';
      $data_ad->save();

      DB::commit();
      return response()->json(['status' => 1, 'success' => __('task assigned successfully')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
