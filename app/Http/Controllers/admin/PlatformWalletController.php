<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlatformWalletController extends Controller
{
  /**
   * Display platform wallet page
   */
  public function index()
  {
    return view('admin.platform-wallet.index');
  }

  /**
   * Get platform commissions data for DataTable
   */
  public function data(Request $request)
  {
    $query = Task::with(['customer', 'driver', 'team', 'pickup', 'delivery'])
      ->where('status', 'completed')
      ->where('closed', 1)
      ->where('commission', '>', 0);

    // Apply filters
    if ($request->filled('date_from')) {
      $query->whereDate('completed_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
      $query->whereDate('completed_at', '<=', $request->date_to);
    }

    if ($request->filled('payment_status')) {
      $query->where('payment_paid', $request->payment_status);
    }

    if ($request->filled('commission_type')) {
      $query->where('commission_type', $request->commission_type);
    }

    $tasks = $query->orderBy('completed_at', 'desc')->get();

    $data = $tasks->map(function ($task) {
      return [
        'id' => $task->id,
        'customer' => $task->customer ? $task->customer->name : 'Admin',
        'driver' => $task->driver ? $task->driver->name : 'N/A',
        'team' => $task->team ? $task->team->name : 'N/A',
        'pickup_address' => $task->pickup ? $task->pickup->address : 'N/A',
        'delivery_address' => $task->delivery ? $task->delivery->address : 'N/A',
        'total_price' => number_format($task->total_price, 2),
        'commission' => number_format($task->commission, 2),
        'commission_type' => ucfirst($task->commission_type),
        'payment_status' => $task->payment_paid,
        'payment_method' => ucfirst($task->payment_method),
        'completed_at' => $task->completed_at ? $task->completed_at : 'N/A',
        'closed_at' => $task->closed_at ? $task->closed_at->format('Y-m-d H:i') : 'N/A',
      ];
    });

    return response()->json([
      'data' => $data
    ]);
  }

  /**
   * Get platform wallet statistics
   */
  public function statistics(Request $request)
  {
    // Base query ensures only completed and closed tasks with commission
    $baseQuery = Task::where('status', 'completed')
      ->where('closed', 1)
      ->where('commission', '>', 0);

    // Apply date filters if provided
    if ($request->filled('date_from')) {
      $baseQuery->whereDate('completed_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
      $baseQuery->whereDate('completed_at', '<=', $request->date_to);
    }

    // Debug: Log the query to ensure it's correct
    Log::info('Platform Wallet Statistics Query: ' . $baseQuery->toSql());
    Log::info('Platform Wallet Statistics Bindings: ', $baseQuery->getBindings());

    // Total commissions
    $totalCommissions = (clone $baseQuery)->sum('commission');

    // Paid commissions
    $paidCommissions = (clone $baseQuery)
      ->whereIn('payment_paid', ['all', 'just_commission'])
      ->sum('commission');

    // Pending commissions
    $pendingCommissions = (clone $baseQuery)
      ->where('payment_paid', 'pending')
      ->sum('commission');

    // Count of tasks
    $totalTasks = (clone $baseQuery)->count();
    $paidTasks = (clone $baseQuery)
      ->whereIn('payment_paid', ['all', 'just_commission'])
      ->count();
    $pendingTasks = (clone $baseQuery)
      ->where('payment_paid', 'pending')
      ->count();

    // Commission by type
    $dynamicCommissions = (clone $baseQuery)
      ->where('commission_type', 'dynamic')
      ->sum('commission');

    $manualCommissions = (clone $baseQuery)
      ->where('commission_type', 'manual')
      ->sum('commission');

    // Monthly statistics for chart - PostgreSQL compatible
    $monthlyStats = (clone $baseQuery)
      ->select(
        DB::raw('EXTRACT(YEAR FROM completed_at) as year'),
        DB::raw('EXTRACT(MONTH FROM completed_at) as month'),
        DB::raw('SUM(commission) as total_commission'),
        DB::raw('COUNT(*) as task_count')
      )
      ->groupBy(DB::raw('EXTRACT(YEAR FROM completed_at)'), DB::raw('EXTRACT(MONTH FROM completed_at)'))
      ->orderBy(DB::raw('EXTRACT(YEAR FROM completed_at)'), 'desc')
      ->orderBy(DB::raw('EXTRACT(MONTH FROM completed_at)'), 'desc')
      ->limit(12)
      ->get();

    // Validate data before returning
    $responseData = [
      'total_commissions' => $totalCommissions ?? 0,
      'paid_commissions' => $paidCommissions ?? 0,
      'pending_commissions' => $pendingCommissions ?? 0,
      'total_tasks' => $totalTasks ?? 0,
      'paid_tasks' => $paidTasks ?? 0,
      'pending_tasks' => $pendingTasks ?? 0,
      'dynamic_commissions' => $dynamicCommissions ?? 0,
      'manual_commissions' => $manualCommissions ?? 0,
      'monthly_stats' => $monthlyStats ?? [],
      'collection_rate' => $totalCommissions > 0 ? round(($paidCommissions / $totalCommissions) * 100, 2) : 0
    ];

    // Debug: Log the response data
    Log::info('Platform Wallet Statistics Response: ', $responseData);

    return response()->json([
      'success' => true,
      'data' => $responseData
    ]);
  }

  /**
   * Export platform wallet data
   */
  public function export(Request $request)
  {
    $query = Task::with(['customer', 'driver', 'team', 'pickup', 'delivery'])
      ->where('status', 'completed')
      ->where('closed', 1)
      ->where('commission', '>', 0);

    // Apply filters
    if ($request->filled('date_from')) {
      $query->whereDate('completed_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
      $query->whereDate('completed_at', '<=', $request->date_to);
    }

    if ($request->filled('payment_status')) {
      $query->where('payment_paid', $request->payment_status);
    }

    $tasks = $query->orderBy('completed_at', 'desc')->get();

    $filename = 'platform_wallet_' . date('Y-m-d_H-i-s') . '.csv';

    $headers = [
      'Content-Type' => 'text/csv',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function () use ($tasks) {
      $file = fopen('php://output', 'w');

      // CSV Headers
      fputcsv($file, [
        'Task ID',
        'Customer',
        'Driver',
        'Team',
        'Pickup Address',
        'Delivery Address',
        'Total Price',
        'Commission',
        'Commission Type',
        'Payment Status',
        'Payment Method',
        'Completed At',
        'Closed At'
      ]);

      // CSV Data
      foreach ($tasks as $task) {
        fputcsv($file, [
          $task->id,
          $task->customer ? $task->customer->name : 'Admin',
          $task->driver ? $task->driver->name : 'N/A',
          $task->team ? $task->team->name : 'N/A',
          $task->pickup ? $task->pickup->address : 'N/A',
          $task->delivery ? $task->delivery->address : 'N/A',
          $task->total_price,
          $task->commission,
          $task->commission_type,
          $task->payment_paid,
          $task->payment_method,
          $task->completed_at ? $task->completed_at->format('Y-m-d H:i:s') : '',
          $task->closed_at ? $task->closed_at->format('Y-m-d H:i:s') : ''
        ]);
      }

      fclose($file);
    };

    return response()->stream($callback, 200, $headers);
  }
}
