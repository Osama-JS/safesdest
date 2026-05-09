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

        $stats = [
            'investment_balance' => $investorWallet?->balance ?? 0,
            'personal_balance'   => $personalWallet?->balance ?? 0,
            'paid_tasks_count'   => Task::where('investor_id', $investor->id)->count(),
            'total_commissions'  => $personalWallet?->transactions()
                ->where('transaction_type', 'credit')->sum('amount') ?? 0,
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
