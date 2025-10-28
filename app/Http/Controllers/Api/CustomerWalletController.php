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
    public function show(Request $request)
    {
        try {
            $customer = $request->user();

            $wallet = Wallet::where('user_type', 'customer')
                           ->where('customer_id', $customer->id)
                           ->first();

            if (!$wallet || !$wallet->status) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Wallet not found'
                ]);
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
                                                            'sequence' => $transaction->sequence,
                                                            'image' => $transaction->image ? url($transaction->image) : null,
                                                            'created_at' => $transaction->created_at,
                                                            'updated_at' => $transaction->updated_at,



                                                       ];
                                                   });

            // Calculate statistics
            $totalDebit = $wallet->debit;

            $totalCredit = $wallet->credit;


            return response()->json([
                'status' => 200,
                'data' => [
                    'wallet' => [
                        'id' => $wallet->id,
                        'balance' => (string) ($totalDebit - $totalCredit),
                        'currency' => 'SAR',
                        'status' => $wallet->status ?? 'active',
                        'debt_ceiling' => $wallet->debt_ceiling,
                        'created_at' => $wallet->created_at,
                        'updated_at' => $wallet->updated_at,

                    ],
                    'statistics' => [
                        'total_debit' => $totalDebit,
                        'total_credit' => $totalCredit,
                        'net_balance' => (string) ($totalDebit - $totalCredit),

                    ],
                    'recent_transactions' => $recentTransactions,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get wallet information',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get wallet transactions with pagination and filters
     */
    public function getTransactions(Request $request)
    {
        try {
            $customer = $request->user();

            // جلب المحفظة الخاصة بالعميل
            $wallet = Wallet::where('user_type', 'customer')
                            ->where('customer_id', $customer->id)
                            ->first();

            if (!$wallet) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Wallet not found'
                ]);
            }

            // بناء الاستعلام الأساسي
            $query = Wallet_Transaction::where('wallet_id', $wallet->id);

            // ✅ تطبيق الفلاتر
            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }


            if ($request->filled('image') && $request->image == 1) {
                $query->where('image', '!=', null);
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

            // ✅ البحث
            if ($request->filled('search')) {
                $query->where('description', 'like', '%' . $request->search . '%');
            }

            // ✅ الترتيب
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // ✅ التصفح (Pagination)
            $perPage = $request->get('per_page', 15);
            $transactions = $query->paginate($perPage);

            // ✅ تنسيق البيانات بنفس نمط getTasks
            $transactionsData = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'transaction_type' => $transaction->transaction_type,
                    'description' => $transaction->description,
                    'sequence' => $transaction->sequence,
                    'image' => $transaction->image ? url('storage/' . $transaction->image) : null,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ];
            });

            // ✅ الإرجاع بنفس بنية getTasks
            return response()->json([
                'status' => 200,
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
                'status' => 500,
                'message' => 'Failed to get transactions',
                'error' => $e->getMessage()
            ]);
        }
    }

}
