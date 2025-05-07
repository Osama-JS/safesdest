<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Task_Ad;
use Illuminate\Http\Request;

class TasksAdsController extends Controller
{
  public function index()
  {
    return view('admin.ads.index');
  }


  public function getData(Request $request)
  {
    $query = Task_Ad::query();

    // إضافة التصفية إذا كان هناك قيمة بحث
    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('name', 'ILIKE', '%' . $search . '%')
          ->orWhere('id', 'ILIKE', '%' . $search . '%');
      });
    }

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

    // إرجاع النتيجة مع التعداد (count) و pagination
    return response()->json(['data' => $products, 'count' => $products->total()]);
  }
}
