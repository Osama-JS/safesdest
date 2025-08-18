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
            $totalEarnings = $driver->tasks()
                ->where('status', 'delivered')
                ->sum('commission');

            // Get pending amount (from tasks not yet paid)
            $pendingAmount = $driver->tasks()
                ->where('status', 'delivered')
                ->where('payment_status', '!=', 'paid')
                ->sum('commission');

            return response()->json([
                'success' => true,
                'message' => 'Wallet information retrieved successfully',
                'data' => [
                    'wallet' => [
                        'balance' => (float) $wallet->balance,
                        'debt_ceiling' => (float) $wallet->debt_ceiling,
                        'pending_amount' => (float) $pendingAmount,
                        'total_earnings' => (float) $totalEarnings,
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
