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
   * Get platform commissions data for DataTable (Server-Side Paginated)
   */
  public function data(Request $request)
  {
    $query = Task::query()
      ->with([
        'customer:id,name',
        'driver:id,name',
        'team:id,name',
        'pickup:id,task_id,address',
        'delivery:id,task_id,address'
      ])
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

    // Search filter
    $searchValue = $request->input('search.value');
    if (!empty($searchValue)) {
      $query->where(function($q) use ($searchValue) {
        if (is_numeric($searchValue)) {
          $q->where('id', $searchValue);
        }
        $q->orWhereHas('customer', function($sub) use ($searchValue) {
          $sub->where('name', 'ILIKE', "%{$searchValue}%");
        })->orWhereHas('driver', function($sub) use ($searchValue) {
          $sub->where('name', 'ILIKE', "%{$searchValue}%");
        })->orWhereHas('team', function($sub) use ($searchValue) {
          $sub->where('name', 'ILIKE', "%{$searchValue}%");
        });
      });
    }

    $totalFiltered = (clone $query)->count();

    // Order mappings
    $columns = [
      0 => 'id',
      1 => 'customer_id',
      2 => 'driver_id',
      3 => 'team_id',
      4 => 'id',
      5 => 'total_price',
      6 => 'commission',
      7 => 'commission',
      8 => 'commission_type',
      9 => 'payment_paid',
      10 => 'status',
      11 => 'completed_at',
    ];

    $orderColIdx = $request->input('order.0.column', 11);
    $orderDir    = $request->input('order.0.dir', 'desc');
    $orderCol    = $columns[$orderColIdx] ?? 'completed_at';
    $query->orderBy($orderCol, $orderDir);

    // Server-side pagination
    $start  = (int) $request->input('start', 0);
    $length = (int) $request->input('length', 25);
    if ($length <= 0) {
      $length = 25;
    }

    $tasks = $query->skip($start)->take($length)->get();

    $data = $tasks->map(function ($task) {
      return [
        'id' => $task->id,
        'customer' => $task->customer ? $task->customer->name : 'Admin',
        'driver' => $task->driver ? $task->driver->name : 'N/A',
        'team' => $task->team ? $task->team->name : 'N/A',
        'pickup_address' => $task->pickup ? $task->pickup->address : 'N/A',
        'delivery_address' => $task->delivery ? $task->delivery->address : 'N/A',
        'total_price' => number_format((float) $task->total_price, 2),
        'commission' => number_format((float) $task->commission, 2),
        'commission_type' => ucfirst($task->commission_type ?? ''),
        'payment_status' => $task->payment_paid,
        'payment_method' => ucfirst($task->payment_method ?? ''),
        'task_status' => $task->status,
        'is_closed' => (bool) $task->closed,
        'completed_at' => $task->completed_at ? (is_string($task->completed_at) ? $task->completed_at : $task->completed_at->format('Y-m-d H:i')) : 'N/A',
        'closed_at' => $task->closed_at ? $task->closed_at->format('Y-m-d H:i') : 'N/A',
        'total_given_commission' => (float) ($task->total_given_commission ?? 0),
        'net_commission' => number_format(max(0, (float) $task->commission - ((float) ($task->total_given_commission ?? 0))), 2),
      ];
    });

    $recordsTotal = Task::where('commission', '>', 0)->count();

    return response()->json([
      'draw' => intval($request->input('draw', 1)),
      'recordsTotal' => $recordsTotal,
      'recordsFiltered' => $totalFiltered,
      'data' => $data
    ]);
  }

  /**
   * Get detailed deductions breakdown for a task's commission
   */
  public function taskDeductions($id)
  {
    $task = Task::with([
      'customer',
      'driver',
      'team',
      'userWalletTransactions' => function ($q) {
        $q->where('transaction_type', 'credit')
          ->with(['userWallet.user', 'user']);
      }
    ])->findOrFail($id);

    $originalCommission = (float) $task->commission;

    // Load related investor if applicable
    $investor = null;
    if ($task->investor_id) {
      $investor = \App\Models\User::find($task->investor_id);
    }

    $deductions = [];
    $totalDeductions = 0;

    foreach ($task->userWalletTransactions as $tx) {
      $amount = (float) $tx->amount;
      $totalDeductions += $amount;

      // Identify beneficiary user
      $beneficiary = $tx->userWallet ? $tx->userWallet->user : ($tx->user ?? null);
      $beneficiaryId = $beneficiary ? $beneficiary->id : null;
      $beneficiaryName = $beneficiary ? $beneficiary->name : 'N/A';
      $beneficiaryPhone = $beneficiary ? $beneficiary->phone : null;

      $desc = $tx->description ?? '';

      // Determine commission type accurately
      $typeKey = 'task_broker';
      $typeLabel = 'وساطة مهمة مباشرة';
      $badgeClass = 'bg-label-info';
      $sourceDetails = 'مرتبط بالمهمة #' . $task->id;

      // 1. Investor Broker or Investor profit
      if (
        str_contains($desc, 'تسويق المضارب') ||
        str_contains($desc, 'وسيط المستثمر') ||
        str_contains($desc, 'وسيط المضارب') ||
        str_contains($desc, 'استثمار')
      ) {
        $typeKey = 'investor_broker';
        $typeLabel = 'وسيط مستثمر / مضارب';
        $badgeClass = 'bg-label-primary';
        $sourceDetails = $investor ? "تسويق المضارب: {$investor->name}" : 'وساطة عقد استثمار';
      } elseif (
        str_contains($desc, 'عمولة المضارب') ||
        str_contains($desc, 'أرباح المضارب') ||
        ($investor && $beneficiaryId == $investor->id)
      ) {
        $typeKey = 'investor_profit';
        $typeLabel = 'حصة المستثمر / المضارب';
        $badgeClass = 'bg-label-dark';
        $sourceDetails = 'عقد استثمار المهمة';
      }
      // 2. Customer Broker / User Commission
      elseif (
        str_contains($desc, 'Customer:') ||
        str_contains($desc, 'Commission from Task:') ||
        str_contains($desc, 'عمولة عميل') ||
        str_contains($desc, 'عمولة مستخدم')
      ) {
        $typeKey = 'customer_broker';
        $typeLabel = 'وسيط عميل (تسويق عميل)';
        $badgeClass = 'bg-label-success';
        $sourceDetails = $task->customer ? "العميل: {$task->customer->name}" : 'وساطة حساب عميل';
      }
      // 3. Driver Broker
      elseif (
        str_contains($desc, 'وساطة شاحنات') ||
        str_contains($desc, 'سائق') ||
        str_contains($desc, 'مندوب') ||
        ($task->driver && ($task->driver->broker_id == $beneficiaryId || ($task->driver->relationLoaded('brokers') && $task->driver->brokers->contains('id', $beneficiaryId))))
      ) {
        $typeKey = 'driver_broker';
        $typeLabel = 'وسيط سائق / شاحنة';
        $badgeClass = 'bg-label-warning';
        $sourceDetails = $task->driver ? "السائق: {$task->driver->name}" : 'وساطة سائق وشاحنة';
      }
      // 4. Team Broker
      elseif (
        str_contains($desc, 'فريق') ||
        str_contains($desc, 'Team') ||
        ($tx->team_id || ($task->team_id && $tx->team_id == $task->team_id))
      ) {
        $typeKey = 'team_broker';
        $typeLabel = 'وسيط فريق عمل';
        $badgeClass = 'bg-label-secondary';
        $sourceDetails = $task->team ? "الفريق: {$task->team->name}" : 'وساطة فريق عمل';
      }

      $deductions[] = [
        'id'                => $tx->id,
        'amount'            => $amount,
        'amount_formatted'  => number_format($amount, 2),
        'beneficiary_name'  => $beneficiaryName,
        'beneficiary_phone' => $beneficiaryPhone,
        'beneficiary_id'    => $beneficiaryId,
        'type_key'          => $typeKey,
        'type_label'        => $typeLabel,
        'badge_class'       => $badgeClass,
        'source_details'    => $sourceDetails,
        'description'       => $desc,
        'created_at'        => $tx->created_at ? $tx->created_at->format('Y-m-d H:i') : '-',
      ];
    }

    $netPlatformCommission = max(0, $originalCommission - $totalDeductions);

    return response()->json([
      'success' => true,
      'data' => [
        'task' => [
          'id'                   => $task->id,
          'customer_name'        => $task->customer ? $task->customer->name : 'N/A',
          'driver_name'          => $task->driver ? $task->driver->name : 'N/A',
          'team_name'            => $task->team ? $task->team->name : 'N/A',
          'total_price'          => number_format((float) $task->total_price, 2),
          'commission'           => number_format($originalCommission, 2),
          'raw_commission'       => $originalCommission,
          'total_deductions'     => number_format($totalDeductions, 2),
          'raw_total_deductions' => $totalDeductions,
          'net_commission'       => number_format($netPlatformCommission, 2),
          'raw_net_commission'   => $netPlatformCommission,
          'has_deductions'       => count($deductions) > 0,
        ],
        'deductions' => $deductions,
      ]
    ]);
  }

  /**
   * Get platform wallet statistics (Ultra Optimized SQL Aggregation)
   */
  public function statistics(Request $request)
  {
    // Build optimized base subquery matching the exact filters
    $filterSql = "WHERE commission > 0";
    $bindings = [];

    if ($request->filled('task_status')) {
      $filterSql .= " AND status = ?";
      $bindings[] = $request->task_status;
    } else {
      $filterSql .= " AND status NOT IN ('canceled', 'refund')";
    }

    if ($request->filled('is_closed')) {
      $filterSql .= " AND closed = ?";
      $bindings[] = (int) $request->is_closed;
    }

    if ($request->filled('date_from')) {
      $filterSql .= " AND completed_at >= ?";
      $bindings[] = $request->date_from . ' 00:00:00';
    }

    if ($request->filled('date_to')) {
      $filterSql .= " AND completed_at <= ?";
      $bindings[] = $request->date_to . ' 23:59:59';
    }

    if ($request->filled('payment_status')) {
      $filterSql .= " AND payment_paid = ?";
      $bindings[] = $request->payment_status;
    }

    if ($request->filled('commission_type')) {
      $filterSql .= " AND commission_type = ?";
      $bindings[] = $request->commission_type;
    }

    // Single-pass aggregated query computing all totals including net commissions in SQL
    $statsSql = "
      SELECT
        COUNT(*) AS total_tasks,
        COALESCE(SUM(commission), 0) AS total_commissions,
        COALESCE(SUM(CASE WHEN payment_paid IN ('all', 'just_commission') THEN commission ELSE 0 END), 0) AS paid_commissions,
        COALESCE(SUM(CASE WHEN payment_paid = 'pending' THEN commission ELSE 0 END), 0) AS pending_commissions,
        COUNT(CASE WHEN payment_paid IN ('all', 'just_commission') THEN 1 END) AS paid_tasks,
        COUNT(CASE WHEN payment_paid = 'pending' THEN 1 END) AS pending_tasks,
        COALESCE(SUM(CASE WHEN commission_type = 'dynamic' THEN commission ELSE 0 END), 0) AS dynamic_commissions,
        COALESCE(SUM(CASE WHEN commission_type = 'manual' THEN commission ELSE 0 END), 0) AS manual_commissions,
        COALESCE(SUM(GREATEST(0, commission - COALESCE(given_comm.total_given, 0))), 0) AS total_net_commissions,
        COALESCE(SUM(CASE WHEN payment_paid IN ('all', 'just_commission') THEN GREATEST(0, commission - COALESCE(given_comm.total_given, 0)) ELSE 0 END), 0) AS paid_net_commissions,
        COALESCE(SUM(CASE WHEN payment_paid = 'pending' THEN GREATEST(0, commission - COALESCE(given_comm.total_given, 0)) ELSE 0 END), 0) AS pending_net_commissions
      FROM tasks t
      LEFT JOIN (
        SELECT task_id, SUM(amount) AS total_given
        FROM user_wallet_transactions
        WHERE transaction_type = 'credit'
        GROUP BY task_id
      ) given_comm ON given_comm.task_id = t.id
      {$filterSql}
    ";

    $statsResult = DB::selectOne($statsSql, $bindings);

    $isNetFilter = $request->filled('net_commission_filter') && $request->net_commission_filter == 1;

    $totalCommissions   = (float) ($statsResult->total_commissions ?? 0);
    $paidCommissions    = (float) ($statsResult->paid_commissions ?? 0);
    $pendingCommissions = (float) ($statsResult->pending_commissions ?? 0);

    $totalNetCommissions   = (float) ($statsResult->total_net_commissions ?? 0);
    $paidNetCommissions    = (float) ($statsResult->paid_net_commissions ?? 0);
    $pendingNetCommissions = (float) ($statsResult->pending_net_commissions ?? 0);

    $finalTotalCommission = $isNetFilter ? $totalNetCommissions : $totalCommissions;
    $finalPaidCommission  = $isNetFilter ? $paidNetCommissions : $paidCommissions;
    $finalPendingCommission = $isNetFilter ? $pendingNetCommissions : $pendingCommissions;

    $collectionRate = $finalTotalCommission > 0
      ? round(($finalPaidCommission / $finalTotalCommission) * 100, 2)
      : 0;

    $responseData = [
      'total_commissions'   => $finalTotalCommission,
      'paid_commissions'    => $finalPaidCommission,
      'pending_commissions' => $finalPendingCommission,
      'total_tasks'         => (int) ($statsResult->total_tasks ?? 0),
      'paid_tasks'          => (int) ($statsResult->paid_tasks ?? 0),
      'pending_tasks'       => (int) ($statsResult->pending_tasks ?? 0),
      'dynamic_commissions' => (float) ($statsResult->dynamic_commissions ?? 0),
      'manual_commissions'  => (float) ($statsResult->manual_commissions ?? 0),
      'monthly_stats'       => [],
      'collection_rate'     => $collectionRate
    ];

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
