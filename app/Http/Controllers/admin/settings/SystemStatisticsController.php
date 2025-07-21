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

      // Get tasks statistics
      $tasksStats = $this->getTasksStatistics($dateFrom, $dateTo);

      // Get financial statistics
      $financialStats = $this->getFinancialStatistics($dateFrom, $dateTo);

      // Get chart data
      $chartData = $this->getChartData($dateFrom, $dateTo);

      return response()->json([
        'status' => 1,
        'data' => [
          'tasks' => $tasksStats,
          'financial' => $financialStats,
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
   * Get tasks statistics
   */
  private function getTasksStatistics($dateFrom, $dateTo)
  {
    $tasksQuery = Task::whereBetween('created_at', [$dateFrom, $dateTo]);

    $totalTasks = (clone $tasksQuery)->count();

    $completedTasks = (clone $tasksQuery)
      ->where('status', 'completed')
      ->count();

    $cancelledTasks = (clone $tasksQuery)
      ->where('status', 'canceled')
      ->count();

    $inProgressTasks = (clone $tasksQuery)
      ->whereNotIn('status', ['completed', 'canceled'])
      ->count();

    $closedTasks = (clone $tasksQuery)
      ->where('status', 'completed')
      ->where('closed', true)
      ->count();

    // Tasks by status
    $tasksByStatus = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->select('status', DB::raw('count(*) as count'))
      ->groupBy('status')
      ->pluck('count', 'status')
      ->toArray();

    // Tasks by payment status
    $tasksByPaymentStatus = Task::whereBetween('created_at', [$dateFrom, $dateTo])
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
      'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
      'tasks_by_status' => $tasksByStatus,
      'tasks_by_payment_status' => $tasksByPaymentStatus
    ];
  }

  /**
   * Get financial statistics
   */
  private function getFinancialStatistics($dateFrom, $dateTo)
  {
    // Total revenue from completed tasks
    $totalRevenue = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->where('status', 'completed')->where('closed', true)
      ->sum('total_price');

    // Total commission from completed tasks
    $totalCommission = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->where('status', 'completed')->where('closed', true)
      ->sum('commission');

    // Platform income (total_price - commission)
    $platformIncome = $totalRevenue - $totalCommission;

    // Average task price
    $averageTaskPrice = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->where('status', 'completed')
      ->avg('total_price') ?: 0;

    // Average commission
    $averageCommission = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->where('status', 'completed')
      ->avg('commission') ?: 0;

    // Revenue by payment method
    $revenueByPaymentMethod = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->where('status', 'completed')
      ->select('payment_method', DB::raw('sum(total_price) as total'))
      ->groupBy('payment_method')
      ->pluck('total', 'payment_method')
      ->toArray();

    // Commission by commission type
    $commissionByType = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->where('status', 'completed')
      ->select('commission_type', DB::raw('sum(commission) as total'))
      ->groupBy('commission_type')
      ->pluck('total', 'commission_type')
      ->toArray();

    return [
      'total_revenue' => round($totalRevenue, 2),
      'total_commission' => round($totalCommission, 2),
      'platform_income' => round($platformIncome, 2),
      'average_task_price' => round($averageTaskPrice, 2),
      'average_commission' => round($averageCommission, 2),
      'revenue_by_payment_method' => $revenueByPaymentMethod,
      'commission_by_type' => $commissionByType
    ];
  }

  /**
   * Get chart data for visualization
   */
  private function getChartData($dateFrom, $dateTo)
  {
    // Daily tasks count
    $dailyTasks = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
      ->groupBy(DB::raw('DATE(created_at)'))
      ->orderBy('date')
      ->get()
      ->pluck('count', 'date')
      ->toArray();

    // Daily revenue
    $dailyRevenue = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->where('status', 'completed')
      ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(total_price) as revenue'))
      ->groupBy(DB::raw('DATE(created_at)'))
      ->orderBy('date')
      ->get()
      ->pluck('revenue', 'date')
      ->toArray();

    // Daily commission
    $dailyCommission = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->where('status', 'completed')
      ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(commission) as commission'))
      ->groupBy(DB::raw('DATE(created_at)'))
      ->orderBy('date')
      ->get()
      ->pluck('commission', 'date')
      ->toArray();

    // Fill missing dates with zeros
    $period = Carbon::parse($dateFrom)->daysUntil($dateTo);
    $dates = [];
    $tasksData = [];
    $revenueData = [];
    $commissionData = [];

    foreach ($period as $date) {
      $dateStr = $date->format('Y-m-d');
      $dates[] = $date->format('M d');
      $tasksData[] = $dailyTasks[$dateStr] ?? 0;
      $revenueData[] = round($dailyRevenue[$dateStr] ?? 0, 2);
      $commissionData[] = round($dailyCommission[$dateStr] ?? 0, 2);
    }

    // Tasks status distribution for pie chart
    $statusDistribution = Task::whereBetween('created_at', [$dateFrom, $dateTo])
      ->select('status', DB::raw('count(*) as count'))
      ->groupBy('status')
      ->get()
      ->map(function ($item) {
        return [
          'label' => $this->getStatusLabel($item->status),
          'value' => $item->count
        ];
      })
      ->toArray();

    return [
      'daily_tasks' => [
        'labels' => $dates,
        'data' => $tasksData
      ],
      'daily_revenue' => [
        'labels' => $dates,
        'data' => $revenueData
      ],
      'daily_commission' => [
        'labels' => $dates,
        'data' => $commissionData
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
      'canceled' => __('Canceled')
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
