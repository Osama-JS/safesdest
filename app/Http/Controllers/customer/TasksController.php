<?php

namespace App\Http\Controllers\customer;

use Exception;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TasksController extends Controller
{
  public function index()
  {
    return view('customers.tasks.index');
  }



  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'order',
      3 => 'adress',
      4 => 'start',
      5 => 'complete',
      6 => 'status',
      7 => 'created_at'
    ];

    $totalData = Task::where('customer_id', auth()->user()->id)->count();
    $limit     = $request->input('length');
    $start     = $request->input('start');
    $order     = $columns[$request->input('order.0.column')] ?? 'id';
    $dir       = $request->input('order.0.dir') ?? 'desc';

    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');


    $query = Task::where('customer_id', auth()->user()->id);

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
        'order'      => $task->order->id ?? "-",
        'driver'     => $task->driver ?? '-',
        'address'    => $task->pickup->address ?? "-",
        'start'      => ($task->pickup && $task->pickup->scheduled_time)
          ? Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i')
          : "",
        'complete'   => ($task->delivery && $task->delivery->scheduled_time)
          ? Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i')
          : "",
        'status'     => $task->status,
        'closed'     => $task->closed,
        'payment'     => $task->payment_status,
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

  public function paymentInfo($id)
  {
    try {
      $data = Task::findOrFail($id);
      if ($data->customer_id !== Auth::user()->id) {
        return response()->json([
          'status' => 2,
          'error' => __('Error to find the task'),
        ]);
      }
      if (in_array($data->status, ['in_progress', 'advertised'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This task cannot be Payed in its current state'),
        ]);
      }
      if ($data->payment_status !== 'waiting') {
        $transiction = Transaction::where('reference_id', $data->id)->first();
        return response()->json([
          'status' => 3,
          'message' => __('This task has already make payment request and it is ' . $data->payment_status),
          'data' => $transiction
        ]);
      }
      return response()->json($data);
    } catch (Exception $e) {
      return response()->json([
        'status' => 2,
        'error' => __('Task not found')
      ]);
    }
  }

  public function showDetails($id)
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
    ])->findOrFail($id);

    if ($task->customer_id !== Auth::user()->id) {
      return redirect()->back();
    }
    return view('admin.tasks.show', compact('task'));
  }
}
