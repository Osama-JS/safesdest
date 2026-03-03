<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\HyperpayService;
use App\Models\Payments;
use App\Traits\HandlesPaymentFulfillment;
use Carbon\Carbon;
use DB;

class HyperPayWebhookController extends Controller
{
    use HandlesPaymentFulfillment;

    protected $hyperpay;

    public function __construct(HyperpayService $hyperpay)
    {
        $this->hyperpay = $hyperpay;
    }

    /**
     * Handle S2S notification from HyperPay.
     */
    public function handleWebhook(Request $request)
    {
        $checkoutId = $request->input('id');
        Log::info('HyperPay Webhook Received', ['checkout_id' => $checkoutId, 'payload' => $request->all()]);

        if (!$checkoutId) {
            return response()->json(['message' => 'Missing ID'], 400);
        }

        // 1. Find the payment record (Unified Payments table)
        // Note: We prioritize finding by transaction_reference (checkoutId)
        $payment = Payments::where('transaction_reference', $checkoutId)->first();

        // Fallback: If not in Payments table, check old tables if legacy support is still needed?
        // For now, focus on the new Unified system.
        if (!$payment) {
            Log::warning('HyperPay Webhook: Payment record not found for ' . $checkoutId);
            return response()->json(['message' => 'Payment record not found'], 404);
        }

        // Skip if already finished
        if ($payment->isFinished()) {
            return response()->json(['message' => 'Payment already finished']);
        }

        try {
            // 2. Refresh status from HyperPay for security
            $result = $this->hyperpay->getPaymentStatus($checkoutId);
            if (!$result || !isset($result['result']['code'])) {
                throw new \Exception('Failed to fetch status from HyperPay');
            }

            $code   = $result['result']['code'];
            $status = $this->hyperpay->codeToStatus($code);

            DB::beginTransaction();

            $payment->update([
                'status'         => $status,
                'gateway_code'   => $code,
                'gateway_msg'    => $result['result']['description'] ?? 'Webhook update',
                'processed_at'   => now(),
                'completed_at'   => in_array($status, ['paid', 'review']) ? now() : null,
            ]);

            // 3. Fulfill if successful
            if (in_array($status, ['paid', 'review'])) {
                $this->fulfillPayment($payment);
                Log::info("HyperPay Webhook: Payment #{$payment->id} fulfilled.");
            }

            DB::commit();
            return response()->json(['message' => 'Webhook processed']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HyperPay Webhook Error', ['msg' => $e->getMessage(), 'id' => $checkoutId]);
            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
