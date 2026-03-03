<?php

namespace App\Traits;

use App\Models\Task;
use App\Models\Customs_Clearance;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use App\Models\Payments;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait HandlesPaymentFulfillment
{
    /**
     * Centralized logic to fulfill a payment based on its purpose.
     */
    public function fulfillPayment(Payments $payment)
    {
        Log::info("Fulfilling payment #{$payment->id} for purpose: {$payment->purpose}");

        switch ($payment->purpose) {
            case 'task_payment':
                $this->fulfillTask($payment);
                break;

            case 'clearance_payment':
                $this->fulfillClearance($payment);
                break;

            case 'wallet_deposit':
                $this->fulfillWalletDeposit($payment);
                break;

            default:
                Log::warning("Unknown payment purpose: {$payment->purpose} for payment #{$payment->id}");
                break;
        }

        // Mark as processed if not already
        if (!$payment->processed_at) {
            $payment->update(['processed_at' => now()]);
        }
    }

    private function fulfillTask(Payments $payment)
    {
        $task = Task::find($payment->reference_id);
        if ($task) {
            $task->update([
                'payment_status' => 'completed',
                'payment_method' => $payment->payment_method,
                'payment_id'     => $payment->id,
            ]);
            Log::info("Task #{$task->id} marked as paid via payment #{$payment->id}");
        }
    }

    private function fulfillClearance(Payments $payment)
    {
        $clearance = Customs_Clearance::find($payment->reference_id);
        if ($clearance) {
            $clearance->update([
                'payment_status' => 'completed',
                'payment_method' => $payment->payment_method,
                'payment_id'     => $payment->id,
            ]);
            Log::info("Customs Clearance #{$clearance->id} marked as paid via payment #{$payment->id}");
        }
    }

    private function fulfillWalletDeposit(Payments $payment)
    {
        $wallet = Wallet::where('user_type', 'customer')
            ->where('user_id', $payment->customer_id)
            ->first();

        if ($wallet) {
            $wallet->increment('balance', $payment->amount);

            Wallet_Transaction::create([
                'wallet_id'        => $wallet->id,
                'amount'           => $payment->amount,
                'transaction_type' => 'credit',
                'description'      => 'Wallet deposit via ' . $payment->gateway_name . ' (Ref: ' . $payment->transaction_reference . ')',
                'status'           => 'completed',
                'maturity_time'    => now(),
                'sequence'         => $this->generateTransactionSequence(),
            ]);
            Log::info("Wallet balance incremented for customer #{$payment->customer_id} via payment #{$payment->id}");
        }
    }

    private function generateTransactionSequence()
    {
        $lastTransaction = Wallet_Transaction::orderBy('sequence', 'desc')->first();
        return $lastTransaction ? $lastTransaction->sequence + 1 : 1000001;
    }
}
