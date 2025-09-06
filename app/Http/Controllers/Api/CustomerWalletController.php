<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CustomerWalletController extends Controller
{
    /**
     * Get customer wallet information
     */
    public function show(Request $request)
    {
        try {
            $customer = $request->user();

            $wallet = Wallet::where('user_type', 'customer')
                           ->where('user_id', $customer->id)
                           ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            // Get recent transactions
            $recentTransactions = Wallet_Transaction::where('wallet_id', $wallet->id)
                                                   ->orderBy('created_at', 'desc')
                                                   ->limit(5)
                                                   ->get()
                                                   ->map(function ($transaction) {
                                                       return [
                                                           'id' => $transaction->id,
                                                           'amount' => $transaction->amount,
                                                           'transaction_type' => $transaction->transaction_type,
                                                           'description' => $transaction->description,
                                                           'status' => $transaction->status,
                                                           'created_at' => $transaction->created_at,
                                                       ];
                                                   });

            // Calculate statistics
            $totalDeposits = Wallet_Transaction::where('wallet_id', $wallet->id)
                                              ->where('transaction_type', 'credit')
                                              ->where('status', 'completed')
                                              ->sum('amount');

            $totalWithdrawals = Wallet_Transaction::where('wallet_id', $wallet->id)
                                                 ->where('transaction_type', 'debit')
                                                 ->where('status', 'completed')
                                                 ->sum('amount');

            $pendingTransactions = Wallet_Transaction::where('wallet_id', $wallet->id)
                                                    ->where('status', 'pending')
                                                    ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'wallet' => [
                        'id' => $wallet->id,
                        'balance' => $wallet->balance,
                        'currency' => 'SAR',
                        'status' => $wallet->status ?? 'active',
                        'created_at' => $wallet->created_at,
                        'updated_at' => $wallet->updated_at,
                    ],
                    'statistics' => [
                        'total_deposits' => $totalDeposits,
                        'total_withdrawals' => $totalWithdrawals,
                        'pending_transactions' => $pendingTransactions,
                        'net_balance' => $totalDeposits - $totalWithdrawals,
                    ],
                    'recent_transactions' => $recentTransactions,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get wallet information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet transactions with pagination and filters
     */
    public function getTransactions(Request $request)
    {
        try {
            $customer = $request->user();

            $wallet = Wallet::where('user_type', 'customer')
                           ->where('user_id', $customer->id)
                           ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            $query = Wallet_Transaction::where('wallet_id', $wallet->id);

            // Apply filters
            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('amount_min')) {
                $query->where('amount', '>=', $request->amount_min);
            }

            if ($request->filled('amount_max')) {
                $query->where('amount', '<=', $request->amount_max);
            }

            // Search in description
            if ($request->filled('search')) {
                $query->where('description', 'like', '%' . $request->search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $transactions = $query->paginate($perPage);

            $transactionsData = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'transaction_type' => $transaction->transaction_type,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'sequence' => $transaction->sequence,
                    'maturity_time' => $transaction->maturity_time,
                    'image' => $transaction->image ? asset('storage/' . $transaction->image) : null,
                    'task_id' => $transaction->task_id,
                    'clearance_id' => $transaction->clearance_id,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactionsData,
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'from' => $transactions->firstItem(),
                        'to' => $transactions->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deposit money to wallet
     */
    public function deposit(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10|max:10000',
                'description' => 'nullable|string|max:255',
                'payment_method' => 'required|string|in:hyperpay,bank_transfer,cash',
                'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $wallet = Wallet::where('user_type', 'customer')
                           ->where('user_id', $customer->id)
                           ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            DB::beginTransaction();

            // Handle receipt image upload
            $receiptPath = null;
            if ($request->hasFile('receipt_image')) {
                $file = $request->file('receipt_image');
                $receiptPath = $file->store('wallet/receipts', 'public');
            }

            // Create transaction record
            $transaction = Wallet_Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'transaction_type' => 'credit',
                'description' => $request->description ?: 'Wallet deposit via ' . $request->payment_method,
                'status' => $request->payment_method === 'hyperpay' ? 'pending' : 'pending_approval',
                'image' => $receiptPath,
                'maturity_time' => now(),
                'sequence' => $this->generateTransactionSequence(),
            ]);

            // For HyperPay, initiate payment process
            if ($request->payment_method === 'hyperpay') {
                // Integrate with HyperPay API here
                // $paymentResult = $this->initiateHyperPayPayment($transaction);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Deposit initiated successfully',
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'status' => $transaction->status,
                        'payment_url' => null, // Will be provided by HyperPay
                        'requires_payment' => true,
                    ]
                ], 201);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deposit request submitted successfully. Awaiting approval.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'requires_approval' => true,
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process deposit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Withdraw money from wallet
     */
    public function withdraw(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10',
                'description' => 'nullable|string|max:255',
                'withdrawal_method' => 'required|string|in:bank_transfer,cash',
                'bank_account' => 'required_if:withdrawal_method,bank_transfer|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $wallet = Wallet::where('user_type', 'customer')
                           ->where('user_id', $customer->id)
                           ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            // Check if sufficient balance
            if ($wallet->balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance'
                ], 400);
            }

            DB::beginTransaction();

            // Create withdrawal transaction
            $transaction = Wallet_Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'transaction_type' => 'debit',
                'description' => $request->description ?: 'Wallet withdrawal via ' . $request->withdrawal_method,
                'status' => 'pending_approval',
                'maturity_time' => now(),
                'sequence' => $this->generateTransactionSequence(),
            ]);

            // Temporarily hold the amount (don't deduct from balance yet)
            // The balance will be deducted when the withdrawal is approved

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully. Awaiting approval.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'requires_approval' => true,
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process withdrawal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer money to another customer
     */
    public function transfer(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'recipient_id' => 'required|exists:customers,id',
                'amount' => 'required|numeric|min:10',
                'description' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Can't transfer to self
            if ($customer->id == $request->recipient_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot transfer to yourself'
                ], 400);
            }

            $senderWallet = Wallet::where('user_type', 'customer')
                                 ->where('user_id', $customer->id)
                                 ->first();

            $recipientWallet = Wallet::where('user_type', 'customer')
                                    ->where('user_id', $request->recipient_id)
                                    ->first();

            if (!$senderWallet || !$recipientWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            // Check sufficient balance
            if ($senderWallet->balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance'
                ], 400);
            }

            DB::beginTransaction();

            // Deduct from sender
            $senderWallet->decrement('balance', $request->amount);

            // Add to recipient
            $recipientWallet->increment('balance', $request->amount);

            // Create sender transaction
            Wallet_Transaction::create([
                'wallet_id' => $senderWallet->id,
                'amount' => $request->amount,
                'transaction_type' => 'debit',
                'description' => $request->description ?: 'Transfer to customer #' . $request->recipient_id,
                'status' => 'completed',
                'maturity_time' => now(),
                'sequence' => $this->generateTransactionSequence(),
            ]);

            // Create recipient transaction
            Wallet_Transaction::create([
                'wallet_id' => $recipientWallet->id,
                'amount' => $request->amount,
                'transaction_type' => 'credit',
                'description' => $request->description ?: 'Transfer from customer #' . $customer->id,
                'status' => 'completed',
                'maturity_time' => now(),
                'sequence' => $this->generateTransactionSequence(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer completed successfully',
                'data' => [
                    'amount' => $request->amount,
                    'recipient_id' => $request->recipient_id,
                    'new_balance' => $senderWallet->fresh()->balance,
                ]
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet statements/reports
     */
    public function getStatements(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'period' => 'required|in:week,month,quarter,year,custom',
                'start_date' => 'required_if:period,custom|date',
                'end_date' => 'required_if:period,custom|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $wallet = Wallet::where('user_type', 'customer')
                           ->where('user_id', $customer->id)
                           ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            // Determine date range
            switch ($request->period) {
                case 'week':
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                    break;
                case 'month':
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    break;
                case 'quarter':
                    $startDate = Carbon::now()->startOfQuarter();
                    $endDate = Carbon::now()->endOfQuarter();
                    break;
                case 'year':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    break;
                case 'custom':
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    break;
            }

            // Get transactions for the period
            $transactions = Wallet_Transaction::where('wallet_id', $wallet->id)
                                             ->whereBetween('created_at', [$startDate, $endDate])
                                             ->orderBy('created_at', 'desc')
                                             ->get();

            // Calculate summary
            $totalCredits = $transactions->where('transaction_type', 'credit')
                                       ->where('status', 'completed')
                                       ->sum('amount');

            $totalDebits = $transactions->where('transaction_type', 'debit')
                                      ->where('status', 'completed')
                                      ->sum('amount');

            $pendingCredits = $transactions->where('transaction_type', 'credit')
                                         ->whereIn('status', ['pending', 'pending_approval'])
                                         ->sum('amount');

            $pendingDebits = $transactions->where('transaction_type', 'debit')
                                        ->whereIn('status', ['pending', 'pending_approval'])
                                        ->sum('amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'type' => $request->period,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ],
                    'summary' => [
                        'opening_balance' => $wallet->balance - ($totalCredits - $totalDebits),
                        'total_credits' => $totalCredits,
                        'total_debits' => $totalDebits,
                        'pending_credits' => $pendingCredits,
                        'pending_debits' => $pendingDebits,
                        'net_change' => $totalCredits - $totalDebits,
                        'closing_balance' => $wallet->balance,
                    ],
                    'transactions' => $transactions->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                            'amount' => $transaction->amount,
                            'type' => $transaction->transaction_type,
                            'description' => $transaction->description,
                            'status' => $transaction->status,
                            'sequence' => $transaction->sequence,
                        ];
                    }),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate statement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique transaction sequence
     */
    private function generateTransactionSequence()
    {
        $lastTransaction = Wallet_Transaction::orderBy('sequence', 'desc')->first();
        return $lastTransaction ? $lastTransaction->sequence + 1 : 1000001;
    }
}
