<?php

namespace App\Services;

use App\Models\InvestmentContract;
use App\Models\InvestorWallet;
use App\Models\InvestorWalletTransaction;
use App\Models\Task;
use App\Models\User;
use App\Models\UserWalletTransaction;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use Illuminate\Support\Facades\DB;

class InvestorPaymentService
{
    /**
     * دفع قيمة مهمة من محفظة مستثمر.
     * يُستخدم لكلا نوعي الاستثمار (بالمهام والعام).
     *
     * الخطوات:
     * 1. التحقق من أهلية المهمة (غير مقفلة، غير مدفوعة)
     * 2. التحقق من سقف مديونية العميل قبل الدفع
     * 3. التحقق من رصيد محفظة الاستثمار
     * 4. خصم من محفظة الاستثمار + تغيير حالة المهمة إلى 'paid'
     * 5. تسجيل دين على العميل في محفظته (Wallet debit)
     * 6. حساب العمولة (لا تتجاوز عمولة المنصة) + إيداع في المحفظة الشخصية
     *
     * @param  User                $investor  المستثمر
     * @param  Task                $task      المهمة المراد دفعها
     * @param  InvestmentContract  $contract  العقد النشط
     * @throws \Exception
     */
    public function payTask(User $investor, Task $task, InvestmentContract $contract): void
    {
        DB::transaction(function () use ($investor, $task, $contract) {

            // ── 1. قفل السجلات لمنع Race Conditions ──────────────────────────
            $task           = Task::lockForUpdate()->findOrFail($task->id);
            $investorWallet = InvestorWallet::lockForUpdate()->where('user_id', $investor->id)->firstOrFail();

            // ── 2. التحقق من أهلية المهمة ────────────────────────────────────
            if ($task->investor_payment_status !== 'none') {
                throw new \Exception('هذه المهمة تم دفعها مسبقاً من قبل مستثمر آخر.');
            }
            if ($task->closed) {
                throw new \Exception('لا يمكن الدفع على مهمة مقفلة.');
            }

            $taskPrice = (float) $task->total_price;

            // ── 3. التحقق من سقف مديونية العميل ─────────────────────────────
            if ($task->customer_id) {
                $customerWallet = Wallet::lockForUpdate()
                    ->where('customer_id', $task->customer_id)
                    ->where('user_type', 'customer')
                    ->first();

                if ($customerWallet) {
                    $currentDebt  = $customerWallet->debit - $customerWallet->credit; // الدين = مجموع الخصم - مجموع الإيداع
                    $debtCeiling  = (float) $customerWallet->debt_ceiling;
                    $newDebt      = $currentDebt + $taskPrice;

                    if ($newDebt > $debtCeiling) {
                        throw new \Exception(
                            "لا يمكن الدفع: سيتجاوز دين العميل الحد المسموح به ({$debtCeiling} ر.س). الدين الحالي: {$currentDebt} ر.س."
                        );
                    }
                }
            }

            // ── 4. التحقق من رصيد محفظة الاستثمار ───────────────────────────
            $investorBalance = $investorWallet->balance;
            if ($investorBalance < $taskPrice) {
                throw new \Exception(
                    "رصيد محفظة الاستثمار غير كافٍ. الرصيد المتاح: {$investorBalance} ر.س."
                );
            }

            $balanceAfterDebit = $investorBalance - $taskPrice;

            // ── 5. خصم من محفظة الاستثمار ────────────────────────────────────
            InvestorWalletTransaction::create([
                'investor_wallet_id' => $investorWallet->id,
                'task_id'            => $task->id,
                'transaction_type'   => 'debit',
                'amount'             => $taskPrice,
                'description'        => "دفع قيمة المهمة رقم #{$task->id}",
                'performed_by'       => auth()->id() ?? $investor->id,
                'balance_after'      => $balanceAfterDebit,
            ]);

            // ── 6. تحديث حالة الدفع في المهمة ───────────────────────────────
            $task->update([
                'investor_id'             => $investor->id,
                'investor_payment_status' => 'paid',
                'payment_status'          => 'paid',
                'payment_method'          => 'Wallet',
                'payment_note'            => 'سدد من محفظة العميل',
            ]);

            // ── 7. تسجيل دين على العميل في محفظته ───────────────────────────
            if ($task->customer_id) {
                $customerWallet = Wallet::where('customer_id', $task->customer_id)
                    ->where('user_type', 'customer')
                    ->first();

                if ($customerWallet) {
                    Wallet_Transaction::create([
                        'wallet_id'        => $customerWallet->id,
                        'task_id'          => $task->id,
                        'transaction_type' => 'debit',
                        'amount'           => $taskPrice,
                        'description'      => "Payment: Task #{$task->id}",
                        'user_id'          => auth()->id() ?? $investor->id,
                        'status'           => 1,
                    ]);
                }
            }

            // ── 8. احتساب عمولة المستثمر وإيداعها في المحفظة الشخصية ────────
            $this->creditInvestorCommission($investor, $task, $contract);
        });
    }

    /**
     * احتساب عمولة مستثمر عام على جميع مهام المنصة ضمن نطاق العقد.
     * تُستدعى عند الضغط على زر "احتساب العمولات" من لوحة تحكم المستثمر.
     *
     * القواعد:
     * - تُحتسب فقط المهام التي created_at بين start_date و end_date
     * - لا تُحتسب عمولة على مهمة سبق احتساب عمولتها لهذا المستثمر
     * - عمولة المستثمر لا تتجاوز عمولة المنصة لكل مهمة
     *
     * @param  User               $investor
     * @param  InvestmentContract $contract
     * @return array ['count' => int, 'total_commission' => float]
     * @throws \Exception
     */
    public function calculateGeneralCommissions(User $investor, InvestmentContract $contract): array
    {
        if ($contract->contract_type !== 'general_investment') {
            throw new \Exception('هذه الدالة مخصصة للمستثمر العام فقط.');
        }

        if (!$contract->isActive()) {
            throw new \Exception('العقد غير نشط أو منتهي الصلاحية.');
        }

        $personalWallet = $investor->userWallet;
        if (!$personalWallet) {
            throw new \Exception('لا توجد محفظة شخصية للمستثمر.');
        }

        // جلب المهام ضمن نطاق تاريخ العقد
        $tasksQuery = Task::with('ad')
            ->where(function($q) use ($contract) {
                // المهمة يجب أن تكون لها عمولة (إما في جدول المهام أو الإعلان)
                $q->where('commission', '>', 0)
                  ->orWhereHas('ad', fn($sub) => $sub->where('service_commission', '>', 0));
            })
            ->where('created_at', '>=', $contract->start_date->startOfDay())
            ->where(function ($q) use ($contract) {
                if ($contract->end_date) {
                    $q->where('created_at', '<=', $contract->end_date->endOfDay());
                }
            })
            // استثناء المهام التي تم احتساب عمولتها لهذا المستثمر مسبقاً
            ->whereNotExists(function ($sub) use ($personalWallet, $investor) {
                $sub->from('user_wallet_transactions')
                    ->where('user_wallet_id', $personalWallet->id)
                    ->whereColumn('task_id', 'tasks.id')
                    ->where('transaction_type', 'credit');
            });

        $tasks = $tasksQuery->get();

        if ($tasks->isEmpty()) {
            return ['count' => 0, 'total_commission' => 0.0];
        }

        $totalCommission = 0.0;
        $count           = 0;

        DB::transaction(function () use ($tasks, $contract, $personalWallet, $investor, &$totalCommission, &$count) {
            foreach ($tasks as $task) {
                // جلب عمولة المنصة من الإعلان أو المهمة
                $platformCommission = (float) ($task->ad->service_commission ?? $task->commission ?? 0);
                if ($platformCommission <= 0) continue;

                // حساب العمولة مع ضمان عدم تجاوز عمولة المنصة
                $investorCommission = $contract->calculateCommission($platformCommission);
                if ($investorCommission <= 0) continue;

                $currentBalance = $personalWallet->balance + $totalCommission; // تراكمي

                UserWalletTransaction::create([
                    'user_wallet_id'   => $personalWallet->id,
                    'task_id'          => $task->id,
                    'transaction_type' => 'credit',
                    'amount'           => $investorCommission,
                    'description'      => "عمولة المهمة #{$task->id} (مستثمر عام)",
                    'status'           => true,
                ]);

                $totalCommission += $investorCommission;
                $count++;
            }
        });

        return ['count' => $count, 'total_commission' => $totalCommission];
    }

    /**
     * إيداع عمولة المستثمر في محفظته الشخصية بعد الدفع على مهمة.
     * مشتركة بين نوعي الاستثمار.
     */
    private function creditInvestorCommission(User $investor, Task $task, InvestmentContract $contract): void
    {
        $personalWallet = $investor->userWallet;
        if (!$personalWallet) return;

        // جلب عمولة المنصة (من الإعلان أو المهمة مباشرة)
        $platformCommission = (float) ($task->ad->service_commission ?? $task->commission ?? 0);
        if ($platformCommission <= 0) return;

        // حساب العمولة مع ضمان عدم تجاوزها عمولة المنصة
        $investorCommission = $contract->calculateCommission($platformCommission);
        if ($investorCommission <= 0) return;

        $newPersonalBalance = $personalWallet->balance + $investorCommission;

        UserWalletTransaction::create([
            'user_wallet_id'   => $personalWallet->id,
            'task_id'          => $task->id,
            'transaction_type' => 'credit',
            'amount'           => $investorCommission,
            'description'      => "عمولة المهمة #{$task->id}",
            'status'           => true,
        ]);
    }

    /**
     * إيداع مبلغ في محفظة الاستثمار (يستخدمه Admin).
     *
     * @param  User   $investor
     * @param  float  $amount
     * @param  string $description
     */
    public function depositToInvestorWallet(User $investor, float $amount, string $description = 'إيداع من الإدارة'): void
    {
        DB::transaction(function () use ($investor, $amount, $description) {
            $wallet = $investor->investorWallet;

            if (!$wallet) {
                throw new \Exception('لا توجد محفظة استثمار لهذا المستثمر.');
            }

            $newBalance = $wallet->balance + $amount;

            InvestorWalletTransaction::create([
                'investor_wallet_id' => $wallet->id,
                'transaction_type'   => 'credit',
                'amount'             => $amount,
                'description'        => $description,
                'performed_by'       => auth()->id(),
                'balance_after'      => $newBalance,
            ]);
        });
    }
}
