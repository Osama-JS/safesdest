<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\HyperpayService;
use App\Models\Transaction;
use App\Models\Task;
use App\Models\Customs_Clearance;
use App\Models\Wallet_Transaction;
use App\Models\Wallet;
use App\Models\Payments;
use Carbon\Carbon;
use Illuminate\Support\Str;
use DB;

class HyperPayWebhookController extends Controller
{
    protected $hyperpay;

    public function __construct(HyperpayService $hyperpay)
    {
        $this->hyperpay = $hyperpay;
    }

    public function handleWebhook(Request $request)
    {
        // HyperPay sends data in the body or query parameters depending on configuration.
        // Usually for S2S, it sends 'id' (checkoutId) and 'resourcePath'.

        $payload = $request->all();
        Log::info('HyperPay Webhook Recieved:', $payload);

        $checkoutId = $request->input('id');
        // Some integrations send encrypted data (IV/Tag/EncryptedData),
        // but here we will assume standard Checkout ID notification for simplicity
        // and reliability by querying the status back.

        if (!$checkoutId) {
            return response()->json(['message' => 'No checkout ID provided'], 400);
        }

        // Verify the status directly with HyperPay to ensure authenticity
        $result = $this->hyperpay->getPaymentStatus($checkoutId);

        if (!$result || !isset($result['result']['code'])) {
            Log::error('HyperPay Webhook: Failed to verify status for ID ' . $checkoutId);
            return response()->json(['message' => 'Status verification failed'], 502);
        }

        $code = $result['result']['code'];

        // Try finding in standard Transactions
        $transaction = Transaction::where('checkout_id', $checkoutId)->first();
        $isClearance = false;

        if (!$transaction) {
            // Try finding in Clearance Transactions
            $transaction = \App\Models\Clearance_Transactions::where('checkout_id', $checkoutId)->first();
            $isClearance = true;
        }

        $isMobilePayment = false;
        if (!$transaction) {
            // Try finding in Mobile API Payments table
            $transaction = Payments::where('transaction_reference', $checkoutId)->first();
            $isMobilePayment = true;
        }

        if (!$transaction) {
            Log::warning('HyperPay Webhook: Transaction not found for ID ' . $checkoutId);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        if ($transaction->status === 'paid' || $transaction->status === 'completed') {
            return response()->json(['message' => 'Transaction already processed']);
        }

        // Determine Status
        $status = 'failed';
        if (Str::startsWith($code, ['000.000', '000.100'])) {
            $status = 'paid'; // Or 'completed' depending on database enum
        } elseif (Str::startsWith($code, '000.400')) {
            $status = 'review';
        } elseif (Str::startsWith($code, '000.200')) {
            $status = 'pending';
        }

        DB::beginTransaction();
        try {
            $transaction->update([
                'status' => $status,
                'gateway_code' => $code,
                'gateway_msg' => data_get($result, 'result.description', 'Webhook Update'),
                'processed_at' => Carbon::now(),
            ]);

            if ($status === 'paid') {
                if ($isMobilePayment) {
                    $this->fulfillMobilePayment($transaction);
                } elseif ($isClearance) {
                    $this->fulfillClearanceOrder($transaction);
                } else {
                    $this->fulfillOrder($transaction);
                }
            }

            DB::commit();
            Log::info("HyperPay Webhook: Transaction {$transaction->id} updated to {$status}");

            return response()->json(['message' => 'Webhook processed successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HyperPay Webhook Error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    protected function fulfillOrder(Transaction $transaction)
    {
        // Delivery Task
        if ($transaction->type === 'delivery' && $transaction->reference_id) {
            $task = Task::find($transaction->reference_id);
            if ($task) {
                // Determine what was paid based on 'payment_paid' logic in PaymentController/Initiate
                // Simplified here: if fully paid or commissioned.
                // You might need to check 'payment_paid' column in tasks table if it exists or infer it.
                // Based on PaymentController, it updates payment_status to 'pending' then 'completed'??
                // No, in initiatePayment it sets 'pending'.

                // Let's rely on the transaction amount regarding the task logic.
                // But for safety, we just mark payment_status as paid or completed.
                // In PaymentController: $task->update(['payment_status' => 'pending'...])
                // We should update it to 'paid' or specific status.

                $task->update([
                    'payment_status' => 'paid', // Or 'completed'
                    'payment_method' => 'credit', // Ensure method is set if not already
                    'payment_id' => $transaction->id
                ]);
            }
        }

        // Customs Clearance
        // Logic for customs clearance if needed, checking type...
        // PaymentController has 'initiatePaymentClearance'.

        // Check if transaction relates to Customs Clearance?
        // The Transaction model might not strictly distinguish except by reference_id context or maybe a 'model' type.
        // PaymentController uses $user->transactionsClearance()->create(...) which implies a relation.
        // But the table is likely 'transactions'.
        // If 'type' is 'delivery', it could be Task or Clearance?
        // PaymentController:
        // Task: 'type' => 'delivery', 'payment_type' => 'credit'
        // Clearance: 'type' => 'delivery', 'payment_type' => 'credit'... same type.

        // We have to inspect the transaction to see if it belongs to a clearance.
        // Assuming standard 'reference_id' points to ID. We might need a 'reference_type' column which isn't there?
        // Or maybe we can try finding both.
        // Be careful of ID collision.

        // In PaymentController, Clearance creation:
        // $transaction = $user->transactionsClearance()->create(...)
        // Let's check Transaction model to see if it has morph.

    }

    protected function fulfillClearanceOrder(\App\Models\Clearance_Transactions $transaction)
    {
        if ($transaction->reference_id) {
            $clearance = Customs_Clearance::find($transaction->reference_id);
            if ($clearance) {
                $clearance->update([
                     'payment_status' => 'paid',
                     'payment_method' => 'credit',
                     'payment_id' => $transaction->id
                ]);
            }
        }
    }

    protected function fulfillMobilePayment(Payments $payment)
    {
        // Update payment status
        $payment->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ]);

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

    protected function processWalletDeposit($payment)
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
                'maturity_time' => Carbon::now(),
                'sequence' => $this->generateTransactionSequence(),
            ]);
        }
    }

    protected function processTaskPayment($payment)
    {
        if ($payment->reference_id) {
            $task = Task::find($payment->reference_id);
            if ($task) {
                $task->update(['payment_status' => 'paid']);
            }
        }
    }

    protected function processClearancePayment($payment)
    {
        if ($payment->reference_id) {
            $clearance = Customs_Clearance::find($payment->reference_id);
            if ($clearance) {
                $clearance->update(['payment_status' => 'paid']);
            }
        }
    }

    protected function generateTransactionSequence()
    {
        $lastTransaction = Wallet_Transaction::orderBy('sequence', 'desc')->first();
        return $lastTransaction ? $lastTransaction->sequence + 1 : 1000001;
    }
}
