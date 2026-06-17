<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PlatformWalletExport;

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
      ->withSum(['userWalletTransactions as total_given_commission' => function($q) {
          $q->where('transaction_type', 'credit');
      }], 'amount')
      ->where('commission', '>', 0);

    // Default exclusion if no status filter is active
    if (!$request->filled('task_status')) {
      $query->whereNotIn('status', ['canceled', 'refund']);
    } else {
      $query->where('status', $request->task_status);
    }

    if ($request->filled('is_closed')) {
      $query->where('closed', $request->is_closed);
    }

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
        'task_status' => $task->status,
        'is_closed' => $task->closed,
        'completed_at' => $task->completed_at ? $task->completed_at : 'N/A',
        'closed_at' => $task->closed_at ? $task->closed_at->format('Y-m-d H:i') : 'N/A',
        'net_commission' => number_format(max(0, $task->commission - ($task->total_given_commission ?? 0)), 2),
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
    // Base query ensures tasks have commission
    $baseQuery = Task::where('commission', '>', 0)
        ->withSum(['userWalletTransactions as total_given_commission' => function($q) {
            $q->where('transaction_type', 'credit');
        }], 'amount');

    // Apply Filters (Same as data method)
    if ($request->filled('task_status')) {
      $baseQuery->where('status', $request->task_status);
    } else {
      $baseQuery->whereNotIn('status', ['canceled', 'refund']);
    }

    if ($request->filled('is_closed')) {
      $baseQuery->where('closed', $request->is_closed);
    }

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

    // Net commission calculations
    // We need to sum (commission - total_given_commission) for the whole query.
    // Since total_given_commission is aggregated via withSum, we cannot just do ->sum('commission - total_given_commission') easily in Eloquent without raw query.
    // However, we can fetch the tasks and sum them in PHP if the count is reasonable, or use a DB::raw subquery.
    // Given the statistics method might be called for many records, a raw query is better.
    // But since $baseQuery already has filters applied, let's just use get() and sum it using collections to be safe and accurate.
    $allTasksForNet = (clone $baseQuery)->get(['id', 'commission']); // withSum is already added
    $totalNetCommissions = $allTasksForNet->sum(function($task) {
        return max(0, $task->commission - ($task->total_given_commission ?? 0));
    });

    $paidTasksForNet = clone $baseQuery;
    $paidTasksForNet = $paidTasksForNet->whereIn('payment_paid', ['all', 'just_commission'])->get(['id', 'commission']);
    $paidNetCommissions = $paidTasksForNet->sum(function($task) {
        return max(0, $task->commission - ($task->total_given_commission ?? 0));
    });

    $pendingTasksForNet = clone $baseQuery;
    $pendingTasksForNet = $pendingTasksForNet->where('payment_paid', 'pending')->get(['id', 'commission']);
    $pendingNetCommissions = $pendingTasksForNet->sum(function($task) {
        return max(0, $task->commission - ($task->total_given_commission ?? 0));
    });

    $isNetFilter = $request->filled('net_commission_filter') && $request->net_commission_filter == 1;

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
      'total_commissions' => $isNetFilter ? $totalNetCommissions : ($totalCommissions ?? 0),
      'paid_commissions' => $isNetFilter ? $paidNetCommissions : ($paidCommissions ?? 0),
      'pending_commissions' => $isNetFilter ? $pendingNetCommissions : ($pendingCommissions ?? 0),
      'total_tasks' => $totalTasks ?? 0,
      'paid_tasks' => $paidTasks ?? 0,
      'pending_tasks' => $pendingTasks ?? 0,
      'dynamic_commissions' => $dynamicCommissions ?? 0,
      'manual_commissions' => $manualCommissions ?? 0,
      'monthly_stats' => $monthlyStats ?? [],
      'collection_rate' => $totalCommissions > 0 ? round((($isNetFilter ? $paidNetCommissions : $paidCommissions) / ($isNetFilter ? $totalNetCommissions : $totalCommissions)) * 100, 2) : 0
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
      ->where('commission', '>', 0);

    // Apply Filters (Same as data method)
    if ($request->filled('task_status')) {
      $query->where('status', $request->task_status);
    } else {
      $query->whereNotIn('status', ['canceled', 'refund']);
    }

    if ($request->filled('is_closed')) {
      $query->where('closed', $request->is_closed);
    }

    // Apply other filters
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

  /**
   * Export platform wallet data to Excel (Professional format)
   */
  public function exportExcel(Request $request)
  {
    // 1. Get Tasks Data (Same logic as data() method)
    $query = Task::with(['customer', 'driver', 'team', 'pickup', 'delivery'])
      ->withSum(['userWalletTransactions as total_given_commission' => function($q) {
          $q->where('transaction_type', 'credit');
      }], 'amount')
      ->where('commission', '>', 0);

    if ($request->filled('task_status')) {
      $query->where('status', $request->task_status);
    } else {
      $query->whereNotIn('status', ['canceled', 'refund']);
    }

    if ($request->filled('is_closed')) {
      $query->where('closed', $request->is_closed);
    }

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

    $tasks = $query->orderBy('id', 'desc')->get();

    $mappedData = $tasks->map(function ($task) {
      return [
        'id' => $task->id,
        'customer' => $task->customer ? $task->customer->name : 'Admin',
        'driver' => $task->driver ? $task->driver->name : 'N/A',
        'team' => $task->team ? $task->team->name : 'N/A',
        'pickup_address' => $task->pickup ? $task->pickup->address : 'N/A',
        'delivery_address' => $task->delivery ? $task->delivery->address : 'N/A',
        'total_price' => $task->total_price,
        'commission' => $task->commission,
        'net_commission' => max(0, $task->commission - ($task->total_given_commission ?? 0)),
        'commission_type' => ucfirst($task->commission_type),
        'payment_status' => $task->payment_paid,
        'task_status' => $task->status,
        'completed_at' => $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : 'N/A',
      ];
    })->toArray();

    // 2. Get Statistics (Same logic as statistics() method)
    $statsResponse = $this->statistics($request);
    $statistics = $statsResponse->getData()->data;

    // 3. Filters for header
    $filters = [
      'date_from' => $request->date_from,
      'date_to' => $request->date_to,
      'net_commission_filter' => $request->net_commission_filter
    ];

    $filename = 'platform_wallet_report_' . date('Y-m-d_H-i') . '.xlsx';

    return Excel::download(
      new PlatformWalletExport($mappedData, (array)$statistics, $filters),
      $filename
    );
  }
}
