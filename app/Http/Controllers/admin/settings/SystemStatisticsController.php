<?php

namespace App\Http\Controllers\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\Wallet_Transaction;
use App\Models\Team_Wallet_Transaction;
use Carbon\Carbon;

class SystemStatisticsController extends Controller
{
  public function __construct()
  {
    $this->middleware('permission:view_statistics');
  }

  /**
   * Display system statistics page
   */
  public function index()
  {
    return view('admin.settings.statistics.index');
  }

  /**
   * Get system statistics data
   */
  public function getData(Request $request)
  {
    $request->validate([
      'date_from' => 'nullable|date',
      'date_to' => 'nullable|date|after_or_equal:date_from'
    ]);

    try {
      $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
      $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();

      // Calculate previous period for growth comparison
      $daysCount = $dateFrom->diffInDays($dateTo) + 1;
      $prevDateTo = (clone $dateFrom)->subSecond();
      $prevDateFrom = (clone $prevDateTo)->subDays($daysCount)->startOfDay();

      // Get current period statistics
      $tasksStats = $this->getTasksStatistics($dateFrom, $dateTo);
      $financialStats = $this->getFinancialStatistics($dateFrom, $dateTo);
      $operationalStats = $this->getOperationalStatistics($dateFrom, $dateTo);
      $topPerformers = $this->getTopPerformers($dateFrom, $dateTo);

      // Get previous period statistics for growth comparison
      $prevTasksStats = $this->getTasksStatistics($prevDateFrom, $prevDateTo);
      $prevFinancialStats = $this->getFinancialStatistics($prevDateFrom, $prevDateTo);

      $growth = [
        'tasks' => $this->calculateGrowthRate($tasksStats['total_tasks'], $prevTasksStats['total_tasks']),
        'revenue' => $this->calculateGrowthRate($financialStats['total_revenue'], $prevFinancialStats['total_revenue']),
        'commission' => $this->calculateGrowthRate($financialStats['total_commission'], $prevFinancialStats['total_commission']),
        'net_commission' => $this->calculateGrowthRate($financialStats['net_commission'], $prevFinancialStats['net_commission']),
      ];

      // Get chart data
      $chartData = $this->getChartData($dateFrom, $dateTo);

      return response()->json([
        'status' => 1,
        'data' => [
          'tasks' => $tasksStats,
          'financial' => $financialStats,
          'operational' => $operationalStats,
          'top_performers' => $topPerformers,
          'growth' => $growth,
          'charts' => $chartData,
          'period' => [
            'from' => $dateFrom->format('Y-m-d'),
            'to' => $dateTo->format('Y-m-d')
          ]
        ]
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 2,
        'error' => __('Failed to load statistics: ') . $e->getMessage()
      ]);
    }
  }

  /**
   * Calculate percentage growth between two periods
   */
  private function calculateGrowthRate($current, $previous)
  {
    $current = (float) $current;
    $previous = (float) $previous;

    if ($previous <= 0) {
      return $current > 0 ? 100 : 0;
    }

    $rate = (($current - $previous) / $previous) * 100;
    return round($rate, 1);
  }

  /**
   * Get tasks statistics (Optimized Single-Pass SQL)
   */
  private function getTasksStatistics($dateFrom, $dateTo)
  {
    $stats = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->selectRaw("
        COUNT(*) as total_tasks,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN status IN ('canceled', 'cancelled', 'refunded', 'refund') THEN 1 END) as cancelled_tasks,
        COUNT(CASE WHEN status NOT IN ('completed', 'canceled', 'cancelled', 'refunded', 'refund') THEN 1 END) as in_progress_tasks,
        COUNT(CASE WHEN closed = true THEN 1 END) as closed_tasks,
        COUNT(CASE WHEN (closed = false OR closed IS NULL) AND status NOT IN ('canceled', 'cancelled', 'refunded', 'refund') THEN 1 END) as unclosed_tasks
      ")
      ->first();

    $totalTasks = (int) ($stats->total_tasks ?? 0);
    $completedTasks = (int) ($stats->completed_tasks ?? 0);
    $cancelledTasks = (int) ($stats->cancelled_tasks ?? 0);
    $inProgressTasks = (int) ($stats->in_progress_tasks ?? 0);
    $closedTasks = (int) ($stats->closed_tasks ?? 0);
    $unclosedTasks = (int) ($stats->unclosed_tasks ?? 0);

    // Tasks by status with detailed amounts (Canceled/refunded tasks strictly have 0 amount)
    $tasksByStatus = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->select(
        'status',
        DB::raw('count(*) as count'),
        DB::raw("COALESCE(SUM(CASE WHEN status NOT IN ('canceled', 'cancelled', 'refunded', 'refund') AND closed = true THEN total_price ELSE 0 END), 0) as closed_amount"),
        DB::raw("COALESCE(SUM(CASE WHEN status NOT IN ('canceled', 'cancelled', 'refunded', 'refund') AND (closed = false OR closed IS NULL) THEN total_price ELSE 0 END), 0) as unclosed_amount"),
        DB::raw("COALESCE(SUM(CASE WHEN status NOT IN ('canceled', 'cancelled', 'refunded', 'refund') THEN total_price ELSE 0 END), 0) as total_amount")
      )
      ->groupBy('status')
      ->get()
      ->keyBy('status')
      ->toArray();

    // Tasks by payment status
    $tasksByPaymentStatus = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->select('payment_status', DB::raw('count(*) as count'))
      ->groupBy('payment_status')
      ->pluck('count', 'payment_status')
      ->toArray();

    return [
      'total_tasks' => $totalTasks,
      'completed_tasks' => $completedTasks,
      'cancelled_tasks' => $cancelledTasks,
      'in_progress_tasks' => $inProgressTasks,
      'closed_tasks' => $closedTasks,
      'unclosed_tasks' => $unclosedTasks,
      'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
      'tasks_by_status' => $tasksByStatus,
      'tasks_by_payment_status' => $tasksByPaymentStatus
    ];
  }

  /**
   * Get financial statistics (with Closed vs Unclosed breakdown & Net Platform Profit)
   */
  private function getFinancialStatistics($dateFrom, $dateTo)
  {
    $sql = "
      SELECT
        COUNT(*) AS total_count,
        COUNT(CASE WHEN t.closed = true THEN 1 END) AS closed_count,
        COUNT(CASE WHEN t.closed = false OR t.closed IS NULL THEN 1 END) AS unclosed_count,

        COALESCE(SUM(t.total_price), 0) AS total_revenue,
        COALESCE(SUM(CASE WHEN t.closed = true THEN t.total_price ELSE 0 END), 0) AS closed_revenue,
        COALESCE(SUM(CASE WHEN t.closed = false OR t.closed IS NULL THEN t.total_price ELSE 0 END), 0) AS unclosed_revenue,

        COALESCE(SUM(t.commission), 0) AS total_commission,
        COALESCE(SUM(CASE WHEN t.closed = true THEN t.commission ELSE 0 END), 0) AS closed_commission,
        COALESCE(SUM(CASE WHEN t.closed = false OR t.closed IS NULL THEN t.commission ELSE 0 END), 0) AS unclosed_commission,

        COALESCE(SUM(CASE WHEN t.closed = true THEN COALESCE(ded.total_deductions, 0) ELSE 0 END), 0) AS closed_deductions,
        COALESCE(SUM(CASE WHEN t.closed = true THEN GREATEST(0, t.commission - COALESCE(ded.total_deductions, 0)) ELSE 0 END), 0) AS closed_net_commission,

        COALESCE(SUM(CASE WHEN t.commission_type = 'dynamic' THEN t.commission ELSE 0 END), 0) AS dynamic_commission,
        COALESCE(SUM(CASE WHEN t.commission_type = 'manual' THEN t.commission ELSE 0 END), 0) AS manual_commission
      FROM tasks t
      LEFT JOIN (
        SELECT task_id, SUM(amount) AS total_deductions
        FROM user_wallet_transactions
        WHERE transaction_type = 'credit'
        GROUP BY task_id
      ) ded ON ded.task_id = t.id
      WHERE t.created_at BETWEEN ? AND ?
        AND t.status NOT IN ('canceled', 'cancelled', 'refunded', 'refund')
    ";

    $fin = DB::selectOne($sql, [$dateFrom, $dateTo]);

    $totalRevenue          = (float) ($fin->total_revenue ?? 0);
    $closedRevenue         = (float) ($fin->closed_revenue ?? 0);
    $unclosedRevenue       = (float) ($fin->unclosed_revenue ?? 0);

    $totalCommission       = (float) ($fin->total_commission ?? 0);
    $closedCommission      = (float) ($fin->closed_commission ?? 0);
    $unclosedCommission    = (float) ($fin->unclosed_commission ?? 0);

    $closedDeductions      = (float) ($fin->closed_deductions ?? 0);
    $closedNetCommission   = (float) ($fin->closed_net_commission ?? 0);
    $unclosedNetCommission = $unclosedCommission;
    $totalNetCommission    = $closedNetCommission + $unclosedNetCommission;

    $totalDriverPayouts    = max(0, $totalRevenue - $totalCommission);
    $closedDriverPayouts   = max(0, $closedRevenue - $closedCommission);
    $unclosedDriverPayouts = max(0, $unclosedRevenue - $unclosedCommission);

    $closedCount           = (int) ($fin->closed_count ?? 0);
    $unclosedCount         = (int) ($fin->unclosed_count ?? 0);
    $totalCount            = (int) ($fin->total_count ?? 0);

    $avgTaskPrice          = $totalCount > 0 ? ($totalRevenue / $totalCount) : 0;
    $avgCommission         = $totalCount > 0 ? ($totalCommission / $totalCount) : 0;

    // Detailed payment methods with closed vs unclosed
    $revenueByPaymentMethod = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->whereNotIn('status', ['canceled', 'cancelled', 'refunded', 'refund'])
      ->select(
        'payment_method',
        DB::raw('COALESCE(SUM(CASE WHEN closed = true THEN total_price ELSE 0 END), 0) as closed_amount'),
        DB::raw('COALESCE(SUM(CASE WHEN closed = false OR closed IS NULL THEN total_price ELSE 0 END), 0) as unclosed_amount'),
        DB::raw('COALESCE(SUM(total_price), 0) as total_amount')
      )
      ->groupBy('payment_method')
      ->get()
      ->keyBy('payment_method')
      ->toArray();

    return [
      // Total (Closed + Unclosed)
      'total_revenue'            => round($totalRevenue, 2),
      'total_commission'         => round($totalCommission, 2),
      'total_net_commission'     => round($totalNetCommission, 2),
      'total_driver_payouts'     => round($totalDriverPayouts, 2),

      // Closed tasks (المحققة / المغلقة)
      'closed_revenue'           => round($closedRevenue, 2),
      'closed_commission'        => round($closedCommission, 2),
      'closed_deductions'        => round($closedDeductions, 2),
      'closed_net_commission'    => round($closedNetCommission, 2),
      'closed_driver_payouts'    => round($closedDriverPayouts, 2),
      'closed_count'             => $closedCount,

      // Unclosed tasks (المعلقة / غير المغلقة)
      'unclosed_revenue'         => round($unclosedRevenue, 2),
      'unclosed_commission'      => round($unclosedCommission, 2),
      'unclosed_net_commission'  => round($unclosedNetCommission, 2),
      'unclosed_driver_payouts'  => round($unclosedDriverPayouts, 2),
      'unclosed_count'           => $unclosedCount,

      // Backward compatibility aliases
      'net_commission'           => round($closedNetCommission, 2),
      'total_deductions'         => round($closedDeductions, 2),
      'driver_payouts'           => round($closedDriverPayouts, 2),
      'platform_income'          => round($closedDriverPayouts, 2),
      'average_task_price'       => round($avgTaskPrice, 2),
      'average_commission'       => round($avgCommission, 2),
      'dynamic_commission'       => round((float)($fin->dynamic_commission ?? 0), 2),
      'manual_commission'        => round((float)($fin->manual_commission ?? 0), 2),
      'revenue_by_payment_method'=> $revenueByPaymentMethod,
    ];
  }

  /**
   * Get operational fleet statistics
   */
  private function getOperationalStatistics($dateFrom, $dateTo)
  {
    // Active drivers in the period
    $activeDriversCount = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->whereNotNull('driver_id')
      ->distinct('driver_id')
      ->count('driver_id');

    // Total drivers in system
    $totalDriversCount = DB::table('drivers')->count();

    // Active customers in the period
    $activeCustomersCount = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->whereNotNull('customer_id')
      ->distinct('customer_id')
      ->count('customer_id');

    // Total customers in system
    $totalCustomersCount = DB::table('customers')->count();

    return [
      'active_drivers'   => $activeDriversCount,
      'total_drivers'    => $totalDriversCount,
      'active_customers' => $activeCustomersCount,
      'total_customers'  => $totalCustomersCount,
    ];
  }

  /**
   * Get top performers (Customers, Drivers, Teams)
   */
  private function getTopPerformers($dateFrom, $dateTo)
  {
    // Top 5 Customers by Revenue (showing closed vs unclosed)
    $topCustomers = DB::table('tasks')
      ->join('customers', 'customers.id', '=', 'tasks.customer_id')
      ->whereBetween('tasks.created_at', [$dateFrom, $dateTo])
      ->whereNotIn('tasks.status', ['canceled', 'cancelled', 'refunded', 'refund'])
      ->select(
        'customers.name',
        DB::raw('count(tasks.id) as tasks_count'),
        DB::raw('count(CASE WHEN tasks.closed = true THEN 1 END) as closed_tasks_count'),
        DB::raw('COALESCE(sum(CASE WHEN tasks.closed = true THEN tasks.total_price ELSE 0 END), 0) as closed_spent'),
        DB::raw('COALESCE(sum(CASE WHEN tasks.closed = false OR tasks.closed IS NULL THEN tasks.total_price ELSE 0 END), 0) as unclosed_spent'),
        DB::raw('COALESCE(sum(tasks.total_price), 0) as total_spent'),
        DB::raw('COALESCE(sum(tasks.commission), 0) as total_commission')
      )
      ->groupBy('customers.id', 'customers.name')
      ->orderByDesc('total_spent')
      ->limit(5)
      ->get();

    // Top 5 Drivers by Delivered Amount & Tasks
    $topDrivers = DB::table('tasks')
      ->join('drivers', 'drivers.id', '=', 'tasks.driver_id')
      ->whereBetween('tasks.created_at', [$dateFrom, $dateTo])
      ->whereNotIn('tasks.status', ['canceled', 'cancelled', 'refunded', 'refund'])
      ->select(
        'drivers.name',
        'drivers.phone',
        DB::raw('count(tasks.id) as total_tasks'),
        DB::raw('count(CASE WHEN tasks.closed = true THEN 1 END) as completed_tasks'),
        DB::raw('COALESCE(sum(CASE WHEN tasks.closed = true THEN tasks.total_price ELSE 0 END), 0) as closed_amount'),
        DB::raw('COALESCE(sum(CASE WHEN tasks.closed = false OR tasks.closed IS NULL THEN tasks.total_price ELSE 0 END), 0) as unclosed_amount'),
        DB::raw('COALESCE(sum(tasks.total_price), 0) as total_delivered_amount')
      )
      ->groupBy('drivers.id', 'drivers.name', 'drivers.phone')
      ->orderByDesc('total_tasks')
      ->limit(5)
      ->get();

    return [
      'customers' => $topCustomers,
      'drivers'   => $topDrivers,
    ];
  }

  /**
   * Get chart data for visualization (Closed vs Unclosed breakdown)
   */
  private function getChartData($dateFrom, $dateTo)
  {
    // Daily tasks count (closed vs unclosed)
    $dailyTasks = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('COUNT(*) as total_count'),
        DB::raw('COUNT(CASE WHEN closed = true THEN 1 END) as closed_count'),
        DB::raw("COUNT(CASE WHEN (closed = false OR closed IS NULL) AND status NOT IN ('canceled', 'cancelled', 'refunded', 'refund') THEN 1 END) as unclosed_count")
      )
      ->groupBy(DB::raw('DATE(created_at)'))
      ->orderBy('date')
      ->get()
      ->keyBy('date');

    // Daily revenue (closed vs unclosed)
    $dailyRevenue = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->whereNotIn('status', ['canceled', 'cancelled', 'refunded', 'refund'])
      ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('COALESCE(SUM(CASE WHEN closed = true THEN total_price ELSE 0 END), 0) as closed_revenue'),
        DB::raw('COALESCE(SUM(CASE WHEN closed = false OR closed IS NULL THEN total_price ELSE 0 END), 0) as unclosed_revenue'),
        DB::raw('COALESCE(SUM(total_price), 0) as total_revenue')
      )
      ->groupBy(DB::raw('DATE(created_at)'))
      ->orderBy('date')
      ->get()
      ->keyBy('date');

    // Daily commission (closed vs unclosed)
    $dailyCommission = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->whereNotIn('status', ['canceled', 'cancelled', 'refunded', 'refund'])
      ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('COALESCE(SUM(CASE WHEN closed = true THEN commission ELSE 0 END), 0) as closed_commission'),
        DB::raw('COALESCE(SUM(CASE WHEN closed = false OR closed IS NULL THEN commission ELSE 0 END), 0) as unclosed_commission'),
        DB::raw('COALESCE(SUM(commission), 0) as total_commission')
      )
      ->groupBy(DB::raw('DATE(created_at)'))
      ->orderBy('date')
      ->get()
      ->keyBy('date');

    // Fill missing dates with zeros
    $period = Carbon::parse($dateFrom)->daysUntil($dateTo);
    $dates = [];
    $tasksTotalData = [];
    $tasksClosedData = [];
    $tasksUnclosedData = [];

    $revenueClosedData = [];
    $revenueUnclosedData = [];
    $revenueTotalData = [];

    $commissionClosedData = [];
    $commissionUnclosedData = [];
    $commissionTotalData = [];

    foreach ($period as $date) {
      $dateStr = $date->format('Y-m-d');
      $dates[] = $date->format('M d');

      $t = $dailyTasks[$dateStr] ?? null;
      $tasksTotalData[] = (int) ($t->total_count ?? 0);
      $tasksClosedData[] = (int) ($t->closed_count ?? 0);
      $tasksUnclosedData[] = (int) ($t->unclosed_count ?? 0);

      $r = $dailyRevenue[$dateStr] ?? null;
      $revenueClosedData[] = round((float) ($r->closed_revenue ?? 0), 2);
      $revenueUnclosedData[] = round((float) ($r->unclosed_revenue ?? 0), 2);
      $revenueTotalData[] = round((float) ($r->total_revenue ?? 0), 2);

      $c = $dailyCommission[$dateStr] ?? null;
      $commissionClosedData[] = round((float) ($c->closed_commission ?? 0), 2);
      $commissionUnclosedData[] = round((float) ($c->unclosed_commission ?? 0), 2);
      $commissionTotalData[] = round((float) ($c->total_commission ?? 0), 2);
    }

    // Tasks status distribution for pie chart
    $statusDistribution = DB::table('tasks')
      ->whereBetween('created_at', [$dateFrom, $dateTo])
      ->select('status', DB::raw('count(*) as count'))
      ->groupBy('status')
      ->get()
      ->map(function ($item) {
        return [
          'label' => $this->getStatusLabel($item->status),
          'value' => (int) $item->count
        ];
      })
      ->toArray();

    return [
      'daily_tasks' => [
        'labels' => $dates,
        'total' => $tasksTotalData,
        'closed' => $tasksClosedData,
        'unclosed' => $tasksUnclosedData,
        'data' => $tasksTotalData
      ],
      'daily_revenue' => [
        'labels' => $dates,
        'closed' => $revenueClosedData,
        'unclosed' => $revenueUnclosedData,
        'total' => $revenueTotalData,
        'data' => $revenueTotalData
      ],
      'daily_commission' => [
        'labels' => $dates,
        'closed' => $commissionClosedData,
        'unclosed' => $commissionUnclosedData,
        'total' => $commissionTotalData,
        'data' => $commissionClosedData
      ],
      'status_distribution' => $statusDistribution
    ];
  }

  /**
   * Get localized status label
   */
  private function getStatusLabel($status)
  {
    $labels = [
      'in_progress' => __('In Progress'),
      'advertised' => __('Advertised'),
      'assign' => __('Assigned'),
      'accepted' => __('Accepted'),
      'start' => __('Started'),
      'completed' => __('Completed'),
      'canceled' => __('Canceled'),
      'cancelled' => __('Cancelled'),
      'refunded' => __('Refunded')
    ];

    return $labels[$status] ?? $status;
  }

  /**
   * Export statistics to Excel
   */
  public function export(Request $request)
  {
    $request->validate([
      'date_from' => 'nullable|date',
      'date_to' => 'nullable|date|after_or_equal:date_from',
      'format' => 'required|in:excel,pdf'
    ]);

    try {
      $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
      $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();

      $data = [
        'tasks' => $this->getTasksStatistics($dateFrom, $dateTo),
        'financial' => $this->getFinancialStatistics($dateFrom, $dateTo),
        'period' => [
          'from' => $dateFrom->format('Y-m-d'),
          'to' => $dateTo->format('Y-m-d')
        ]
      ];

      if ($request->format === 'excel') {
        return $this->exportToExcel($data);
      } else {
        return $this->exportToPdf($data);
      }
    } catch (\Exception $e) {
      return response()->json([
        'status' => 2,
        'error' => __('Failed to export statistics: ') . $e->getMessage()
      ]);
    }
  }

  /**
   * Export to Excel format
   */
  private function exportToExcel($data)
  {
    // Implementation for Excel export
    // This would use a library like PhpSpreadsheet
    return response()->json([
      'status' => 1,
      'message' => __('Excel export functionality to be implemented')
    ]);
  }

  /**
   * Export to PDF format
   */
  private function exportToPdf($data)
  {
    // Implementation for PDF export
    // This would use a library like DomPDF
    return response()->json([
      'status' => 1,
      'message' => __('PDF export functionality to be implemented')
    ]);
  }
}
