<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WithdrawalRequest;
use App\Models\Wallet_Transaction;
use App\Models\Wallet;
use App\Models\InvestorWallet;
use App\Models\InvestorWalletTransaction;
use App\Models\UserWallet;
use App\Models\UserWalletTransaction;
use App\Models\Team_Wallet;
use App\Models\Team_Wallet_Transaction;

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
        $amount = $payload['amount'] ?? 0;

        if (!$reference) {
            return response()->json(['message' => 'Reference not found'], 400);
        }

        // Extract Prefix and ID from reference (Format: PREFIX-{id}-{time})
        $parts = explode('-', $reference);
        $prefix = $parts[0] ?? null;
        $referenceId = $parts[1] ?? null;

        if (!$prefix || !$referenceId) {
            return response()->json(['message' => 'Invalid reference format'], 400);
        }

        $isSuccess = ($responseCode === '00000');
        $failureReason = $payload['responseMessage'] ?? 'Unknown Error';

        try {
            switch ($prefix) {
                case 'WD': // WithdrawalRequest
                    $this->handleWithdrawalRequest($referenceId, $isSuccess, $payoutId, $failureReason, $amount);
                    break;
                case 'INV': // InvestorWallet
                    $this->handleInvestorPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount);
                    break;
                case 'UWP': // User Wallet (Commissions)
                    $this->handleUserWalletPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount);
                    break;
                case 'MT': // Manual Transaction (Driver)
                case 'WP': // Wallet Payment (Driver)
                    $this->handleDriverWalletPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount);
                    break;
                case 'TWM': // Team Wallet Manual
                case 'TWP': // Team Wallet Payment
                    $this->handleTeamWalletPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount);
                    break;
                default:
                    Log::warning("Unknown HyperPay Payout Prefix: {$prefix} for Reference: {$reference}");
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Error processing HyperPay Webhook for {$reference}: " . $e->getMessage());
            return response()->json(['message' => 'Internal server error processing webhook'], 500);
        }

        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }

    protected function handleWithdrawalRequest($id, $isSuccess, $payoutId, $failureReason, $amount)
    {
        $withdrawal = WithdrawalRequest::find($id);
        if (!$withdrawal) {
            Log::error("Withdrawal #{$id} not found for HyperPay Webhook.");
            return;
        }

        if ($isSuccess) {
            $withdrawal->update([
                'status' => 'completed',
                'admin_notes' => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "Confirmed by Webhook. PayoutId: " . $payoutId
            ]);
            Log::info("Withdrawal #{$id} confirmed successfully via HyperPay Webhook.");
        } else {
            $withdrawal->update([
                'status' => 'failed',
                'admin_notes' => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "Failed via Webhook: " . $failureReason
            ]);

            // Refund logic
            if ($withdrawal->wallet_transaction_id) {
                $transaction = Wallet_Transaction::find($withdrawal->wallet_transaction_id);
                if ($transaction && $transaction->transaction_type === 'debit') {
                    Wallet_Transaction::create([
                        'wallet_id' => $withdrawal->wallet_id,
                        'user_id' => $withdrawal->driver_id ?? null,
                        'amount' => $amount > 0 ? $amount : $withdrawal->amount_paid,
                        'transaction_type' => 'credit',
                        'description' => "استرداد مبالغ - فشل عملية الدفع عبر HyperPay لطلب رقم #{$withdrawal->id}",
                        'status' => 1,
                        'maturity_time' => now(),
                    ]);
                    $transaction->update([
                        'description' => $transaction->description . " (FAILED & REFUNDED)"
                    ]);
                }
            }
            Log::error("Withdrawal #{$id} failed via Webhook. Reason: " . $failureReason);
        }
    }

    protected function handleInvestorPayout($walletId, $isSuccess, $payoutId, $failureReason, $amount)
    {
        $wallet = InvestorWallet::find($walletId);
        if (!$wallet) {
            Log::error("InvestorWallet #{$walletId} not found for HyperPay Webhook.");
            return;
        }

        if ($isSuccess) {
            Log::info("Investor Payout for Wallet #{$walletId} confirmed by Webhook. PayoutId: " . $payoutId);
        } else {
            InvestorWalletTransaction::create([
                'investor_wallet_id' => $wallet->id,
                'amount' => $amount > 0 ? $amount : 0,
                'transaction_type' => 'credit',
                'description' => "استرداد مبالغ - فشل تحويل HyperPay (السبب: {$failureReason})",
                'performed_by' => 1 // System
            ]);
            Log::error("Investor Payout for Wallet #{$walletId} failed via Webhook and was refunded. Reason: " . $failureReason);
        }
    }

    protected function handleUserWalletPayout($walletId, $isSuccess, $payoutId, $failureReason, $amount)
    {
        $wallet = UserWallet::find($walletId);
        if (!$wallet) {
            Log::error("User Wallet (Commissions) #{$walletId} not found for HyperPay Webhook.");
            return;
        }

        if ($isSuccess) {
            Log::info("User Wallet Payout for Wallet #{$walletId} confirmed by Webhook. PayoutId: " . $payoutId);
        } else {
            UserWalletTransaction::create([
                'user_wallet_id' => $wallet->id,
                'user_id' => 1, // System
                'amount' => $amount > 0 ? $amount : 0,
                'transaction_type' => 'credit',
                'description' => "استرداد مبالغ - فشل تحويل HyperPay (السبب: {$failureReason})",
            ]);
            
            // Adjust balance correctly
            $wallet->balance += ($amount > 0 ? $amount : 0);
            $wallet->save();

            Log::error("User Wallet Payout for Wallet #{$walletId} failed via Webhook and was refunded. Reason: " . $failureReason);
        }
    }

    protected function handleDriverWalletPayout($walletId, $isSuccess, $payoutId, $failureReason, $amount)
    {
        $wallet = Wallet::find($walletId);
        if (!$wallet) {
            Log::error("Driver Wallet #{$walletId} not found for HyperPay Webhook.");
            return;
        }

        if ($isSuccess) {
            Log::info("Driver Payout for Wallet #{$walletId} confirmed by Webhook. PayoutId: " . $payoutId);
        } else {
            Wallet_Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id ?? $wallet->driver_id ?? null,
                'amount' => $amount > 0 ? $amount : 0,
                'transaction_type' => 'credit',
                'description' => "استرداد مبالغ - فشل تحويل HyperPay للسائق (السبب: {$failureReason})",
                'status' => 1,
                'maturity_time' => now(),
            ]);
            Log::error("Driver Payout for Wallet #{$walletId} failed via Webhook and was refunded. Reason: " . $failureReason);
        }
    }

    protected function handleTeamWalletPayout($walletId, $isSuccess, $payoutId, $failureReason, $amount)
    {
        $wallet = Team_Wallet::find($walletId);
        if (!$wallet) {
            Log::error("Team Wallet #{$walletId} not found for HyperPay Webhook.");
            return;
        }

        if ($isSuccess) {
            Log::info("Team Payout for Wallet #{$walletId} confirmed by Webhook. PayoutId: " . $payoutId);
        } else {
            Team_Wallet_Transaction::create([
                'team_wallet_id' => $wallet->id,
                'user_id' => 1, // System
                'amount' => $amount > 0 ? $amount : 0,
                'transaction_type' => 'credit',
                'description' => "استرداد مبالغ - فشل تحويل HyperPay للفريق (السبب: {$failureReason})",
                'status' => 1,
                'maturity_time' => now(),
            ]);
            Log::error("Team Payout for Wallet #{$walletId} failed via Webhook and was refunded. Reason: " . $failureReason);
        }
    }
}
