<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Task;
use App\Models\Payments;
use App\Models\UserWalletTransaction;
use App\Models\Wallet_Transaction;
use App\Models\InvestorWalletTransaction;
use App\Exports\StatisticalReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class StatisticalReportController extends Controller
{
    public function index()
    {
        $customers = Customer::select('id', 'name', 'company_name')->get();
        return view('admin.reports.statistical', compact('customers'));
    }

    public function previewReport(Request $request)
    {
        try {
            $reportData = $this->getReportData($request);
            return response()->json([
                'success' => true,
                'data' => $reportData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateReport(Request $request)
    {
        $reportData = $this->getReportData($request);
        $filename = 'statistical_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new StatisticalReportExport($reportData), $filename);
    }

    private function getReportData(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'exists:customers,id'
        ]);

        $dateFrom = Carbon::parse($request->date_from)->startOfDay();
        $dateTo = Carbon::parse($request->date_to)->endOfDay();
        $customerIds = $request->customer_ids ?? [];
        $showCurrency = $request->has('show_currency');
        $calcNetCommission = $request->has('calc_net_commission');

        $period = CarbonPeriod::create($dateFrom, $dateTo);
        $days = [];
        foreach ($period as $date) {
            $days[] = $date->format('Y-m-d');
        }

        $reportData = [
            'days' => $days,
            'show_currency' => $showCurrency,
            'calc_net_commission' => $calcNetCommission,
            'filters_applied' => [
                'customers' => empty($customerIds) ? 'الجميع' : Customer::whereIn('id', $customerIds)->pluck('name')->implode('، ')
            ],
            'activity' => [
                'shipments' => array_fill_keys($days, 0),
                'active_customers' => array_fill_keys($days, []),
                'revenue' => array_fill_keys($days, 0),
                'carrier_cost' => array_fill_keys($days, 0),
                'net_commission' => array_fill_keys($days, 0),
            ],
            'cash' => [
                'collected' => array_fill_keys($days, 0),
                'paid_to_carriers' => array_fill_keys($days, 0),
                'gap' => array_fill_keys($days, 0),
            ]
        ];

        // 1. Activity & Profitability Data
        $tasksQuery = Task::whereBetween('created_at', [$dateFrom, $dateTo])
            ->withSum(['userWalletTransactions as total_given_commission' => function($q) {
                $q->where('transaction_type', 'credit');
            }], 'amount');
        if (!empty($customerIds)) {
            $tasksQuery->whereIn('user_id', $customerIds);
        }
        $tasks = $tasksQuery->get();

        foreach ($tasks as $task) {
            $date = $task->created_at->format('Y-m-d');
            if (isset($reportData['activity']['shipments'][$date])) {
                $reportData['activity']['shipments'][$date]++;
                
                if ($task->user_id) {
                    $reportData['activity']['active_customers'][$date][$task->user_id] = true;
                }
                
                $revenue = $task->total_price ?: 0;
                $reportData['activity']['revenue'][$date] += $revenue;
                
                $commission = $task->commission ?: 0;
                $carrierCost = max(0, $revenue - $commission);
                $reportData['activity']['carrier_cost'][$date] += $carrierCost;

                if ($calcNetCommission) {
                    $grossMargin = $revenue - $carrierCost;
                    $givenCommission = $task->total_given_commission ?: 0;
                    $netCommission = max(0, $grossMargin - $givenCommission);
                    
                    $reportData['activity']['net_commission'][$date] += $netCommission;
                }
            }
        }

        foreach ($reportData['activity']['active_customers'] as $date => $customers) {
            $reportData['activity']['active_customers'][$date] = count($customers);
        }

        // 2. Cash & Collection Data
        $paymentsQuery = Payments::where('status', 'paid')
            ->whereBetween('created_at', [$dateFrom, $dateTo]);
        
        if (!empty($customerIds)) {
            $paymentsQuery->where(function($q) use ($customerIds) {
                $q->whereIn('owner_id', $customerIds); 
            });
        }
        $payments = $paymentsQuery->get();
        
        foreach ($payments as $payment) {
            $paymentDate = $payment->completed_at ? Carbon::parse($payment->completed_at)->format('Y-m-d') : $payment->created_at->format('Y-m-d');
            if (isset($reportData['cash']['collected'][$paymentDate])) {
                $reportData['cash']['collected'][$paymentDate] += $payment->amount;
            }
        }

        $driverTransactionsQuery = Wallet_Transaction::where('transaction_type', 'debit')
            ->whereHas('wallet', function($q) {
                $q->where('user_type', 'driver');
            })
            ->whereBetween('created_at', [$dateFrom, $dateTo]);
            
        if (!empty($customerIds)) {
            $driverTransactionsQuery->whereHas('task', function($q) use ($customerIds) {
                $q->whereIn('user_id', $customerIds);
            });
        }
        
        $driverTransactions = $driverTransactionsQuery->get();
        foreach ($driverTransactions as $tx) {
            $txDate = $tx->created_at->format('Y-m-d');
            if (isset($reportData['cash']['paid_to_carriers'][$txDate])) {
                $reportData['cash']['paid_to_carriers'][$txDate] += $tx->amount;
            }
        }

        return $reportData;
    }
}
