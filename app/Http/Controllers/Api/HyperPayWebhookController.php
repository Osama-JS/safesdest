<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WithdrawalRequest;
use App\Models\Wallet_Transaction;

class HyperPayWebhookController extends Controller
{
    /**
     * Handle incoming HyperPay Payout Webhook
     */
    public function handlePayout(Request $request)
    {
        Log::info('HyperPay Webhook Received:', $request->all());

        $payload = $request->all();
        $reference = $payload['payoutReference'] ?? null;
        $responseCode = $payload['responseCode'] ?? null;
        $payoutId = $payload['payoutId'] ?? 'N/A';

        if (!$reference) {
            return response()->json(['message' => 'Reference not found'], 400);
        }

        // Extract Withdrawal ID from reference (Format: WD-{id}-{time})
        $parts = explode('-', $reference);
        $withdrawalId = $parts[1] ?? null;

        if (!$withdrawalId) {
            return response()->json(['message' => 'Invalid reference format'], 400);
        }

        $withdrawal = WithdrawalRequest::find($withdrawalId);

        if (!$withdrawal) {
            Log::error("Withdrawal #{$withdrawalId} not found for HyperPay Webhook.");
            return response()->json(['message' => 'Withdrawal not found'], 404);
        }

        // responseCode "00000" means Success
        if ($responseCode === '00000') {
            $withdrawal->update([
                'status' => 'completed', // Already set in controller, but ensures sync
                'admin_notes' => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "Confirmed by Webhook. PayoutId: " . $payoutId
            ]);
            
            Log::info("Withdrawal #{$withdrawalId} confirmed successfully via HyperPay Webhook.");
        } else {
            // If it fails, we might need to refund or mark as failed
            $withdrawal->update([
                'status' => 'failed',
                'admin_notes' => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "Failed via Webhook: " . ($payload['responseMessage'] ?? 'Unknown Error')
            ]);

            // Optional: Logic to refund the wallet if it was already deducted
            $this->refundWallet($withdrawal);

            Log::error("Withdrawal #{$withdrawalId} failed via HyperPay Webhook. Reason: " . ($payload['responseMessage'] ?? 'Unknown'));
        }

        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }

    /**
     * Refund wallet if payout failed
     */
    protected function refundWallet($withdrawal)
    {
        try {
            if ($withdrawal->wallet_transaction_id) {
                $transaction = Wallet_Transaction::find($withdrawal->wallet_transaction_id);
                if ($transaction && $transaction->transaction_type === 'debit') {
                    // Create a credit transaction as a refund
                    Wallet_Transaction::create([
                        'wallet_id' => $withdrawal->wallet_id,
                        'user_id' => $withdrawal->driver_id,
                        'amount' => $withdrawal->amount_paid,
                        'transaction_type' => 'credit',
                        'description' => "استرداد مبالغ - فشل عملية الدفع عبر HyperPay لطلب رقم #{$withdrawal->id}",
                        'status' => 1,
                        'maturity_time' => now(),
                    ]);
                    
                    // Update original transaction description
                    $transaction->update([
                        'description' => $transaction->description . " (FALIED & REFUNDED)"
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Refund failed for Withdrawal #{$withdrawal->id}: " . $e->getMessage());
        }
    }
}
