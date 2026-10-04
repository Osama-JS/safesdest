<?php

namespace App\Services;

use App\Models\User;
use App\Models\Task;
use App\Models\Driver;
use App\Models\Customer;
use App\Models\UserCommission;
use App\Models\UserWallet;
use App\Models\UserWalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BrokerReportService
{
    /**
     * Get all brokers in the platform
     */
    public function getBrokersList()
    {
        $brokerUserIds = collect();

        // 1. From user_commissions (Customer brokers)
        $customerBrokerIds = UserCommission::distinct('user_id')->pluck('user_id');
        $brokerUserIds = $brokerUserIds->merge($customerBrokerIds);

        // 2. From driver_brokers pivot (Driver brokers)
        if (\Illuminate\Support\Facades\Schema::hasTable('driver_brokers')) {
            $driverBrokerPivotIds = DB::table('driver_brokers')->distinct()->pluck('broker_id');
            $brokerUserIds = $brokerUserIds->merge($driverBrokerPivotIds);
        }

        // 3. From drivers.broker_id (Legacy driver brokers)
        if (\Illuminate\Support\Facades\Schema::hasColumn('drivers', 'broker_id')) {
            $driverLegacyBrokerIds = Driver::whereNotNull('broker_id')->distinct()->pluck('broker_id');
            $brokerUserIds = $brokerUserIds->merge($driverLegacyBrokerIds);
        }

        // 4. From task_brokers pivot (Task brokers)
        if (\Illuminate\Support\Facades\Schema::hasTable('task_brokers')) {
            $taskBrokerPivotIds = DB::table('task_brokers')->distinct()->pluck('broker_id');
            $brokerUserIds = $brokerUserIds->merge($taskBrokerPivotIds);
        }

        // 5. From tasks.broker_id (Legacy task brokers)
        if (\Illuminate\Support\Facades\Schema::hasColumn('tasks', 'broker_id')) {
            $taskLegacyBrokerIds = Task::whereNotNull('broker_id')->distinct()->pluck('broker_id');
            $brokerUserIds = $brokerUserIds->merge($taskLegacyBrokerIds);
        }

        // 6. From investment_contract_brokers pivot (Investor brokers)
        if (\Illuminate\Support\Facades\Schema::hasTable('investment_contract_brokers')) {
            $investorBrokerPivotIds = DB::table('investment_contract_brokers')->distinct()->pluck('broker_id');
            $brokerUserIds = $brokerUserIds->merge($investorBrokerPivotIds);
        }

        // 7. From investment_contracts.broker_id (Legacy investor brokers)
        if (\Illuminate\Support\Facades\Schema::hasTable('investment_contracts') && \Illuminate\Support\Facades\Schema::hasColumn('investment_contracts', 'broker_id')) {
            $investorLegacyBrokerIds = DB::table('investment_contracts')->whereNotNull('broker_id')->where('broker_id', '>', 0)->distinct()->pluck('broker_id');
            $brokerUserIds = $brokerUserIds->merge($investorLegacyBrokerIds);
        }

        // 8. From wallet transactions with broker commission descriptions
        $walletBrokerIds = DB::table('user_wallets')
            ->join('user_wallet_transactions', 'user_wallets.id', '=', 'user_wallet_transactions.user_wallet_id')
            ->where('user_wallet_transactions.transaction_type', 'credit')
            ->where(function ($q) {
                $q->where('user_wallet_transactions.description', 'LIKE', '%عمولة وساطة%')
                  ->orWhere('user_wallet_transactions.description', 'LIKE', '%Commission from Task:%')
                  ->orWhere('user_wallet_transactions.description', 'LIKE', '%عمولة وسيط%');
            })
            ->distinct()
            ->pluck('user_wallets.user_id');

        $brokerUserIds = $brokerUserIds->merge($walletBrokerIds)->filter()->unique();

        return User::whereIn('id', $brokerUserIds)
            ->select('id', 'name', 'phone', 'email')
            ->orderBy('name')
            ->get();
    }

    /**
     * Determine the commission source for a transaction
     * Returns: ['type' => 'customer'|'driver'|'task', 'type_label' => '...', 'source_name' => '...', 'source_id' => ...]
     */
    public function determineTransactionSource($tx, $brokerId)
    {
        $task = $tx->task;
        $desc = $tx->description ?? '';

        // Check if Investor linking
        if (str_contains($desc, 'تسويق المضارب') || str_contains($desc, 'مستثمر') || str_contains($desc, 'مضارب') || str_contains($desc, 'Investor')) {
            $investorName = '-';
            if (preg_match('/تسويق المضارب\s+([^ل]+?)\s+للمهمة/u', $desc, $matches)) {
                $investorName = trim($matches[1]);
            } elseif ($task && $task->investor_id) {
                $inv = \App\Models\User::find($task->investor_id);
                $investorName = $inv ? $inv->name : __('Investor #:id', ['id' => $task->investor_id]);
            }
            return [
                'type' => 'investor',
                'type_label' => __('Investor (Linking with Investors)'),
                'source_name' => $investorName !== '-' ? $investorName : __('Investor Linking'),
                'source_id' => $tx->task_id ?? null,
            ];
        }

        if ($task && !empty($task->investor_id)) {
            $isInvBroker = false;
            if (\Illuminate\Support\Facades\Schema::hasTable('investment_contract_brokers')) {
                $isInvBroker = DB::table('investment_contract_brokers')
                    ->join('investment_contracts', 'investment_contracts.id', '=', 'investment_contract_brokers.investment_contract_id')
                    ->where('investment_contracts.user_id', $task->investor_id)
                    ->where('investment_contract_brokers.broker_id', $brokerId)
                    ->exists();
            }
            if (!$isInvBroker && \Illuminate\Support\Facades\Schema::hasTable('investment_contracts')) {
                $isInvBroker = DB::table('investment_contracts')
                    ->where('user_id', $task->investor_id)
                    ->where('broker_id', $brokerId)
                    ->exists();
            }
            if ($isInvBroker) {
                $inv = \App\Models\User::find($task->investor_id);
                return [
                    'type' => 'investor',
                    'type_label' => __('Investor (Linking with Investors)'),
                    'source_name' => $inv ? $inv->name : __('Investor #:id', ['id' => $task->investor_id]),
                    'source_id' => $task->investor_id,
                ];
            }
        }

        // Check if customer linking
        if (str_contains($desc, 'Commission from Task:') || str_contains($desc, 'Customer:') || str_contains($desc, 'عمولة مستخدم') || str_contains($desc, 'عمولة عميل')) {
            $customerName = '-';
            $customerId = null;
            if ($task && $task->customer) {
                $customerName = $task->customer->name ?? $task->customer->company_name ?? '-';
                $customerId = $task->customer->id;
            } elseif (preg_match('/Customer:\s*(.+)$/i', $desc, $matches)) {
                $customerName = trim($matches[1]);
            }
            return [
                'type' => 'customer',
                'type_label' => __('Customer (Linking with Customers)'),
                'source_name' => $customerName,
                'source_id' => $customerId,
            ];
        }

        // Check Task direct link vs Driver link
        if ($task) {
            // 1. Is direct task broker?
            $isDirectTaskBroker = false;
            if ($task->relationLoaded('brokers') && $task->brokers->contains('id', $brokerId)) {
                $isDirectTaskBroker = true;
            } elseif (!empty($task->broker_id) && $task->broker_id == $brokerId) {
                $isDirectTaskBroker = true;
            }

            if ($isDirectTaskBroker) {
                return [
                    'type' => 'task',
                    'type_label' => __('Task (Direct Task Link)'),
                    'source_name' => __('Task #:id', ['id' => $task->id]),
                    'source_id' => $task->id,
                ];
            }

            // 2. Is driver broker?
            $isDriverBroker = false;
            $driver = $task->driver;
            if ($driver) {
                if ($driver->relationLoaded('brokers') && $driver->brokers->contains('id', $brokerId)) {
                    $isDriverBroker = true;
                } elseif (!empty($driver->broker_id) && $driver->broker_id == $brokerId) {
                    $isDriverBroker = true;
                }
            }

            if ($isDriverBroker && $driver) {
                return [
                    'type' => 'driver',
                    'type_label' => __('Driver (Linking with Drivers)'),
                    'source_name' => $driver->name . ($driver->phone ? ' (' . $driver->phone . ')' : ''),
                    'source_id' => $driver->id,
                ];
            }

            // 3. User commission with task customer?
            if ($task->customer_id) {
                $hasCustCommission = UserCommission::where('user_id', $brokerId)
                    ->where('customer_id', $task->customer_id)
                    ->exists();
                if ($hasCustCommission) {
                    return [
                        'type' => 'customer',
                        'type_label' => __('Customer (Linking with Customers)'),
                        'source_name' => optional($task->customer)->name ?? (optional($task->customer)->company_name ?? __('Customer #:id', ['id' => $task->customer_id])),
                        'source_id' => $task->customer_id,
                    ];
                }
            }
        }

        // Fallback by description analysis
        if (str_contains($desc, 'وساطة شاحنات') || str_contains($desc, 'سائق') || str_contains($desc, 'شاحنة')) {
            $driverName = ($task && $task->driver) ? $task->driver->name : __('Trucks Brokerage');
            return [
                'type' => 'driver',
                'type_label' => __('Driver (Linking with Drivers)'),
                'source_name' => $driverName,
                'source_id' => ($task && $task->driver) ? $task->driver->id : null,
            ];
        }

        if (str_contains($desc, 'مهمة') || str_contains($desc, 'Task')) {
            return [
                'type' => 'task',
                'type_label' => __('Task (Direct Task Link)'),
                'source_name' => $task ? __('Task #:id', ['id' => $task->id]) : __('Task'),
                'source_id' => $task ? $task->id : null,
            ];
        }

        return [
            'type' => 'task',
            'type_label' => __('Task (Direct Task Link)'),
            'source_name' => $task ? __('Task #:id', ['id' => $task->id]) : '-',
            'source_id' => $task ? $task->id : null,
        ];
    }

    /**
     * Normalize selected commission sources
     * Returns array of sources or empty array for all
     */
    public function normalizeSources(array $filters): array
    {
        $raw = $filters['commission_sources'] ?? ($filters['commission_source'] ?? ['all']);
        if (!is_array($raw)) {
            $raw = [$raw];
        }

        if (empty($raw) || in_array('all', $raw)) {
            return []; // Empty means all sources
        }

        return array_values(array_intersect($raw, ['customer', 'driver', 'task', 'investor']));
    }

    /**
     * Generate detailed wallet transactions report
     */
    public function generateWalletTransactionsReport(array $filters, bool $preview = false)
    {
        $brokerIds = $filters['broker_ids'] ?? [];
        if (!is_array($brokerIds)) {
            $brokerIds = array_filter([$brokerIds]);
        }

        $selectedSources = $this->normalizeSources($filters);

        // Query user wallet transactions
        $query = UserWalletTransaction::with([
            'userWallet.user',
            'task.customer',
            'task.driver.brokers',
            'task.brokers',
            'task.ad'
        ])
        ->where('transaction_type', 'credit')
        ->where(function ($q) {
            $q->whereNotNull('task_id')
              ->orWhere('description', 'LIKE', '%عمولة%')
              ->orWhere('description', 'LIKE', '%Commission%');
        });

        // Filter by broker IDs (via user_wallets)
        if (!empty($brokerIds)) {
            $query->whereHas('userWallet', function ($q) use ($brokerIds) {
                $q->whereIn('user_id', $brokerIds);
            });
        } else {
            // Only brokers wallets
            $allBrokerIds = $this->getBrokersList()->pluck('id')->toArray();
            $query->whereHas('userWallet', function ($q) use ($allBrokerIds) {
                $q->whereIn('user_id', $allBrokerIds);
            });
        }

        // Apply date range
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        $allTransactions = $query->orderBy('created_at', 'desc')->get();

        $processedTransactions = collect();
        $totalCommissions = 0;
        $customerCommissions = 0;
        $driverCommissions = 0;
        $taskCommissions = 0;
        $investorCommissions = 0;
        $distinctBrokers = [];

        foreach ($allTransactions as $tx) {
            $broker = $tx->userWallet ? $tx->userWallet->user : null;
            if (!$broker) {
                continue;
            }

            $sourceInfo = $this->determineTransactionSource($tx, $broker->id);

            // Filter by sources if specified
            if (!empty($selectedSources) && !in_array($sourceInfo['type'], $selectedSources)) {
                continue;
            }

            $amount = (float) $tx->amount;
            $totalCommissions += $amount;

            if ($sourceInfo['type'] === 'customer') {
                $customerCommissions += $amount;
            } elseif ($sourceInfo['type'] === 'driver') {
                $driverCommissions += $amount;
            } elseif ($sourceInfo['type'] === 'task') {
                $taskCommissions += $amount;
            } elseif ($sourceInfo['type'] === 'investor') {
                $investorCommissions += $amount;
            }

            $distinctBrokers[$broker->id] = true;

            $item = [
                'id' => $tx->id,
                'sequence' => $tx->sequence ?? $tx->id,
                'broker_id' => $broker->id,
                'broker_name' => $broker->name,
                'broker_phone' => $broker->phone ?? '-',
                'task_id' => $tx->task_id ?? '-',
                'source_type' => $sourceInfo['type'],
                'source_type_label' => $sourceInfo['type_label'],
                'source_name' => $sourceInfo['source_name'],
                'amount' => $amount,
                'description' => $tx->description,
                'created_at' => $tx->created_at ? $tx->created_at->format('Y-m-d H:i') : '-',
                'created_at_raw' => $tx->created_at,
            ];

            $processedTransactions->push($item);
        }

        $summary = [
            'total_commissions' => $totalCommissions,
            'customer_commissions_total' => $customerCommissions,
            'driver_commissions_total' => $driverCommissions,
            'task_commissions_total' => $taskCommissions,
            'investor_commissions_total' => $investorCommissions,
            'transactions_count' => $processedTransactions->count(),
            'brokers_count' => count($distinctBrokers),
            'average_commission' => $processedTransactions->count() > 0 ? ($totalCommissions / $processedTransactions->count()) : 0,
        ];

        // Slice for preview if preview mode requested
        $displayTransactions = $preview ? $processedTransactions->take(150)->values() : $processedTransactions->values();

        return [
            'mode' => 'transactions',
            'transactions' => $displayTransactions,
            'summary' => $summary,
            'filters_applied' => $this->formatFiltersApplied($filters, count($distinctBrokers)),
            'generated_at' => now(),
            'generated_by' => Auth::check() ? Auth::user()->name : __('System'),
        ];
    }

    /**
     * Generate aggregated totals report per broker
     */
    public function generateAggregatedBrokersReport(array $filters)
    {
        $brokerIds = $filters['broker_ids'] ?? [];
        if (!is_array($brokerIds)) {
            $brokerIds = array_filter([$brokerIds]);
        }

        $allBrokers = $this->getBrokersList();
        if (!empty($brokerIds)) {
            $allBrokers = $allBrokers->whereIn('id', $brokerIds);
        }

        $selectedSources = $this->normalizeSources($filters);

        // Query transactions for these brokers
        $txQuery = UserWalletTransaction::with([
            'userWallet',
            'task.customer',
            'task.driver.brokers',
            'task.brokers',
        ])
        ->where('transaction_type', 'credit')
        ->where(function ($q) {
            $q->whereNotNull('task_id')
              ->orWhere('description', 'LIKE', '%عمولة%')
              ->orWhere('description', 'LIKE', '%Commission%');
        })
        ->whereHas('userWallet', function ($q) use ($allBrokers) {
            $q->whereIn('user_id', $allBrokers->pluck('id'));
        });

        if (!empty($filters['date_from'])) {
            $txQuery->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $txQuery->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        $transactions = $txQuery->get();

        // Group transactions by broker_id
        $txByBroker = [];
        foreach ($transactions as $tx) {
            $bId = $tx->userWallet ? $tx->userWallet->user_id : null;
            if (!$bId) continue;

            $sourceInfo = $this->determineTransactionSource($tx, $bId);

            if (!empty($selectedSources) && !in_array($sourceInfo['type'], $selectedSources)) {
                continue;
            }

            if (!isset($txByBroker[$bId])) {
                $txByBroker[$bId] = [
                    'customer' => 0.0,
                    'driver' => 0.0,
                    'task' => 0.0,
                    'investor' => 0.0,
                    'total' => 0.0,
                    'count' => 0,
                ];
            }

            $amount = (float) $tx->amount;
            $txByBroker[$bId][$sourceInfo['type']] += $amount;
            $txByBroker[$bId]['total'] += $amount;
            $txByBroker[$bId]['count']++;
        }

        // Build broker summary rows
        $brokerRows = collect();
        $grandTotal = 0;
        $totalCustomer = 0;
        $totalDriver = 0;
        $totalTask = 0;
        $totalInvestor = 0;
        $totalTxCount = 0;

        foreach ($allBrokers as $broker) {
            $data = $txByBroker[$broker->id] ?? [
                'customer' => 0.0,
                'driver' => 0.0,
                'task' => 0.0,
                'investor' => 0.0,
                'total' => 0.0,
                'count' => 0,
            ];

            // If a broker has zero commissions from the selected sources, exclude if specific sources were filtered
            if ($data['total'] <= 0 && !empty($selectedSources)) {
                continue;
            }

            $wallet = $broker->userWallet;
            $walletBalance = $wallet ? (float) ($wallet->credit - $wallet->debit) : 0.0;

            $grandTotal += $data['total'];
            $totalCustomer += $data['customer'];
            $totalDriver += $data['driver'];
            $totalTask += $data['task'];
            $totalInvestor += $data['investor'];
            $totalTxCount += $data['count'];

            $brokerRows->push([
                'broker_id' => $broker->id,
                'broker_name' => $broker->name,
                'broker_phone' => $broker->phone ?? '-',
                'broker_email' => $broker->email ?? '-',
                'customer_commissions' => $data['customer'],
                'driver_commissions' => $data['driver'],
                'task_commissions' => $data['task'],
                'investor_commissions' => $data['investor'],
                'total_commissions' => $data['total'],
                'transactions_count' => $data['count'],
                'wallet_balance' => $walletBalance,
            ]);
        }

        // Sort by total_commissions descending
        $brokerRows = $brokerRows->sortByDesc('total_commissions')->values();

        $summary = [
            'grand_total_commissions' => $grandTotal,
            'total_customer_commissions' => $totalCustomer,
            'total_driver_commissions' => $totalDriver,
            'total_task_commissions' => $totalTask,
            'total_investor_commissions' => $totalInvestor,
            'total_transactions_count' => $totalTxCount,
            'total_brokers' => $brokerRows->count(),
            'average_per_broker' => $brokerRows->count() > 0 ? ($grandTotal / $brokerRows->count()) : 0,
        ];

        return [
            'mode' => 'aggregated',
            'brokers' => $brokerRows,
            'summary' => $summary,
            'filters_applied' => $this->formatFiltersApplied($filters, $brokerRows->count()),
            'generated_at' => now(),
            'generated_by' => Auth::check() ? Auth::user()->name : __('System'),
        ];
    }

    /**
     * Format filter strings for header display
     */
    private function formatFiltersApplied(array $filters, int $brokersCount)
    {
        $brokerText = __('All Brokers');
        if (!empty($filters['broker_ids'])) {
            $ids = is_array($filters['broker_ids']) ? $filters['broker_ids'] : [$filters['broker_ids']];
            $names = User::whereIn('id', $ids)->pluck('name')->toArray();
            $brokerText = !empty($names) ? implode('، ', $names) : __(':count Brokers', ['count' => $brokersCount]);
        }

        $dateRangeText = __('All Periods');
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $dateRangeText = __('From :from to :to', ['from' => $filters['date_from'], 'to' => $filters['date_to']]);
        } elseif (!empty($filters['date_from'])) {
            $dateRangeText = __('From :from', ['from' => $filters['date_from']]);
        } elseif (!empty($filters['date_to'])) {
            $dateRangeText = __('Up to :to', ['to' => $filters['date_to']]);
        }

        $selectedSources = $this->normalizeSources($filters);
        $sourceMap = [
            'customer' => __('Customer Linking Commissions'),
            'driver' => __('Driver Linking Commissions'),
            'task' => __('Direct Task Linking Commissions'),
        ];

        if (empty($selectedSources) || count($selectedSources) === 3) {
            $sourceText = __('All Sources');
        } else {
            $labels = [];
            foreach ($selectedSources as $s) {
                if (isset($sourceMap[$s])) {
                    $labels[] = $sourceMap[$s];
                }
            }
            $sourceText = implode(' + ', $labels);
        }

        return [
            'brokers' => $brokerText,
            'date_range' => $dateRangeText,
            'source' => $sourceText,
            'mode' => ($filters['report_mode'] ?? 'transactions') === 'aggregated' ? __('Brokers Commissions Aggregates (Summary)') : __('Recorded Wallet Transactions (Detailed)'),
        ];
    }
}
