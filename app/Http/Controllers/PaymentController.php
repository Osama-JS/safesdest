<?php

namespace App\Http\Controllers;

use App\Models\Payments;
use App\Models\Task;
use App\Traits\HandlesPaymentFulfillment;
use App\Models\Customs_Clearance;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use App\Services\HyperpayService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use HandlesPaymentFulfillment;

    protected HyperpayService $hyperpay;

    public function __construct(HyperpayService $hyperpay)
    {
        $this->hyperpay = $hyperpay;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UNIFIED INITIATION  (Web + API)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Initiate a payment.
     *
     * Web callers  → must have 'id' (task_id) and be authenticated via web session.
     * Mobile callers → must be authenticated via Sanctum guard 'customer'.
     *
     * Returns JSON in all cases.
     * For HyperPay methods: response includes `payment_url` for the browser widget.
     */
    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'             => 'nullable|integer',
            'clearance_id'   => 'nullable|integer',
            'payment_method' => 'required|in:credit,cash,banking,wallet,hyperpay_visa,hyperpay_mastercard,hyperpay_mada,bank_transfer',
            'purpose'        => 'nullable|in:task_payment,clearance_payment,wallet_deposit',
            'amount'         => 'nullable|numeric|min:1',
            'receipt_number' => 'nullable|string|max:255',
            'receipt_image'  => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
            'note'           => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // ── Resolve owner ─────────────────────────────────────────────────────
        $owner = $this->resolveOwner($request);
        if (!$owner) {
            return response()->json([
                'success' => false,
                'status'  => 2,
                'error'   => __('Unauthenticated')
            ]);
        }

        // ── Resolve subject (task or clearance) ───────────────────────────────
        [$subject, $purpose] = $this->resolveSubject($request);
        $amount  = $request->filled('amount') ? (float)$request->amount : ($subject?->total_price ?? 0);
        $method  = $request->payment_method;

        // Normalize method aliases
        if ($method === 'credit') $method = 'hyperpay_visa';
        if ($method === 'banking') $method = 'bank_transfer';

        // ── Prevent double payment ─────────────────────────────────────────────
        if ($subject && in_array($subject->payment_status ?? '', ['paid', 'completed'])) {
            return response()->json(['success' => false, 'message' => __('This item is already paid')], 400);
        }

        DB::beginTransaction();
        try {
            // ── Wallet payment ─────────────────────────────────────────────────
            if ($method === 'wallet') {
                $result = $this->processWalletPayment($owner, $amount, $subject, $purpose);
                DB::commit();
                return response()->json($result);
            }

            // ── Bank transfer (manual) ─────────────────────────────────────────
            if ($method === 'bank_transfer') {
                $result = $this->processBankTransfer($request, $owner, $amount, $subject, $purpose);
                DB::commit();
                return response()->json($result);
            }

            // ── HyperPay ──────────────────────────────────────────────────────
            $brand    = $this->methodToBrand($method);
            $token    = Str::random(48);
            $callbackUrl = route('payment.callback', ['token' => $token]);

            $customerEmail = $owner->email ?? 'test@safedest.com';
            $customerGivenName = $owner->first_name ?? $owner->name ?? 'Customer';
            $customerSurname = $owner->last_name ?? 'Safedest';
            $merchantTransactionId = uniqid('PAY-');

            $options = [
                'merchantTransactionId' => $merchantTransactionId,
                'customer.email'        => $customerEmail,
                'customer.givenName'    => $customerGivenName,
                'customer.surname'      => $customerSurname,
            ];

            $checkout = $this->hyperpay->createCheckout($amount, $brand, $callbackUrl, $options);

            if (!$checkout || !isset($checkout['id']) ||
                !isset($checkout['result']['code']) || $checkout['result']['code'] !== '000.200.100') {
                Log::error('HyperPay checkout failed', ['response' => $checkout]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => __('Payment gateway error. Please try again.'),
                    'debug'   => $checkout['result']['description'] ?? null,
                ], 502);
            }

            // Create payment record
            $payment = Payments::create([
                'customer_id'           => ($owner instanceof \App\Models\Customer) ? $owner->id : null,
                'owner_type'            => ($owner instanceof \App\Models\Customer) ? 'customer' : 'user',
                'owner_id'              => $owner->id,
                'amount'                => $amount,
                'payment_method'        => $method,
                'purpose'               => $purpose,
                'payment_paid'          => $this->resolvePaid($method, $subject),
                'reference_id'          => $subject?->id,
                'task_id'               => ($purpose === 'task_payment') ? $subject?->id : null,
                'status'                => 'pending',
                'payment_token'         => $token,
                'transaction_reference' => $checkout['id'],
                'gateway_name'          => 'hyperpay',
                'gateway_response'      => json_encode(['integrity' => $checkout['integrity'] ?? null]),
                'expires_at'            => Carbon::now()->addHours(2),
            ]);

            DB::commit();

            $paymentUrl = route('payment.page', ['token' => $token]);

            return response()->json([
                'success'     => true,
                'status'      => 1,             // For backward compatibility (Web JS)
                'hyperpay'    => true,          // For backward compatibility (Web JS)
                'url'         => $paymentUrl,   // For backward compatibility (Web JS)
                'payment_url' => $paymentUrl,
                'payment_id'  => $payment->id,
                'checkout_id' => $checkout['id'],
                'expires_at'  => $payment->expires_at,
                'message'     => __('Redirecting to payment page...'),
                'success_msg' => __('Redirecting to payment page...'), // backward comp.
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('initiatePayment exception', ['msg' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'status'  => 2,
                'error'   => $e->getMessage()
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PAYMENT PAGE  (browser)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Render standalone HyperPay widget page.
     */
    public function showPaymentPage(Request $request, string $token)
    {
        $payment = Payments::where('payment_token', $token)->first();

        if (!$payment) {
            abort(404, __('Payment not found'));
        }

        if ($payment->isExpired()) {
            return redirect()->route('payment.result', ['status' => 'expired', 'token' => $token]);
        }

        if ($payment->isFinished()) {
            $status = in_array($payment->status, ['paid', 'review']) ? 'success' : $payment->status;
            return redirect()->route('payment.result', ['status' => $status, 'token' => $token]);
        }

        $brand       = $this->methodToBrand($payment->payment_method);
        $scriptUrl   = $this->hyperpay->getScriptUrl() . '?checkoutId=' . $payment->transaction_reference;
        $brandsCss   = $this->brandsToBrandCss($brand);
        $isApp       = $request->query('is_app') ? 1 : 0;
        $callbackUrl = route('payment.callback', ['token' => $token, 'is_app' => $isApp]);

        return view('payment.form', compact('payment', 'scriptUrl', 'brandsCss', 'callbackUrl', 'token'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CALLBACK  (after HyperPay redirects back)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Handle HyperPay redirect callback.
     * URL: GET /payment/callback?id={checkoutId}&token={token}
     */
    public function handleCallback(Request $request)
    {
        $checkoutId   = $request->query('id');
        $token        = $request->query('token');
        $resourcePath = $request->query('resourcePath');

        $payment = Payments::where('payment_token', $token)->first()
                ?? Payments::where('transaction_reference', $checkoutId)->first();

        $isApp      = $request->query('is_app') ? 1 : 0;

        if (!$payment) {
            return redirect()->route('payment.result', ['status' => 'failed', 'token' => $token ?? 'unknown', 'is_app' => $isApp])
                ->with('error', __('Payment record not found'));
        }

        // Query status from HyperPay
        $brand  = $this->methodToBrand($payment->payment_method);
        $result = null;

        if ($resourcePath) {
            $result = $this->hyperpay->getPaymentStatusByResourcePath($resourcePath, $brand);
        }

        if (empty($result) && $checkoutId) {
            $result = $this->hyperpay->getPaymentStatus($payment->transaction_reference, $brand);
        }

        $code        = data_get($result, 'result.code', '');
        $description = data_get($result, 'result.description', '');
        $status      = HyperpayService::codeToStatus($code);

        Log::info('Payment Callback', ['token' => $token, 'code' => $code, 'status' => $status]);

        DB::transaction(function () use ($payment, $status, $code, $description, $result) {
            $payment->update([
                'status'           => $status,
                'gateway_code'     => $code,
                'gateway_msg'      => $description,
                'gateway_response' => json_encode($result),
                'processed_at'     => now(),
                'completed_at'     => in_array($status, ['paid', 'review']) ? now() : null,
            ]);

            if (in_array($status, ['paid', 'review'])) {
                $this->fulfillPayment($payment);
            }
        });

        $resultStatus = match ($status) {
            'paid', 'review' => 'success',
            'pending'        => 'pending',
            default          => 'failed',
        };

        return redirect()->route('payment.result', [
            'status' => $resultStatus,
            'token'  => $payment->payment_token,
            'is_app' => $isApp
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RESULT PAGE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Show payment result page.
     */
    public function showResult(Request $request, string $status, string $token)
    {
        $payment = Payments::where('payment_token', $token)->first();

        $isApp = $request->query('is_app') ? true : false;

        return view('payment.result', compact('status', 'payment', 'token', 'isApp'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API STATUS CHECK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return payment status via API (Sanctum).
     * GET /api/customer/payments/{id}/status
     */
    public function getStatus(Request $request, int $id)
    {
        $customer = $request->user();
        $payment  = Payments::where('id', $id)
                            ->where(function ($q) use ($customer) {
                                $q->where('customer_id', $customer->id)
                                  ->orWhere(function ($q2) use ($customer) {
                                      $q2->where('owner_type', 'customer')
                                         ->where('owner_id', $customer->id);
                                  });
                            })
                            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        // For pending HyperPay: re-check with gateway
        if ($payment->status === 'pending' && str_starts_with($payment->payment_method, 'hyperpay')) {
            $brand  = $this->methodToBrand($payment->payment_method);
            $result = $this->hyperpay->getPaymentStatus($payment->transaction_reference, $brand);
            $code   = data_get($result, 'result.code', '');
            if ($code) {
                $status = HyperpayService::codeToStatus($code);
                if ($status !== 'pending') {
                    DB::transaction(function () use ($payment, $status, $code, $result) {
                        $payment->update([
                            'status'       => $status,
                            'gateway_code' => $code,
                            'processed_at' => now(),
                            'completed_at' => in_array($status, ['paid','review']) ? now() : null,
                        ]);
                        if (in_array($status, ['paid', 'review'])) {
                            $this->fulfillPayment($payment);
                        }
                    });
                    $payment->refresh();
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $payment->id,
                'status'         => $payment->status,
                'amount'         => $payment->amount,
                'payment_method' => $payment->payment_method,
                'purpose'        => $payment->purpose,
                'completed_at'   => $payment->completed_at,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve authenticated owner from request (Sanctum or web session).
     */
       /**
     * Resolve authenticated owner from request (Sanctum or web session).
     */
    private function resolveOwner(Request $request)
    {
        // 1. Try to resolve from subject (Task or Clearance)
        if ($request->filled('id')) {
            $task = Task::find($request->id);
            if ($task && $task->customer_id) {
                return $task->customer;
            }
        }
        if ($request->filled('clearance_id')) {
            $clr = Customs_Clearance::find($request->clearance_id);
            if ($clr && $clr->customer_id) {
                return $clr->customer;
            }
        }

        // 2. Fallback to authenticated user/customer
        // Sanctum customer guard
        if ($request->user('sanctum')) {
            return $request->user('sanctum');
        }
        // Web session: customer or user
        if (Auth::guard('customer')->check()) {
            return Auth::guard('customer')->user();
        }
        if (Auth::check()) {
            return Auth::user();
        }
        return null;
    }


    /**
     * Resolve the subject (Task or Customs_Clearance) and purpose.
     */
    private function resolveSubject(Request $request): array
    {
        if ($request->filled('id')) {
            $task = Task::find($request->id);
            return [$task, 'task_payment'];
        }
        if ($request->filled('clearance_id')) {
            $clr = Customs_Clearance::find($request->clearance_id);
            return [$clr, 'clearance_payment'];
        }
        if ($request->filled('purpose') && $request->purpose === 'wallet_deposit') {
            return [null, 'wallet_deposit'];
        }
        return [null, $request->input('purpose', 'task_payment')];
    }

    /**
     * Map payment_method slug to HyperPay brand string.
     */
    private function methodToBrand(string $method): string
    {
        return match ($method) {
            'hyperpay_mada'        => 'MADA',
            'hyperpay_mastercard'  => 'MASTER',
            default                => 'VISA MASTER',
        };
    }

    /**
     * Map brand to CSS class(es) for widget.
     */
    private function brandsToBrandCss(string $brand): string
    {
        return match ($brand) {
            'MADA'   => 'MADA',
            'MASTER' => 'MASTER',
            default  => 'VISA MASTER',
        };
    }

    /**
     * Determine payment_paid value.
     */
    private function resolvePaid(string $method, $subject): string
    {
        if ($method === 'cash' && $subject instanceof Task) return 'just_commission';
        return 'all';
    }



    // ─────────────────────────────────────────────────────────────────────────
    // Wallet & Bank Transfer (inline)
    // ─────────────────────────────────────────────────────────────────────────

    private function processWalletPayment($owner, float $amount, $subject, string $purpose): array
    {
        // 1. Determine target customer whose wallet will be debited
        $customerId = null;
        if ($owner instanceof \App\Models\Customer) {
            $customerId = $owner->id;
        } elseif ($subject && isset($subject->customer_id)) {
            $customerId = $subject->customer_id;
        }

        Log::info($owner);
        if (!$customerId) {
            throw new Exception(__('Customer not found for this payment'));
        }

        $wallet = Wallet::where('user_type', 'customer')
                        ->where('customer_id', $customerId)
                        ->first();

        Log::info($wallet);
        if (!$wallet || !$wallet->status) {
            throw new Exception(__('Wallet is inactive or not found'));
        }

        $newBalance = $wallet->balance - $amount;
        if ($newBalance < -$wallet->debt_ceiling) {
            throw new Exception(__('Insufficient wallet balance'));
        }
        Log::info($newBalance);

        $seq = (Wallet_Transaction::max('sequence') ?? 1000000) + 1;
        Log::info($seq);
        $userId = (Auth::check() && Auth::user() instanceof \App\Models\User) ? Auth::user()->id : null;

        Log::info([
          'amount'           => $amount,
          'transaction_type' => 'debit',
          'description'      => 'Payment: ' . ($subject ? class_basename($subject) . ' #' . $subject->id : $purpose),
          'status'           => 1,
          'maturity_time'    => now()->addDays(3),
          'task_id'          => ($subject instanceof Task) ? $subject->id : null,
          'sequence'         => $seq,
          'user_id'          => $userId,
        ]);
        $wt  = Wallet_Transaction::create([
            'wallet_id'        => $wallet->id,
            'amount'           => $amount,
            'transaction_type' => 'debit',
            'description'      => 'Payment: ' . ($subject ? class_basename($subject) . ' #' . $subject->id : $purpose),
            'status'           => 1,
            'maturity_time'    => now()->addDays(3),
            'task_id'          => ($subject instanceof Task) ? $subject->id : null,
            'sequence'         => $seq,
            'user_id'          => $userId,
        ]);

        Log::info($wt);
        $payment = Payments::create([
            'owner_type'     => ($owner instanceof \App\Models\Customer) ? 'customer' : 'user',
            'owner_id'       => $owner->id,
            'customer_id'    => $customerId,
            'amount'         => $amount,
            'payment_method' => 'wallet',
            'purpose'        => $purpose,
            'reference_id'   => $subject?->id,
            'task_id'        => ($subject instanceof Task) ? $subject->id : null,
            'status'         => 'paid',
            'payment_token'  => Str::random(48),
            'gateway_name'   => 'wallet',
            'completed_at'   => now(),
        ]);

        $subject?->update([
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'payment_id'     => $payment->id,
            'payment_paid'   => 'all',
        ]);
        
        // التسوية للمستثمر إذا كانت المهمة ممولة
        if ($subject instanceof Task) {
            app(\App\Services\InvestorPaymentService::class)->settleTaskInvestment($subject);
        }

        return [
            'success' => __('Payment completed successfully via wallet'),
            'status'  => 1,
            'message' => __('Payment completed successfully via wallet'),
            'success_msg' => __('Payment completed successfully via wallet')
        ];
    }

    private function processBankTransfer(Request $request, $owner, float $amount, $subject, string $purpose): array
    {
        $receiptPath = null;
        if ($request->hasFile('receipt_image')) {
            $receiptPath = $request->file('receipt_image')->store('payments/receipts', 'public');
        }

        $payment = Payments::create([
            'owner_type'     => 'customer',
            'owner_id'       => $owner->id,
            'customer_id'    => $owner->id,
            'amount'         => $amount,
            'payment_method' => 'bank_transfer',
            'purpose'        => $purpose,
            'reference_id'   => $subject?->id,
            'task_id'        => ($subject instanceof Task) ? $subject->id : null,
            'status'         => 'pending',
            'payment_token'  => Str::random(48),
            'gateway_name'   => 'bank_transfer',
            'receipt_image'  => $receiptPath,
            'receipt_number' => $request->receipt_number,
            'description'    => $request->note,
        ]);

        $subject?->update([
            'payment_method' => 'banking',
            'payment_status' => 'pending',
            'payment_id'     => $payment->id,
            'payment_paid'   => 'all',
        ]);

        return [
            'success'    => true,
            'status'     => 1,
            'payment_id' => $payment->id,
            'message'    => __('Bank transfer received. We will verify and notify you shortly.'),
            'success_msg'=> __('Bank transfer received. We will verify and notify you shortly.'),
        ];
    }
}
