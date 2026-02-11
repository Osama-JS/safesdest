<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DriverWalletController extends Controller
{
    /**
     * Get driver wallet information
     */
    public function getWallet(Request $request)
    {
        try {
            $driver = $request->user();

            // Get or create wallet for driver
            $wallet = $driver->wallet;
            if (!$wallet) {
                $wallet = Wallet::create([
                    'driver_id' => $driver->id,
                    'user_type' => 'driver',
                    'balance' => 0,
                    'debt_ceiling' => 0
                ]);
            }

            // Calculate total earnings from completed tasks
            $totalEarnings = $wallet->credit;

            // Get pending amount (from tasks not yet paid)
            $pendingAmount = $wallet->debit;

            // Calculate pending withdrawals
            $pendingWithdrawals = $wallet->withdrawalRequests()
                ->where('status', 'pending')
                ->sum('amount_requested');

            Log::alert("Pending amount: " . $pendingAmount);
            Log::alert("Total earnings: " . $totalEarnings);

            // Calculate available balance (Total - Paid - Pending Withdrawals)
            // Note: Balance from getBalanceAttribute is Credit - Debit.
            // We should subtract pending withdrawals from the available balance.
            $availableBalance = $wallet->balance - $pendingWithdrawals;

            return response()->json([
                'success' => true,
                'message' => 'Wallet information retrieved successfully',
                'data' => [
                    'wallet' => [
                        'balance' => (float) $availableBalance, // Show available balance
                        'current_balance' => (float) $wallet->balance, // Show actual ledger balance
                        'debt_ceiling' => (float) $wallet->debt_ceiling,
                        'pending_amount' => (float) $pendingAmount,
                        'total_earnings' => (float) $totalEarnings,
                        'pending_withdrawal' => (float) $pendingWithdrawals,
                        'currency' => 'SAR' // Assuming Saudi Riyal
                    ],
                    'commission' => [
                        'type' => $driver->commission_type,
                        'value' => (float) $driver->commission_value
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get driver wallet error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get wallet information'
            ], 500);
        }
    }

    /**
     * Request a withdrawal
     */
    public function requestWithdrawal(Request $request)
    {
        try {
            $driver = $request->user();
            $wallet = $driver->wallet;

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'notes' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Calculate pending withdrawals
            $pendingWithdrawals = $wallet->withdrawalRequests()
                ->where('status', 'pending')
                ->sum('amount_requested');

            // Available balance = Ledger Balance - Pending Withdrawals
            $availableBalance = $wallet->balance - $pendingWithdrawals;

            if ($request->amount > $availableBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient available balance'
                ], 400);
            }

            // Create Withdrawal Request
            $withdrawal = \App\Models\WithdrawalRequest::create([
                'driver_id' => $driver->id,
                'wallet_id' => $wallet->id,
                'amount_requested' => $request->amount,
                'status' => 'pending',
                'admin_notes' => $request->notes, // Storing user notes in admin_notes for now or separate field if exists
                'payment_method' => 'cash', // Default or from request
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully',
                'data' => $withdrawal
            ], 200);

        } catch (\Exception $e) {
             Log::error('Withdrawal request error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit withdrawal request'
            ], 500);
        }
    }

    /**
     * Get withdrawal history
     */
    public function getWithdrawalHistory(Request $request)
    {
        try {
            $driver = $request->user();

            $withdrawals = \App\Models\WithdrawalRequest::where('driver_id', $driver->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'withdrawals' => $withdrawals->items(),
                    'pagination' => [
                        'current_page' => $withdrawals->currentPage(),
                        'last_page' => $withdrawals->lastPage(),
                        'per_page' => $withdrawals->perPage(),
                        'total' => $withdrawals->total(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch withdrawal history'
            ], 500);
        }
    }

    /**
     * Get wallet transactions history
     */
    public function getTransactions(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'type' => 'nullable|string|in:credit,debit,commission,withdrawal,deposit',
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $perPage = $request->get('per_page', 20);

            // Get wallet
            $wallet = $driver->wallet;
            if (!$wallet) {
                return response()->json([
                    'success' => true,
                    'transactions' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0
                    ]
                ], 200);
            }

            // Build query for wallet transactions
            $query = Wallet_Transaction::where('wallet_id', $wallet->id);

            // Filter by transaction type
            if ($request->type) {
                $query->where('transaction_type', $request->type);
            }

            // Date range filter
            if ($request->from) {
                $query->whereDate('created_at', '>=', $request->from);
            }
            if ($request->to) {
                $query->whereDate('created_at', '<=', $request->to);
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Format transactions
            $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => (float) $transaction->amount,
                    'type' => $transaction->transaction_type,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'image' => $transaction->image ? url('storage/' . $transaction->image) : null,
                    'created_at' => $transaction->created_at,
                    'maturity_time' => $transaction->maturity_time,
                    'reference_id' => $transaction->reference_id
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Transactions retrieved successfully',
                'data' => [
                    'transactions' => $formattedTransactions,
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'from' => $transactions->firstItem(),
                        'to' => $transactions->lastItem()
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get wallet transactions error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get transactions'
            ], 500);
        }
    }

    /**
     * Get driver earnings statistics
     */
    public function getEarningsStats(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'period' => 'nullable|string|in:today,week,month,year'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $period = $request->get('period', 'month');

            // Define date ranges
            $dateRanges = [
                'today' => [now()->startOfDay(), now()->endOfDay()],
                'week' => [now()->startOfWeek(), now()->endOfWeek()],
                'month' => [now()->startOfMonth(), now()->endOfMonth()],
                'year' => [now()->startOfYear(), now()->endOfYear()]
            ];

            [$startDate, $endDate] = $dateRanges[$period];

            // Get completed tasks in period
            $completedTasks = $driver->tasks()
                ->where('status', 'delivered')
                ->whereBetween('completed_at', [$startDate, $endDate]);

            $totalEarnings = $completedTasks->sum('commission');
            $totalTasks = $completedTasks->count();
            $averageEarning = $totalTasks > 0 ? $totalEarnings / $totalTasks : 0;

            // Get all time stats
            $allTimeEarnings = $driver->tasks()
                ->where('status', 'delivered')
                ->sum('commission');

            $allTimeTasks = $driver->tasks()
                ->where('status', 'delivered')
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Earnings statistics retrieved successfully',
                'data' => [
                    'period' => $period,
                    'stats' => [
                        'total_earnings' => (float) $totalEarnings,
                        'total_tasks' => $totalTasks,
                        'average_earning_per_task' => (float) $averageEarning,
                        'period_start' => $startDate->toDateString(),
                        'period_end' => $endDate->toDateString()
                    ],
                    'all_time' => [
                        'total_earnings' => (float) $allTimeEarnings,
                        'total_tasks' => $allTimeTasks,
                        'average_earning_per_task' => $allTimeTasks > 0 ? (float) ($allTimeEarnings / $allTimeTasks) : 0
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get earnings stats error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get earnings statistics'
            ], 500);
        }
    }
}
