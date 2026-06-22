<?php

namespace App\Http\Controllers\Api;

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
                    $this->handleWithdrawalRequest($referenceId, $isSuccess, $payoutId, $failureReason, $amount, $reference);
                    break;
                case 'INV': // InvestorWallet
                    $this->handleInvestorPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount);
                    break;
                case 'UWP': // User Wallet (Commissions)
                    $this->handleUserWalletPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount);
                    break;
                case 'MT': // Manual Transaction (Driver)
                case 'WP': // Wallet Payment (Driver)
                    $this->handleDriverWalletPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount, $reference);
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

    protected function handleWithdrawalRequest($id, $isSuccess, $payoutId, $failureReason, $amount, $reference)
    {
        $payout = \App\Models\HyperpayPayout::where('reference_id', $reference)->first();
        if (!$payout) {
            Log::error("HyperpayPayout for reference {$reference} not found.");
            // Fallback for old transactions
            $withdrawal = WithdrawalRequest::find($id);
            if ($withdrawal && !$isSuccess && $withdrawal->status === 'processing') {
                $withdrawal->update(['status' => 'failed', 'admin_notes' => "Failed via Webhook: " . $failureReason]);
            }
            return;
        }

        $withdrawal = WithdrawalRequest::find($id);

        if ($payout && $payout->status !== 'pending') {
            Log::info("Withdrawal Payout for reference {$reference} is already processed. Skipping to prevent duplication.");
            return;
        }

        if ($withdrawal && $withdrawal->status !== 'processing') {
            Log::info("Withdrawal #{$id} is already processed (Status: {$withdrawal->status}). Skipping.");
            return;
        }

        if ($isSuccess) {
            $payout->update(['status' => 'completed']);
            
            // 1. Create Wallet Transaction Debit ONLY NOW
            $transaction = Wallet_Transaction::create([
                'wallet_id' => $payout->wallet_id,
                'user_id' => 1, // System
                'amount' => $amount > 0 ? $amount : $payout->amount,
                'transaction_type' => 'debit',
                'description' => "سحب نقدي - طلب رقم #{$withdrawal->id}. " . ($payout->transaction_details['admin_notes'] ?? ''),
                'status' => 1,
                'image' => $payout->transaction_details['receipt_image'] ?? null,
                'maturity_time' => now(),
            ]);

            $withdrawal->update([
                'status' => 'completed',
                'wallet_transaction_id' => $transaction->id,
                'admin_notes' => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "Confirmed by Webhook."
            ]);
            
            // Notify Driver
            app(\App\Services\NotificationService::class)->send(
                'driver',
                [$withdrawal->driver_id],
                '✅ تمت الموافقة على طلب السحب',
                "تم صرف مبلغ {$payout->amount} ريال من طلبك رقم #{$withdrawal->id}.",
                '/images/admin-icon.png',
                null,
                "/wallet",
                'withdrawal_approved'
            );
            
            Log::info("Withdrawal #{$id} confirmed successfully via HyperPay Webhook.");
        } else {
            $payout->update([
                'status' => 'failed',
                'failure_reason' => $failureReason
            ]);

            $withdrawal->update([
                'status' => 'failed',
                'admin_notes' => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "Failed via Webhook: " . $failureReason
            ]);
            
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
                'performed_by' => 1, // System
                'source_type' => 'capital'
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

    protected function handleDriverWalletPayout($walletId, $isSuccess, $payoutId, $failureReason, $amount, $reference)
    {
        $payout = \App\Models\HyperpayPayout::where('reference_id', $reference)->first();
        if (!$payout) {
            Log::error("HyperpayPayout for reference {$reference} not found.");
            return;
        }

        if ($payout->status !== 'pending') {
            Log::info("Driver Payout for reference {$reference} is already processed (Status: {$payout->status}). Skipping to prevent duplication.");
            return;
        }

        if ($isSuccess) {
            $payout->update(['status' => 'completed']);
            Log::info("Driver Payout for reference {$reference} confirmed by Webhook. PayoutId: " . $payoutId);
            
            $details = $payout->transaction_details;
            
            if ($payout->payout_type === 'WP') {
                $walletTransactions = \App\Models\Wallet_Transaction::whereIn('id', collect($details['transactions'])->pluck('id'))
                  ->where('wallet_id', $payout->wallet_id)
                  ->get();

                $remainingAmount = $payout->amount;

                $sortedTransactions = collect($details['transactions'])->sortBy(function ($item, $key) {
                    return $key;
                });

                foreach ($sortedTransactions as $transactionData) {
                    if ($remainingAmount <= 0) break;

                    $walletTransaction = $walletTransactions->where('id', $transactionData['id'])->first();
                    if (!$walletTransaction) continue;

                    $originalAmount = $walletTransaction->amount;
                    $paymentAmount = 0;

                    if ($remainingAmount >= $originalAmount) {
                        $paymentAmount = $originalAmount;
                        $remainingAmount -= $originalAmount;

                        $walletTransaction->update(['status' => 1, 'user_id' => 1]);
                        $paymentDescription = "دفع مستحقات سائق (كامل) للمعاملة رقم #{$walletTransaction->sequence}";
                    } elseif ($remainingAmount > 0) {
                        $paymentAmount = $remainingAmount;
                        $remainingTransactionAmount = $originalAmount - $paymentAmount;
                        $remainingAmount = 0;

                        $walletTransaction->update([
                            'status' => 1,
                            'amount' => $paymentAmount,
                            'user_id' => 1
                        ]);

                        \App\Models\Wallet_Transaction::create([
                            'wallet_id' => $walletTransaction->wallet_id,
                            'amount' => $remainingTransactionAmount,
                            'transaction_type' => $walletTransaction->transaction_type,
                            'description' => "المبلغ المتبقي من المعاملة #{$walletTransaction->sequence} - تم دفع {$paymentAmount} من أصل {$originalAmount} ريال",
                            'status' => 0,
                            'user_id' => 1,
                            'maturity_time' => $walletTransaction->maturity_time,
                            'task_id' => $walletTransaction->task_id,
                            'image' => $walletTransaction->image
                        ]);

                        $paymentDescription = "دفع مستحقات سائق (جزئي: {$paymentAmount} من {$originalAmount}) للمعاملة رقم #{$walletTransaction->sequence}";
                    }

                    if ($paymentAmount > 0) {
                        \App\Models\Wallet_Transaction::create([
                            'wallet_id' => $walletTransaction->wallet_id,
                            'amount' => $paymentAmount,
                            'transaction_type' => 'debit',
                            'description' => $paymentDescription . (!empty($details['notes']) ? " - ملاحظات: {$details['notes']}" : ""),
                            'status' => 1,
                            'user_id' => 1,
                            'maturity_time' => now()
                        ]);
                    }
                }

                // Notify Driver
                app(\App\Services\NotificationService::class)->send(
                    'driver',
                    [$payout->driver_id],
                    '✅ تم إيداع مستحقاتك!',
                    "تمت معالجة مبلغ {$payout->amount} ريال وتحويله إلى حسابك بنجاح.",
                    '/images/admin-icon.png',
                    '/images/banner.png',
                    "/wallet",
                    'payment_processed'
                );
            } elseif ($payout->payout_type === 'MT') {
                // Direct Manual Transaction
                \App\Models\Wallet_Transaction::create([
                    'wallet_id' => $payout->wallet_id,
                    'user_id' => 1,
                    'amount' => $payout->amount,
                    'transaction_type' => 'debit',
                    'description' => $details['description'] ?? "سحب مباشر عبر HyperPay",
                    'status' => 1,
                    'maturity_time' => $details['maturity'] ?? now(),
                    'image' => $details['image'] ?? null,
                    'task_id' => $details['task_id'] ?? null,
                ]);

                app(\App\Services\NotificationService::class)->send(
                    'driver',
                    [$payout->driver_id],
                    "تحديث في المحفظة: خصم",
                    "تمت إضافة عملية خصم بقيمة {$payout->amount} ريال في محفظتك.",
                    '/images/admin-icon.png',
                    '/images/banner.png',
                    "/wallet",
                    'wallet_adjustment'
                );
            }
        } else {
            $payout->update([
                'status' => 'failed',
                'failure_reason' => $failureReason
            ]);
            Log::error("Driver Payout for reference {$reference} failed via Webhook. Reason: " . $failureReason);
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
