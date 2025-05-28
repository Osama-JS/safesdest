<?php

namespace App\Http\Controllers\driver;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TasksController extends Controller
{
  public function index()
  {
    return view('drivers.tasks.index');
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'adress',
      3 => 'start',
      4 => 'complete',
      5 => 'status',
      6 => 'created_at'
    ];

    $totalData = Task::where('driver_id', auth()->user()->id)->count();
    $limit     = $request->input('length');
    $start     = $request->input('start');
    $order     = $columns[$request->input('order.0.column')] ?? 'id';
    $dir       = $request->input('order.0.dir') ?? 'desc';

    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');

    $query = Task::where('driver_id', auth()->user()->id);

    // ✅ فلترة بالتاريخ إذا كانت القيم موجودة
    if ($fromDate && $toDate) {
      $query->whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
      ]);
    }

    $totalFiltered = $query->count();

    $tasks = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    foreach ($tasks as $task) {
      $data[] = [
        'id'         => $task->id,
        'owner'     => $task->owner == "admin" ? $task->user->name : $task->customer->name,
        'owner_phone'     => $task->owner == "admin" ? $task->user->phone : $task->customer->phone,
        'address'    => $task->pickup->address ?? "-",
        'price'    => $task->total_price ? number_format($task->total_price - $task->commission, 2) : "0.00",
        'closed'     => $task->closed ? "Closed" : "Open",
        'start'      => ($task->pickup && $task->pickup->scheduled_time)
          ? Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i')
          : "",
        'complete'   => ($task->delivery && $task->delivery->scheduled_time)
          ? Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i')
          : "",
        'status'     => $task->status,
        'created_at' => $task->created_at->format('Y-m-d H:i'),
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
    $task = Task::with([
      'customer',
      'driver',
      'user',
      'pickup',
      'delivery',
      'points',
      'payments',
      'order',
      'formTemplate',
      'pricingTemplate',
      'vehicle_size',
      'history.user',
      'history.driver',
    ])
      ->where('driver_id', auth()->user()->id)
      ->findOrFail($id);

    return view('drivers.tasks.show', compact('task'));
  }
}
