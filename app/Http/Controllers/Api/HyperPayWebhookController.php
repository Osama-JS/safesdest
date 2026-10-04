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
                    $this->handleWithdrawalRequest($referenceId, $isSuccess, $payoutId, $failureReason, $amount, $reference, $payload);
                    break;
                case 'INV': // InvestorWallet
                    $this->handleInvestorPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount, $payload);
                    break;
                case 'IPW': // Investor Payout Wallet (Commissions)
                case 'IWD': // Investor Withdrawal Request Debit
                case 'UWP': // User Wallet (Commissions)
                    $this->handleInvestorCommissionPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount, $reference, $payload);
                    break;
                case 'MT': // Manual Transaction (Driver)
                case 'WP': // Wallet Payment (Driver)
                    $this->handleDriverWalletPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount, $reference, $payload);
                    break;
                case 'TPW': // Team Payout
                case 'TWM': // Team Wallet Manual
                case 'TWP': // Team Wallet Payment
                    $this->handleTeamWalletPayout($referenceId, $isSuccess, $payoutId, $failureReason, $amount, $reference, $payload);
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

    protected function handleWithdrawalRequest($id, $isSuccess, $payoutId, $failureReason, $amount, $reference, $payload = [])
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

        if ($payout && !in_array($payout->status, ['pending', 'processing'])) {
            Log::info("Withdrawal Payout for reference {$reference} is already processed. Skipping to prevent duplication.");
            return;
        }

        if ($withdrawal && $withdrawal->status !== 'processing') {
            Log::info("Withdrawal #{$id} is already processed (Status: {$withdrawal->status}). Skipping.");
            return;
        }

        if ($isSuccess) {
            $payout->update([
                'status' => 'completed',
                'webhook_payload' => !empty($payload) ? $payload : null
            ]);
            
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
                'failure_reason' => $failureReason,
                'webhook_payload' => !empty($payload) ? $payload : null
            ]);

            $withdrawal->update([
                'status' => 'failed',
                'admin_notes' => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "Failed via Webhook: " . $failureReason
            ]);
            
            Log::error("Withdrawal #{$id} failed via Webhook. Reason: " . $failureReason);
        }
    }

    protected function handleInvestorPayout($walletId, $isSuccess, $payoutId, $failureReason, $amount, $payload = [])
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

    protected function handleInvestorCommissionPayout($walletId, $isSuccess, $payoutId, $failureReason, $amount, $reference, $payload = [])
    {
        $payout = \App\Models\HyperpayPayout::where('reference_id', $reference)->first();
        if (!$payout) {
            Log::error("HyperpayPayout for investor reference {$reference} not found.");
            // Fallback: check if wallet exists directly
            $wallet = UserWallet::find($walletId);
            if (!$wallet) {
                Log::error("User Wallet #{$walletId} not found for HyperPay Webhook.");
            }
            return;
        }

        if (!in_array($payout->status, ['pending', 'processing'])) {
            Log::info("Investor Payout for reference {$reference} is already processed (Status: {$payout->status}). Skipping to prevent duplication.");
            return;
        }

        $wallet = $payout->userWallet ?? UserWallet::find($payout->user_wallet_id ?? $walletId);
        if (!$wallet) {
            Log::error("User Wallet for payout #{$payout->id} not found.");
            return;
        }

        $user = $payout->user ?? $payout->investor ?? $wallet->user;
        $details = $payout->transaction_details ?? [];

        if ($isSuccess) {
            $payout->update([
                'status' => 'completed',
                'webhook_payload' => !empty($payload) ? $payload : null
            ]);
            Log::info("Investor Payout for reference {$reference} confirmed by Webhook. PayoutId: " . $payoutId);

            $bankInfo = "البنك: " . ($details['bank_name'] ?? ($user?->bank_name ?? 'غير محدد')) . " | الآيبان: " . ($details['iban'] ?? ($user?->iban_number ?? 'غير محدد')) . " | رقم العملية: {$payoutId}";

            // 1. Create User Wallet Transaction Debit ONLY NOW
            $transaction = UserWalletTransaction::create([
                'user_wallet_id'   => $wallet->id,
                'user_id'          => $details['admin_id'] ?? ($payout->created_by ?? 1),
                'amount'           => $payout->amount,
                'transaction_type' => 'debit',
                'description'      => ($details['description'] ?? "صرف عمولات مستثمر عبر HyperPay") . " | {$bankInfo}",
                'status'           => true, // Confirmed
                'maturity_time'    => $details['maturity'] ?? now(),
                'image'            => $details['image'] ?? null,
                'task_id'          => $details['task_id'] ?? null,
            ]);

            // If linked to an InvestorCommissionWithdrawal
            if ($payout->source_commission_withdrawal_id) {
                $withdrawal = \App\Models\InvestorCommissionWithdrawal::find($payout->source_commission_withdrawal_id);
                if ($withdrawal && in_array($withdrawal->status, ['pending', 'processing'])) {
                    $withdrawal->update([
                        'status'                     => 'approved',
                        'user_wallet_transaction_id' => $transaction->id,
                        'admin_notes'                => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "تم التحويل بنجاح عبر HyperPay (رقم العملية: {$payoutId})",
                        'processed_by'               => $payout->approved_by ?? ($details['admin_id'] ?? 1),
                        'processed_at'               => now(),
                    ]);

                    try {
                        if ($withdrawal->user) {
                            $newBalance = (float) $wallet->fresh()->withdrawable_balance;
                            app(\App\Services\InvestorNotificationService::class)->notifyCommissionWithdrawalApproved(
                                $withdrawal->user,
                                $withdrawal,
                                $newBalance
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to send investor email: " . $e->getMessage());
                    }
                }
            }

            // 2. Dispatch Admin Notification
            \App\Services\AdminNotificationDispatcher::dispatch(
                eventKey: 'payout_status_updated',
                title: "✅ اكتمال تحويل Payout لمستثمر #{$payout->reference_id}",
                message: "تم تأكيد تحويل مبلغ " . number_format($payout->amount, 2) . " ر.س بنجاح عبر بوابة HyperPay لحساب المستثمر " . ($user?->name ?? '') . "، وتم قيد الخصم في محفظة العمولات.",
                actionUrl: url('/admin/investors/payout-requests'),
                priority: 'normal',
                extraData: [
                    'payout_id'      => $payout->id,
                    'reference_id'   => $payout->reference_id,
                    'amount'         => $payout->amount,
                    'status'         => 'completed',
                    'user_id'        => $payout->user_id,
                    'user_wallet_id' => $wallet->id,
                ]
            );
        } else {
            $payout->update([
                'status'          => 'failed',
                'failure_reason'  => $failureReason,
                'webhook_payload' => !empty($payload) ? $payload : null
            ]);
            Log::error("Investor Payout for reference {$reference} failed via Webhook. Reason: " . $failureReason);

            if ($payout->source_commission_withdrawal_id) {
                $withdrawal = \App\Models\InvestorCommissionWithdrawal::find($payout->source_commission_withdrawal_id);
                if ($withdrawal && in_array($withdrawal->status, ['pending', 'processing'])) {
                    $withdrawal->update([
                        'status'           => 'rejected',
                        'admin_notes'      => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "فشل التحويل عبر HyperPay: " . $failureReason,
                        'rejection_reason' => "فشل التحويل البنكي عبر HyperPay: " . $failureReason,
                        'processed_by'     => $payout->approved_by ?? ($details['admin_id'] ?? 1),
                        'processed_at'     => now(),
                    ]);

                    try {
                        if ($withdrawal->user) {
                            app(\App\Services\InvestorNotificationService::class)->notifyCommissionWithdrawalRejected(
                                $withdrawal->user,
                                $withdrawal,
                                "فشل التحويل البنكي عبر HyperPay: " . $failureReason
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to send investor rejection email: " . $e->getMessage());
                    }
                }
            }

            // Dispatch Admin Notification of Failure
            \App\Services\AdminNotificationDispatcher::dispatch(
                eventKey: 'payout_status_updated',
                title: "❌ فشل تحويل Payout لمستثمر #{$payout->reference_id}",
                message: "فشل التحويل البنكي لمبلغ " . number_format($payout->amount, 2) . " ر.س للمستثمر " . ($user?->name ?? '') . ". السبب: {$failureReason}",
                actionUrl: url('/admin/investors/payout-requests'),
                priority: 'high',
                extraData: [
                    'payout_id'      => $payout->id,
                    'reference_id'   => $payout->reference_id,
                    'amount'         => $payout->amount,
                    'status'         => 'failed',
                    'failure_reason' => $failureReason,
                    'user_id'        => $payout->user_id,
                ]
            );
        }
    }

    protected function handleDriverWalletPayout($walletId, $isSuccess, $payoutId, $failureReason, $amount, $reference, $payload = [])
    {
        $payout = \App\Models\HyperpayPayout::where('reference_id', $reference)->first();
        if (!$payout) {
            Log::error("HyperpayPayout for reference {$reference} not found.");
            return;
        }

        if (!in_array($payout->status, ['pending', 'processing'])) {
            Log::info("Driver Payout for reference {$reference} is already processed (Status: {$payout->status}). Skipping to prevent duplication.");
            return;
        }

        if ($isSuccess) {
            $payout->update([
                'status' => 'completed',
                'webhook_payload' => !empty($payload) ? $payload : null
            ]);
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

                        $walletTransaction->update(['status' => 1, 'user_id' => $details['admin_id'] ?? 1]);
                        $bankInfo = "البنك: " . ($payout->driver->bank_name ?? 'غير محدد') . " | الآيبان: " . ($payout->driver->iban_number ?? 'غير محدد') . " | رقم العملية: {$payoutId}";
                        $paymentDescription = "دفع مستحقات سائق (كامل) للمعاملة رقم #{$walletTransaction->sequence} | {$bankInfo}";
                    } elseif ($remainingAmount > 0) {
                        $paymentAmount = $remainingAmount;
                        $remainingTransactionAmount = $originalAmount - $paymentAmount;
                        $remainingAmount = 0;

                        $walletTransaction->update([
                            'status' => 1,
                            'amount' => $paymentAmount,
                            'user_id' => $details['admin_id'] ?? 1
                        ]);

                        \App\Models\Wallet_Transaction::create([
                            'wallet_id' => $walletTransaction->wallet_id,
                            'amount' => $remainingTransactionAmount,
                            'transaction_type' => $walletTransaction->transaction_type,
                            'description' => "المبلغ المتبقي من المعاملة #{$walletTransaction->sequence} - تم دفع {$paymentAmount} من أصل {$originalAmount} ريال",
                            'status' => 0,
                            'user_id' => $details['admin_id'] ?? 1,
                            'maturity_time' => $walletTransaction->maturity_time,
                            'task_id' => $walletTransaction->task_id,
                            'image' => $walletTransaction->image
                        ]);

                        $bankInfo = "البنك: " . ($payout->driver->bank_name ?? 'غير محدد') . " | الآيبان: " . ($payout->driver->iban_number ?? 'غير محدد') . " | رقم العملية: {$payoutId}";
                        $paymentDescription = "دفع مستحقات سائق (جزئي: {$paymentAmount} من {$originalAmount}) للمعاملة رقم #{$walletTransaction->sequence} | {$bankInfo}";
                    }

                    if ($paymentAmount > 0) {
                        \App\Models\Wallet_Transaction::create([
                            'wallet_id' => $walletTransaction->wallet_id,
                            'amount' => $paymentAmount,
                            'transaction_type' => 'debit',
                            'description' => $paymentDescription . (!empty($details['notes']) ? " - ملاحظات: {$details['notes']}" : ""),
                            'status' => 1,
                            'user_id' => $details['admin_id'] ?? 1,
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
                $bankInfo = "البنك: " . ($payout->driver->bank_name ?? 'غير محدد') . " | الآيبان: " . ($payout->driver->iban_number ?? 'غير محدد') . " | رقم العملية: {$payoutId}";
                // Direct Manual Transaction
                \App\Models\Wallet_Transaction::create([
                    'wallet_id' => $payout->wallet_id,
                    'user_id' => $details['admin_id'] ?? 1,
                    'amount' => $payout->amount,
                    'transaction_type' => 'debit',
                    'description' => ($details['description'] ?? "سحب مباشر عبر HyperPay") . " | {$bankInfo}",
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
            } elseif ($payout->payout_type === 'WD') {
                $withdrawal = \App\Models\WithdrawalRequest::find($payout->source_withdrawal_id);
                if ($withdrawal && $withdrawal->status === 'processing') {
                    $bankInfo = "البنك: " . ($payout->driver->bank_name ?? 'غير محدد') . " | الآيبان: " . ($payout->driver->iban_number ?? 'غير محدد') . " | رقم العملية: {$payoutId}";
                    
                    $transaction = \App\Models\Wallet_Transaction::create([
                        'wallet_id' => $withdrawal->wallet_id,
                        'user_id' => $details['admin_id'] ?? 1,
                        'amount' => $payout->amount,
                        'transaction_type' => 'debit',
                        'description' => "سحب نقدي عبر HyperPay - طلب رقم #{$withdrawal->id} | {$bankInfo}",
                        'status' => 1,
                        'image' => $details['receipt_image'] ?? null,
                        'maturity_time' => now(),
                    ]);

                    $withdrawal->update([
                        'status' => 'completed',
                        'wallet_transaction_id' => $transaction->id,
                    ]);

                    app(\App\Services\NotificationService::class)->send(
                        'driver',
                        [$withdrawal->driver_id],
                        '✅ تمت معالجة طلب السحب عبر HyperPay بنجاح',
                        "تم تحويل مبلغ {$payout->amount} ريال إلى حسابك البنكي لطلب السحب رقم #{$withdrawal->id}.",
                        '/images/admin-icon.png',
                        null,
                        "/wallet",
                        'withdrawal_approved'
                    );
                }
            }

            // إشعار الإدارة باكتمال التحويل البنكي
            \App\Services\AdminNotificationDispatcher::dispatch(
                eventKey: 'payout_status_updated',
                title: "✅ اكتمال تحويل Payout بنكي #{$payout->reference_id}",
                message: "تم تأكيد تحويل مبلغ " . number_format($payout->amount, 2) . " ر.س بنجاح عبر بوابة HyperPay لحساب السائق.",
                actionUrl: url('/admin/wallets/payout-requests'),
                priority: 'normal',
                extraData: [
                    'payout_id'    => $payout->id,
                    'reference_id' => $payout->reference_id,
                    'amount'       => $payout->amount,
                    'status'       => 'completed',
                ]
            );
        } else {
            $payout->update([
                'status' => 'failed',
                'failure_reason' => $failureReason,
                'webhook_payload' => !empty($payload) ? $payload : null
            ]);
            if ($payout->payout_type === 'WD' && $payout->source_withdrawal_id) {
                $withdrawal = \App\Models\WithdrawalRequest::find($payout->source_withdrawal_id);
                if ($withdrawal && $withdrawal->status === 'processing') {
                    $withdrawal->update([
                        'status' => 'pending',
                        'admin_notes' => ($withdrawal->admin_notes ? $withdrawal->admin_notes . ' | ' : '') . "فشل التحويل عبر HyperPay: " . $failureReason,
                    ]);
                }
            }
            Log::error("Driver Payout for reference {$reference} failed via Webhook. Reason: " . $failureReason);

            // إشعار الإدارة بفشل التحويل البنكي
            \App\Services\AdminNotificationDispatcher::dispatch(
                eventKey: 'payout_status_updated',
                title: "❌ فشل تحويل Payout بنكي #{$payout->reference_id}",
                message: "فشل التحويل البنكي للمبلغ " . number_format($payout->amount, 2) . " ر.س. السبب: {$failureReason}",
                actionUrl: url('/admin/wallets/payout-requests'),
                priority: 'high',
                extraData: [
                    'payout_id'      => $payout->id,
                    'reference_id'   => $payout->reference_id,
                    'amount'         => $payout->amount,
                    'status'         => 'failed',
                    'failure_reason' => $failureReason,
                ]
            );
        }
    }

    protected function handleTeamWalletPayout($walletId, $isSuccess, $payoutId, $failureReason, $amount, $reference, $payload = [])
    {
        $payout = \App\Models\HyperpayPayout::where('reference_id', $reference)->first();
        if (!$payout) {
            Log::error("HyperpayPayout for team reference {$reference} not found.");
            // Fallback: check if wallet exists directly
            $wallet = Team_Wallet::find($walletId);
            if (!$wallet) {
                Log::error("Team Wallet #{$walletId} not found for HyperPay Webhook.");
            }
            return;
        }

        if (!in_array($payout->status, ['pending', 'processing'])) {
            Log::info("Team Payout for reference {$reference} is already processed (Status: {$payout->status}). Skipping to prevent duplication.");
            return;
        }

        $wallet = $payout->teamWallet ?? Team_Wallet::find($payout->team_wallet_id ?? $walletId);
        if (!$wallet) {
            Log::error("Team Wallet for payout #{$payout->id} not found.");
            return;
        }

        $team = $payout->team ?? $wallet->team;
        $details = $payout->transaction_details ?? [];

        if ($isSuccess) {
            $payout->update([
                'status' => 'completed',
                'webhook_payload' => !empty($payload) ? $payload : null
            ]);
            Log::info("Team Payout for reference {$reference} confirmed by Webhook. PayoutId: " . $payoutId);

            $bankInfo = "البنك: " . ($details['bank_name'] ?? ($team?->bank_name ?? 'غير محدد')) . " | الآيبان: " . ($details['iban'] ?? ($team?->iban_number ?? 'غير محدد')) . " | رقم العملية: {$payoutId}";

            // 1. Create Team Wallet Transaction Debit ONLY NOW
            Team_Wallet_Transaction::create([
                'team_wallet_id'   => $wallet->id,
                'user_id'          => $details['admin_id'] ?? ($payout->created_by ?? 1),
                'amount'           => $payout->amount,
                'transaction_type' => 'debit',
                'description'      => ($details['description'] ?? "صرف مستحقات فريق عبر HyperPay") . " | {$bankInfo}",
                'status'           => 1, // Confirmed
                'maturity_time'    => $details['maturity'] ?? now(),
                'image'            => $details['image'] ?? null,
                'task_id'          => $details['task_id'] ?? null,
            ]);

            // 2. Dispatch Admin Notification
            \App\Services\AdminNotificationDispatcher::dispatch(
                eventKey: 'payout_status_updated',
                title: "✅ اكتمال تحويل Payout لفريق #{$payout->reference_id}",
                message: "تم تأكيد تحويل مبلغ " . number_format($payout->amount, 2) . " ر.س بنجاح عبر HyperPay لحساب فريق " . ($team?->name ?? '') . "، وتم قيد الخصم في محفظة الفريق.",
                actionUrl: url('/admin/teams/payout-requests'),
                priority: 'normal',
                extraData: [
                    'payout_id'      => $payout->id,
                    'reference_id'   => $payout->reference_id,
                    'amount'         => $payout->amount,
                    'status'         => 'completed',
                    'team_id'        => $payout->team_id,
                    'team_wallet_id' => $wallet->id,
                ]
            );
        } else {
            $payout->update([
                'status'          => 'failed',
                'failure_reason'  => $failureReason,
                'webhook_payload' => !empty($payload) ? $payload : null
            ]);
            Log::error("Team Payout for reference {$reference} failed via Webhook. Reason: " . $failureReason);

            // Dispatch Admin Notification of Failure
            \App\Services\AdminNotificationDispatcher::dispatch(
                eventKey: 'payout_status_updated',
                title: "❌ فشل تحويل Payout لفريق #{$payout->reference_id}",
                message: "فشل التحويل البنكي لمبلغ " . number_format($payout->amount, 2) . " ر.س لفريق " . ($team?->name ?? '') . ". السبب: {$failureReason}",
                actionUrl: url('/admin/teams/payout-requests'),
                priority: 'high',
                extraData: [
                    'payout_id'      => $payout->id,
                    'reference_id'   => $payout->reference_id,
                    'amount'         => $payout->amount,
                    'status'         => 'failed',
                    'failure_reason' => $failureReason,
                    'team_id'        => $payout->team_id,
                ]
            );
        }
    }
}
