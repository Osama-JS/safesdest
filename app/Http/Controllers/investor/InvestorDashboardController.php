<?php

namespace App\Http\Controllers\investor;

use App\Http\Controllers\Controller;
use App\Models\InvestorWalletTransaction;
use App\Models\Task;
use Illuminate\Http\Request;

class InvestorDashboardController extends Controller
{
    public function index()
    {
        $investor = auth()->user()->load([
            'investorWallet',
            'userWallet',
            'activeInvestmentContract',
        ]);

        $investorWallet = $investor->investorWallet;
        $personalWallet = $investor->userWallet;
        $contract       = $investor->activeInvestmentContract;

        // إجمالي التمويل التراكمي للمهام (حجم التدوير)
        $totalInvested = (float) ($investorWallet?->transactions()
            ->where('transaction_type', 'debit')
            ->whereNotNull('task_id')
            ->sum('amount') ?? 0);

        if ($totalInvested == 0) {
            $totalInvested = (float) ($investorWallet?->transactions()
                ->where('transaction_type', 'debit')
                ->sum('amount') ?? 0);
        }
        
        // إجمالي العمولات / الأرباح
        $totalCommissions = (float) ($personalWallet?->transactions()
            ->where('transaction_type', 'credit')->sum('amount') ?? 0);

        // إجمالي العمولات المسحوبة من المحفظة
        $withdrawnCommissions = (float) ($personalWallet?->transactions()
            ->where('transaction_type', 'debit')->sum('amount') ?? 0);

        // إجمالي رأس المال المودع (إيداعات + إعادة استثمار أرباح)
        $depositedCapital = (float) ($investorWallet?->transactions()
            ->where('transaction_type', 'credit')
            ->whereIn('source_type', ['capital', 'hyperpay'])
            ->sum('amount') ?? 0);

        // إجمالي سحوبات رأس المال
        $withdrawnCapital = (float) ($investorWallet?->transactions()
            ->where('transaction_type', 'debit')
            ->whereNull('task_id')
            ->sum('amount') ?? 0);

        // صافي رأس المال الأساسي للمستثمر
        $baseCapital = max(0, $depositedCapital - $withdrawnCapital);

        $paidTasksCount = Task::where('investor_id', $investor->id)
            ->where('investor_payment_status', 'paid')
            ->count();
        if ($paidTasksCount === 0) {
            $paidTasksCount = Task::where('investor_id', $investor->id)->count();
        }

        $stats = [
            'investment_balance'     => (float) ($investorWallet?->balance ?? 0),
            'personal_balance'       => (float) ($personalWallet?->withdrawable_balance ?? 0),
            'personal_total_balance' => (float) ($personalWallet?->balance ?? 0),
            'withdrawn_commissions'  => $withdrawnCommissions,
            'paid_tasks_count'       => $paidTasksCount,
            'total_commissions'  => $totalCommissions,
            'total_invested'     => $totalInvested,
            'deposited_capital'  => $depositedCapital,
            'capital_withdrawn'  => $withdrawnCapital,
            'base_capital'       => $baseCapital,
            'roi_percentage'     => $totalInvested > 0 ? round(($totalCommissions / $totalInvested) * 100, 2) : 0,
        ];

        // البيانات للرسم البياني (أرباح آخر 6 أشهر)
        $chartData = [
            'labels' => [],
            'data' => []
        ];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $amount = $personalWallet?->transactions()
                ->where('transaction_type', 'credit')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('amount') ?? 0;
            
            $chartData['labels'][] = $month->translatedFormat('M');
            $chartData['data'][] = (float) $amount;
        }

        $recentActivity = $investorWallet
            ? InvestorWalletTransaction::where('investor_wallet_id', $investorWallet->id)
                ->with('task')->latest()->take(5)->get()
            : collect();

        return view('investor.dashboard', compact('investor', 'stats', 'recentActivity', 'contract', 'chartData'));
    }
}
