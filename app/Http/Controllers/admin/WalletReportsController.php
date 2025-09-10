<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Teams;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use App\Models\Team_Wallet;
use App\Models\Team_Wallet_Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class WalletReportsController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:view_reports', ['only' => ['index', 'show']]);
    //     $this->middleware('permission:generate_reports', ['only' => ['generateReport']]);
    // }

    /**
     * Display wallet reports page
     */
    public function index()
    {
        try {
            // Get all customers, drivers, and teams for filters
            $customers = Customer::select('id', 'name')->orderBy('name')->get();
            $drivers = Driver::select('id', 'name')->orderBy('name')->get();
            $teams = Teams::select('id', 'name')->orderBy('name')->get();

            return view('admin.reports.wallet-reports', compact('customers', 'drivers', 'teams'));
        } catch (Exception $e) {
            return response()->json([
                'status' => 2,
                'error' => 'Error loading wallet reports page: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate wallet report
     */
    public function generateReport(Request $request)
    {
        try {
            $request->validate([
                'wallet_type' => 'required|in:customer,driver,team',
                'owner_id' => 'required|integer',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
                'transaction_type' => 'nullable|in:credit,debit',
                'status' => 'nullable|in:0,1'
            ]);

            $walletType = $request->wallet_type;
            $ownerId = $request->owner_id;
            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate = Carbon::parse($request->to_date)->endOfDay();

            // Get wallet data based on type
            $walletData = $this->getWalletData($walletType, $ownerId, $fromDate, $toDate, $request);

            if (!$walletData) {
                return response()->json([
                    'status' => 2,
                    'error' => 'Wallet not found for the selected owner'
                ]);
            }

            // Generate PDF
            return $this->generateWalletPDF($walletData, $request);

        } catch (Exception $e) {
            return response()->json([
                'status' => 2,
                'error' => 'Error generating wallet report: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get wallet data based on type
     */
    private function getWalletData($walletType, $ownerId, $fromDate, $toDate, $request)
    {
        switch ($walletType) {
            case 'customer':
                return $this->getCustomerWalletData($ownerId, $fromDate, $toDate, $request);
            case 'driver':
                return $this->getDriverWalletData($ownerId, $fromDate, $toDate, $request);
            case 'team':
                return $this->getTeamWalletData($ownerId, $fromDate, $toDate, $request);
            default:
                return null;
        }
    }

    /**
     * Get customer wallet data
     */
    private function getCustomerWalletData($customerId, $fromDate, $toDate, $request)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return null;
        }

        $wallet = Wallet::where('customer_id', $customerId)->where('user_type', 'customer')->first();
        if (!$wallet) {
            return null;
        }

        // Build transactions query
        $transactionsQuery = Wallet_Transaction::where('wallet_id', $wallet->id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->with(['task', 'user']);

        // Apply filters
        if ($request->transaction_type) {
            $transactionsQuery->where('transaction_type', $request->transaction_type);
        }

        if ($request->status !== null) {
            $transactionsQuery->where('status', $request->status);
        }

        $transactions = $transactionsQuery->orderBy('created_at', 'desc')->get();

        // Calculate statistics
        $statistics = $this->calculateWalletStatistics($transactions, $wallet);

        return [
            'type' => 'customer',
            'owner' => $customer,
            'wallet' => $wallet,
            'transactions' => $transactions,
            'statistics' => $statistics
        ];
    }

    /**
     * Get driver wallet data
     */
    private function getDriverWalletData($driverId, $fromDate, $toDate, $request)
    {
        $driver = Driver::find($driverId);
        if (!$driver) {
            return null;
        }

        $wallet = Wallet::where('driver_id', $driverId)->where('user_type', 'driver')->first();
        if (!$wallet) {
            return null;
        }

        // Build transactions query
        $transactionsQuery = Wallet_Transaction::where('wallet_id', $wallet->id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->with(['task', 'user']);

        // Apply filters
        if ($request->transaction_type) {
            $transactionsQuery->where('transaction_type', $request->transaction_type);
        }

        if ($request->status !== null) {
            $transactionsQuery->where('status', $request->status);
        }

        $transactions = $transactionsQuery->orderBy('created_at', 'desc')->get();

        // Calculate statistics
        $statistics = $this->calculateWalletStatistics($transactions, $wallet);

        return [
            'type' => 'driver',
            'owner' => $driver,
            'wallet' => $wallet,
            'transactions' => $transactions,
            'statistics' => $statistics
        ];
    }

    /**
     * Get team wallet data
     */
    private function getTeamWalletData($teamId, $fromDate, $toDate, $request)
    {
        $team = Teams::find($teamId);
        if (!$team) {
            return null;
        }

        $teamWallet = Team_Wallet::where('team_id', $teamId)->first();
        if (!$teamWallet) {
            return null;
        }

        // Build transactions query
        $transactionsQuery = Team_Wallet_Transaction::where('team_wallet_id', $teamWallet->id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->with(['task', 'user']);

        // Apply filters
        if ($request->transaction_type) {
            $transactionsQuery->where('transaction_type', $request->transaction_type);
        }

        $transactions = $transactionsQuery->orderBy('created_at', 'desc')->get();

        // Calculate statistics for team wallet
        $statistics = $this->calculateTeamWalletStatistics($transactions, $teamWallet);

        return [
            'type' => 'team',
            'owner' => $team,
            'wallet' => $teamWallet,
            'transactions' => $transactions,
            'statistics' => $statistics
        ];
    }

    /**
     * Calculate wallet statistics
     */
    private function calculateWalletStatistics($transactions, $wallet)
    {
        $totalCredit = $transactions->where('transaction_type', 'credit')->sum('amount');
        $totalDebit = $transactions->where('transaction_type', 'debit')->sum('amount');
        $totalTransactions = $transactions->count();
        $currentBalance = $wallet->balance;

        return [
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'net_amount' => $totalCredit - $totalDebit,
            'total_transactions' => $totalTransactions,
            'current_balance' => $currentBalance,
            'credit_transactions' => $transactions->where('transaction_type', 'credit')->count(),
            'debit_transactions' => $transactions->where('transaction_type', 'debit')->count(),
        ];
    }

    /**
     * Calculate team wallet statistics
     */
    private function calculateTeamWalletStatistics($transactions, $teamWallet)
    {
        $totalCredit = $transactions->where('transaction_type', 'credit')->sum('amount');
        $totalDebit = $transactions->where('transaction_type', 'debit')->sum('amount');
        $totalTransactions = $transactions->count();
        $currentBalance = $teamWallet->balance;

        return [
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'net_amount' => $totalCredit - $totalDebit,
            'total_transactions' => $totalTransactions,
            'current_balance' => $currentBalance,
            'credit_transactions' => $transactions->where('transaction_type', 'credit')->count(),
            'debit_transactions' => $transactions->where('transaction_type', 'debit')->count(),
        ];
    }

    /**
     * Generate wallet PDF report
     */
    private function generateWalletPDF($walletData, $request)
    {
        $reportDate = Carbon::now()->format('d/m/Y');
        $fromDate = Carbon::parse($request->from_date)->format('d/m/Y');
        $toDate = Carbon::parse($request->to_date)->format('d/m/Y');

        // Get transactions and summary
        $transactions = $walletData['transactions'] ?? collect([]);
        $wallet = $walletData['wallet'] ?? null;

        // Ensure transactions is a collection
        if (!($transactions instanceof \Illuminate\Support\Collection)) {
            $transactions = collect($transactions);
        }

        if ($wallet && $transactions->isNotEmpty()) {
            $summary = $this->calculateWalletStatistics($transactions, $wallet);
        } else {
            // Default summary for empty transactions
            $summary = [
                'total_credit' => 0,
                'total_debit' => 0,
                'net_amount' => 0,
                'total_transactions' => 0,
                'current_balance' => $wallet ? $wallet->balance : 0,
                'credit_transactions' => 0,
                'debit_transactions' => 0,
            ];
        }

        $data = [
            'wallet_data' => $walletData,
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => $request->all(),
            'report_date' => $reportDate,
            'from_date' => $fromDate,
            'to_date' => $toDate
        ];

        $html = view('admin.reports.pdf.wallet-report', $data)->render();

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Get wallet preview data (AJAX)
     */
    public function getWalletPreview(Request $request)
    {
        try {
            $request->validate([
                'wallet_type' => 'required|in:customer,driver,team',
                'owner_id' => 'required|integer',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
                'transaction_type' => 'nullable|in:credit,debit',
                'status' => 'nullable|in:0,1'
            ]);

            $walletType = $request->wallet_type;
            $ownerId = $request->owner_id;
            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate = Carbon::parse($request->to_date)->endOfDay();

            // Get wallet data
            $walletData = $this->getWalletData($walletType, $ownerId, $fromDate, $toDate, $request);

            if (!$walletData) {
                return response()->json([
                    'success' => false,
                    'message' => 'المحفظة غير موجودة للمالك المحدد'
                ]);
            }

            // Format transactions for preview
            // حساب الرصيد لكل معاملة
            $currentBalance = $walletData['statistics']['current_balance'];
            $transactions = $walletData['transactions']->sortBy('created_at');

            // حساب الرصيد الأولي
            $initialBalance = $currentBalance;
            foreach ($transactions as $transaction) {
                if ($transaction->transaction_type == 'credit') {
                    $initialBalance -= $transaction->amount;
                } else {
                    $initialBalance += $transaction->amount;
                }
            }

            // تنسيق المعاملات مع الرصيد
            $runningBalance = $initialBalance;
            $formattedTransactions = $transactions->map(function ($transaction) use ($walletData, &$runningBalance) {
                // حساب الرصيد بعد هذه المعاملة
                if ($transaction->transaction_type == 'credit') {
                    $runningBalance += $transaction->amount;
                } else {
                    $runningBalance -= $transaction->amount;
                }

                return [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->format('d/m/Y H:i'),
                    'type' => $transaction->transaction_type,
                    'type_ar' => $transaction->transaction_type == 'credit' ? 'إيداع' : 'سحب',
                    'amount' => number_format($transaction->amount, 2),
                    'description' => $transaction->description ?? 'غير محدد',
                    'balance_after' => number_format($runningBalance, 2),
                    'task_id' => $transaction->task_id ? '#' . $transaction->task_id : '-'
                ];
            });

            // عكس الترتيب لعرض الأحدث أولاً
            $formattedTransactions = $formattedTransactions->reverse()->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'wallet_data' => $walletData,
                    'transactions' => $formattedTransactions,
                    'summary' => $walletData['statistics']
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب بيانات المحفظة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get owners by type (AJAX)
     */
    public function getOwnersByType(Request $request)
    {
        try {
            $type = $request->type;
            $owners = [];

            switch ($type) {
                case 'customer':
                    $owners = Customer::select('id', 'name')->orderBy('name')->get();
                    break;
                case 'driver':
                    $owners = Driver::select('id', 'name')->orderBy('name')->get();
                    break;
                case 'team':
                    $owners = Teams::select('id', 'name')->orderBy('name')->get();
                    break;
            }

            return response()->json([
                'status' => 1,
                'data' => $owners
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 2,
                'error' => $e->getMessage()
            ]);
        }
    }
}
