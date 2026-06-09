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

    public function initiateRecharge(Request $request, \App\Services\HyperpayService $hyperpay)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'payment_method' => 'required|string|in:hyperpay_visa,hyperpay_mastercard,hyperpay_mada',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ]);
            }

            $customer = $request->user();
            $wallet = Wallet::where('user_type', 'customer')
                            ->where('customer_id', $customer->id)
                            ->first();

            if (!$wallet) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Wallet not found'
                ]);
            }

            $brand = match ($request->payment_method) {
                'hyperpay_mada' => 'MADA',
                'hyperpay_mastercard' => 'MASTER',
                'hyperpay_visa' => 'VISA',
            };

            $options = [
                'merchantTransactionId' => 'RECHARGE_' . uniqid(),
                'customerEmail' => $customer->email ?? 'customer@safedest.com',
                'customerName' => $customer->name ?? 'Customer',
                'billingStreet1' => 'Riyadh',
                'billingCity' => 'Riyadh',
                'billingState' => 'Riyadh',
                'billingCountry' => 'SA',
                'billingPostcode' => '12211',
            ];

            $checkout = $hyperpay->createCheckout($request->amount, $brand, '', $options);

            if (!isset($checkout['id'])) {
                throw new Exception('Failed to create HyperPay checkout');
            }

            $recharge = \App\Models\WalletRecharge::create([
                'customer_id' => $customer->id,
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'checkout_id' => $checkout['id'],
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 200,
                'success' => true,
                'data' => [
                    'payment_id' => $recharge->id,
                    'gateway_name' => 'hyperpay',
                    'checkout_id' => $checkout['id'],
                    'payment_url' => $hyperpay->getScriptUrl() . "?checkoutId=" . $checkout['id'],
                    'url' => $hyperpay->getScriptUrl() . "?checkoutId=" . $checkout['id'],
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Failed to initiate recharge',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function checkRechargeStatus(Request $request, $id, \App\Services\HyperpayService $hyperpay)
    {
        try {
            $customer = $request->user();
            $recharge = \App\Models\WalletRecharge::where('id', $id)
                            ->where('customer_id', $customer->id)
                            ->first();

            if (!$recharge) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'Recharge not found'
                ]);
            }

            if ($recharge->status === 'completed') {
                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'data' => ['status' => 'paid']
                ]);
            }

            $result = $hyperpay->getPaymentStatus($recharge->checkout_id);
            $paymentStatus = $result['result']['code'] ?? '';
            $isPaid = preg_match('/^(000\.000\.|000\.100\.1|000\.300\.000|000\.600\.000)/', $paymentStatus);

            if ($isPaid) {
                DB::beginTransaction();
                try {
                    $recharge->status = 'completed';
                    $recharge->save();

                    // Create transaction
                    $lastSequence = Wallet_Transaction::where('wallet_id', $recharge->wallet_id)
                        ->lockForUpdate()
                        ->orderByDesc('sequence')
                        ->first();
                    
                    $sequence = $lastSequence ? $lastSequence->sequence + 1 : 1000001;

                    Wallet_Transaction::create([
                        'user_id' => $customer->id,
                        'amount' => $recharge->amount,
                        'transaction_type' => 'credit',
                        'description' => 'شحن المحفظة عبر ' . ($recharge->payment_method === 'hyperpay_mada' ? 'مدى' : 'البطاقة الائتمانية'),
                        'wallet_id' => $recharge->wallet_id,
                        'status' => 'completed',
                        'sequence' => $sequence,
                    ]);

                    DB::commit();

                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'data' => ['status' => 'paid']
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            $isFailed = preg_match('/^(000\.400\.0|800\.[17]00|100\.39[567]|100\.400|800\.800|100\.380|100\.390|100\.100\.300)/', $paymentStatus);
            if ($isFailed) {
                $recharge->status = 'failed';
                $recharge->save();
                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'data' => ['status' => 'failed']
                ]);
            }

            return response()->json([
                'status' => 200,
                'success' => true,
                'data' => ['status' => 'pending']
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Failed to check status',
                'error' => $e->getMessage()
            ]);
        }
    }
}
