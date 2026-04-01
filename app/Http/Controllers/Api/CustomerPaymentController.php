<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use App\Models\Task;
use App\Models\Customs_Clearance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use App\Services\HyperpayService;

class CustomerPaymentController extends Controller
{
    protected HyperpayService $hyperpay;

    public function __construct(HyperpayService $hyperpay)
    {
        $this->hyperpay = $hyperpay;
    }
    /**
     * Get available payment methods
     */
    public function getPaymentMethods(Request $request)
    {
        try {
            $paymentMethods = [
                [
                    'id' => 'hyperpay_mada',
                    'name' => 'Mada',
                    'type' => 'hyperpay',
                    'icon' => 'mada',
                    'enabled' => true,
                    'fees' => 0,
                    'description' => 'Pay with Mada card',
                ],
                [
                    'id' => 'hyperpay_visa',
                    'name' => 'Visa Card',
                    'type' => 'hyperpay',
                    'icon' => 'visa',
                    'enabled' => true,
                    'fees' => 0,
                    'description' => 'Pay with Visa credit/debit card',
                ],
                [
                    'id' => 'hyperpay_mastercard',
                    'name' => 'Mastercard',
                    'type' => 'hyperpay',
                    'icon' => 'mastercard',
                    'enabled' => true,
                    'fees' => 0,
                    'description' => 'Pay with Mastercard credit/debit card',
                ],
                [
                    'id' => 'wallet',
                    'name' => 'Wallet Balance',
                    'type' => 'wallet',
                    'icon' => 'wallet',
                    'enabled' => true,
                    'fees' => 0,
                    'description' => 'Pay from your wallet balance',
                ],
                [
                    'id' => 'bank_transfer',
                    'name' => 'Bank Transfer',
                    'type' => 'bank_transfer',
                    'icon' => 'bank',
                    'enabled' => true,
                    'fees' => 0,
                    'description' => 'Pay via bank transfer',
                ],
            ];

            // Get customer wallet balance
            $customer = $request->user();
            $wallet = Wallet::where('user_type', 'customer')
                           ->where('user_id', $customer->id)
                           ->first();

            $walletBalance = $wallet ? $wallet->balance : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_methods' => $paymentMethods,
                    'wallet_balance' => $walletBalance,
                    'currency' => 'SAR',
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate payment process
     */
    public function initiatePayment(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'payment_method' => 'required|string|in:hyperpay_visa,hyperpay_mastercard,hyperpay_mada,wallet,bank_transfer',
                'purpose' => 'required|string|in:wallet_deposit,task_payment,clearance_payment',
                'reference_id' => 'nullable|integer',
                'description' => 'nullable|string|max:255',
                'return_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate reference based on purpose
            if (in_array($request->purpose, ['task_payment', 'clearance_payment']) && !$request->reference_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reference ID is required for this payment purpose'
                ], 422);
            }

            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create([
                'customer_id' => $customer->id,
                'amount' => $request->amount,
                'currency' => 'SAR',
                'payment_method' => $request->payment_method,
                'purpose' => $request->purpose,
                'reference_id' => $request->reference_id,
                'description' => $request->description,
                'status' => 'pending',
                'payment_reference' => $this->generatePaymentReference(),
                'return_url' => $request->return_url,
            ]);

            // Handle different payment methods
            $response = match($request->payment_method) {
                'wallet' => $this->processWalletPayment($payment),
                'bank_transfer' => $this->processBankTransferPayment($payment),
                default => $this->processHyperPayPayment($payment)
            };

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => array_merge([
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                ], $response)
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $payment = Payment::where('id', $id)
                             ->where('customer_id', $customer->id)
                             ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // For HyperPay payments, check status with gateway
            if (str_starts_with($payment->payment_method, 'hyperpay_') && $payment->status === 'pending') {
                $this->checkHyperPayStatus($payment);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                    'purpose' => $payment->purpose,
                    'status' => $payment->status,
                    'gateway_reference' => $payment->gateway_reference,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                    'completed_at' => $payment->completed_at,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm payment (for manual confirmation)
     */
    public function confirmPayment(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $payment = Payment::where('id', $id)
                             ->where('customer_id', $customer->id)
                             ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            if ($payment->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be confirmed in current status'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'confirmation_code' => 'nullable|string',
                'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Handle receipt upload
            $receiptPath = null;
            if ($request->hasFile('receipt_image')) {
                $file = $request->file('receipt_image');
                $receiptPath = $file->store('payments/receipts', 'public');
            }

            // Update payment
            $payment->update([
                'status' => 'completed',
                'gateway_reference' => $request->confirmation_code,
                'receipt_image' => $receiptPath,
                'completed_at' => now(),
            ]);

            // Process payment completion
            $this->processPaymentCompletion($payment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel payment
     */
    public function cancelPayment(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $payment = Payment::where('id', $id)
                             ->where('customer_id', $customer->id)
                             ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            if (!in_array($payment->status, ['pending', 'processing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be canceled in current status'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Update payment status
            $payment->update([
                'status' => 'canceled',
                'cancellation_reason' => $request->reason,
                'canceled_at' => now(),
            ]);

            // Reverse any wallet deductions if applicable
            if ($payment->payment_method === 'wallet') {
                $wallet = Wallet::where('user_type', 'customer')
                               ->where('user_id', $customer->id)
                               ->first();

                if ($wallet) {
                    $wallet->increment('balance', $payment->amount);

                    // Create reversal transaction
                    Wallet_Transaction::create([
                        'wallet_id' => $wallet->id,
                        'amount' => $payment->amount,
                        'transaction_type' => 'credit',
                        'description' => 'Payment cancellation refund - ' . $payment->payment_reference,
                        'status' => 'completed',
                        'maturity_time' => now(),
                        'sequence' => $this->generateTransactionSequence(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment canceled successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate payment reference
     */
    private function generatePaymentReference()
    {
        return 'PAY-' . strtoupper(Str::random(8)) . '-' . time();
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $customer = $request->user();

            $query = Payment::where('customer_id', $customer->id);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->filled('purpose')) {
                $query->where('purpose', $request->purpose);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('payment_reference', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('gateway_reference', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            $paymentsData = $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                    'purpose' => $payment->purpose,
                    'status' => $payment->status,
                    'description' => $payment->description,
                    'gateway_reference' => $payment->gateway_reference,
                    'created_at' => $payment->created_at,
                    'completed_at' => $payment->completed_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $paymentsData,
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'last_page' => $payments->lastPage(),
                        'per_page' => $payments->perPage(),
                        'total' => $payments->total(),
                        'from' => $payments->firstItem(),
                        'to' => $payments->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment receipt
     */
    public function getReceipt(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $payment = Payment::where('id', $id)
                             ->where('customer_id', $customer->id)
                             ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            if ($payment->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Receipt not available for incomplete payments'
                ], 400);
            }

            $receiptData = [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'customer' => [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ],
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'purpose' => $payment->purpose,
                'description' => $payment->description,
                'gateway_reference' => $payment->gateway_reference,
                'payment_date' => $payment->completed_at,
                'receipt_image' => $payment->receipt_image ? asset('storage/' . $payment->receipt_image) : null,
            ];

            return response()->json([
                'success' => true,
                'data' => $receiptData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process wallet payment
     */
    private function processWalletPayment($payment)
    {
        $wallet = Wallet::where('user_type', 'customer')
                       ->where('user_id', $payment->customer_id)
                       ->first();

        if (!$wallet || $wallet->balance < $payment->amount) {
            throw new Exception('Insufficient wallet balance');
        }

        // Deduct from wallet
        $wallet->decrement('balance', $payment->amount);

        // Create transaction
        Wallet_Transaction::create([
            'wallet_id' => $wallet->id,
            'amount' => $payment->amount,
            'transaction_type' => 'debit',
            'description' => $payment->description ?: 'Payment - ' . $payment->payment_reference,
            'status' => 'completed',
            'maturity_time' => now(),
            'sequence' => $this->generateTransactionSequence(),
        ]);

        // Update payment
        $payment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Process completion
        $this->processPaymentCompletion($payment);

        return [
            'requires_action' => false,
            'payment_completed' => true,
        ];
    }

    /**
     * Process bank transfer payment
     */
    private function processBankTransferPayment($payment)
    {
        // Bank transfer requires manual confirmation
        return [
            'requires_action' => true,
            'action_type' => 'bank_transfer',
            'bank_details' => [
                'bank_name' => 'Saudi National Bank',
                'account_number' => '1234567890',
                'iban' => 'SA1234567890123456789012',
                'account_name' => 'SafeDest Company',
            ],
            'instructions' => 'Please transfer the amount to the provided bank account and upload the receipt.',
        ];
    }

    /**
     * Process HyperPay payment
     */
    private function processHyperPayPayment($payment)
    {
        $customer = Customer::find($payment->customer_id);
        $brand = match ($payment->payment_method) {
            'hyperpay_mada' => 'MADA',
            'hyperpay_mastercard' => 'MASTER',
            default => 'VISA MASTER',
        };

        $options = [
            'merchantTransactionId' => $payment->payment_reference ?? uniqid('PAY-'),
            'customer.email'        => $customer->email ?? 'test@safedest.com',
            'customer.givenName'    => $customer->first_name ?? $customer->name ?? 'Customer',
            'customer.surname'      => $customer->last_name ?? 'User',
        ];

        $checkout = $this->hyperpay->createCheckout($payment->amount, $brand, '', $options);

        if (!$checkout || !isset($checkout['id'])) {
            throw new Exception('Failed to create HyperPay checkout');
        }

        // Save checkout ID (transaction_reference in payments table)
        $payment->update([
            'transaction_reference' => $checkout['id'],
            'gateway_name' => 'hyperpay',
            'gateway_response' => json_encode(['integrity' => $checkout['integrity'] ?? null])
        ]);

        return [
            'requires_action' => true,
            'action_type' => 'redirect',
            'payment_url' => $this->hyperpay->getScriptUrl() . "?checkoutId=" . $checkout['id'],
            'checkout_id' => $checkout['id'],
        ];
    }

    private function checkHyperPayStatus($payment)
    {
        $checkoutId = $payment->transaction_reference;
        if (!$checkoutId) return;

        $result = $this->hyperpay->getPaymentStatus($checkoutId);

        if (!$result || !isset($result['result']['code'])) {
            return;
        }

        $code = $result['result']['code'];

        if (Str::startsWith($code, ['000.000', '000.100'])) {
            // Update payment record
            $payment->update([
                'status' => 'completed',
                'gateway_reference' => $result['id'] ?? null,
                'gateway_response' => json_encode($result),
                'completed_at' => Carbon::now(),
            ]);

            // Process completion fulfillment
            $this->processPaymentCompletion($payment);
        } elseif (Str::startsWith($code, '000.400')) {
            $payment->update(['status' => 'review']);
        } elseif (!Str::startsWith($code, '000.200')) {
             $payment->update(['status' => 'failed']);
        }
    }

    /**
     * Process payment completion
     */
    private function processPaymentCompletion($payment)
    {
        switch ($payment->purpose) {
            case 'wallet_deposit':
                $this->processWalletDeposit($payment);
                break;
            case 'task_payment':
                $this->processTaskPayment($payment);
                break;
            case 'clearance_payment':
                $this->processClearancePayment($payment);
                break;
        }
    }

    /**
     * Process wallet deposit
     */
    private function processWalletDeposit($payment)
    {
        $wallet = Wallet::where('user_type', 'customer')
                       ->where('user_id', $payment->customer_id)
                       ->first();

        if ($wallet) {
            $wallet->increment('balance', $payment->amount);

            Wallet_Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $payment->amount,
                'transaction_type' => 'credit',
                'description' => 'Wallet deposit - ' . $payment->payment_reference,
                'status' => 'completed',
                'maturity_time' => now(),
                'sequence' => $this->generateTransactionSequence(),
            ]);
        }
    }

    /**
     * Process task payment
     */
    private function processTaskPayment($payment)
    {
        if ($payment->reference_id) {
            $task = Task::find($payment->reference_id);
            if ($task) {
                $task->update(['payment_status' => 'paid']);
            }
        }
    }

    /**
     * Process clearance payment
     */
    private function processClearancePayment($payment)
    {
        if ($payment->reference_id) {
            $clearance = Customs_Clearance::find($payment->reference_id);
            if ($clearance) {
                $clearance->update(['payment_status' => 'paid']);
            }
        }
    }

    /**
     * Generate transaction sequence
     */
    private function generateTransactionSequence()
    {
        $lastTransaction = Wallet_Transaction::orderBy('sequence', 'desc')->first();
        return $lastTransaction ? $lastTransaction->sequence + 1 : 1000001;
    }
}
