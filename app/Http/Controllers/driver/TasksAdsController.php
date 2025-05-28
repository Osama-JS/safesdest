<?php

namespace App\Http\Controllers\driver;

use App\Http\Controllers\Controller;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use Illuminate\Http\Request;

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
    return view('drivers.ads.show', compact('ad', 'task'));
  }
}
