<?php

namespace App\Services;

use App\Models\InvestmentContract;
use App\Models\InvestorWallet;
use App\Models\InvestorWalletTransaction;
use App\Models\Task;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserWalletTransaction;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use Illuminate\Support\Facades\DB;

class InvestorPaymentService
{
    /**
     * دفع قيمة مهمة من محفظة مضارب.
     * يُستخدم لكلا نوعي المضاربة (بالمهام والعام).
     *
     * الخطوات:
     * 1. التحقق من أهلية المهمة (غير مقفلة، غير مدفوعة)
     * 2. التحقق من سقف مديونية العميل قبل الدفع
     * 3. التحقق من رصيد محفظة المضاربة
     * 4. خصم من محفظة المضاربة + تغيير حالة المهمة إلى 'paid'
     * 5. تسجيل دين على العميل في محفظته (Wallet debit)
     * 6. حساب العمولة (لا تتجاوز عمولة المنصة) + إيداع في المحفظة الشخصية
     *
     * @param  User                $investor  المضارب
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
                throw new \Exception('هذه المهمة تم دفعها مسبقاً من قبل مضارب آخر.');
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

            // ── 4. التحقق من رصيد محفظة المضاربة ───────────────────────────
            $investorBalance = $investorWallet->balance;
            if ($investorBalance < $taskPrice) {
                throw new \Exception(
                    "رصيد محفظة المضاربة غير كافٍ. الرصيد المتاح: {$investorBalance} ر.س."
                );
            }

            $balanceAfterDebit = $investorBalance - $taskPrice;

            // ── 5. خصم من محفظة المضاربة ────────────────────────────────────
            InvestorWalletTransaction::create([
                'investor_wallet_id' => $investorWallet->id,
                'task_id'            => $task->id,
                'transaction_type'   => 'debit',
                'source_type'        => 'capital',
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

            // ── 8. احتساب عمولة المضارب وإيداعها في المحفظة الشخصية ────────
            $this->creditInvestorCommission($investor, $task, $contract);
        });
    }

    /**
     * احتساب عمولة مضارب عام على جميع مهام المنصة ضمن نطاق العقد.
     * تُستدعى عند الضغط على زر "احتساب العمولات" من لوحة تحكم المضارب.
     *
     * القواعد:
     * - تُحتسب فقط المهام التي created_at بين start_date و end_date
     * - لا تُحتسب عمولة على مهمة سبق احتساب عمولتها لهذا المضارب
     * - عمولة المضارب لا تتجاوز عمولة المنصة لكل مهمة
     *
     * @param  User               $investor
     * @param  InvestmentContract $contract
     * @return array ['count' => int, 'total_commission' => float]
     * @throws \Exception
     */
    public function calculateGeneralCommissions(User $investor, InvestmentContract $contract): array
    {
        if ($contract->contract_type !== 'general_investment') {
            throw new \Exception('هذه الدالة مخصصة للمضارب العام فقط.');
        }

        if (!$contract->isActive()) {
            throw new \Exception('العقد غير نشط أو منتهي الصلاحية.');
        }

        $personalWallet = $investor->userWallet;
        if (!$personalWallet) {
            throw new \Exception('لا توجد محفظة شخصية للمضارب.');
        }

        // جلب المهام المغلقة ضمن نطاق تاريخ العقد
        $tasksQuery = Task::with('ad')
            ->where('closed', true) // فقط المهام المغلقة والمكتملة
            ->where(function($q) use ($investor) {
                // يسمح بالمهام التي لا يوجد لها مضارب (مهام المنصة) 
                // أو المهام التي مولها نفس المضارب الحالي
                $q->whereNull('investor_id')
                  ->orWhere('investor_id', $investor->id);
            })
            ->where(function($q) {
                // المهمة يجب أن تكون لها عمولة (إما في جدول المهام أو الإعلان)
                $q->where('commission', '>', 0)
                  ->orWhereHas('ad', fn($sub) => $sub->where('service_commission', '>', 0));
            })
            // الاعتماد على تاريخ الإنشاء للمطابقة مع العقد
            ->where('created_at', '>=', $contract->start_date->startOfDay())
            ->where(function ($q) use ($contract) {
                if ($contract->end_date) {
                    $q->where('created_at', '<=', $contract->end_date->endOfDay());
                }
            })
            // تطبيق فلتر العملاء إذا وجد في العقد
            ->when(!empty($contract->filter_customer_ids), function($q) use ($contract) {
                $q->whereIn('customer_id', $contract->filter_customer_ids);
            })
            // استثناء المهام التي تم احتساب عمولتها لهذا المضارب مسبقاً
            ->whereNotExists(function ($sub) use ($personalWallet) {
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
                // 1. حساب مبلغ عمولة المنصة الفعلي (Platform Cut)
                $platformCut = 0;
                if ($task->ad) {
                    // إذا كان هناك إعلان، نتحقق من النوع (0 = نسبة مئوية، 1 = مبلغ ثابت)
                    if ($task->ad->service_commission_type == 1) { 
                        $platformCut = (float) $task->ad->service_commission;
                    } else {
                        // احتساب النسبة من سعر المهمة
                        $platformCut = ($task->total_price * $task->ad->service_commission) / 100;
                    }
                } else {
                    // العمولة اليدوية في جدول المهام تُعامل كمبلغ ثابت
                    $platformCut = (float) $task->commission;
                }

                if ($platformCut <= 0) continue;

                // 2. التحقق من الحد الأدنى للعمولة في العقد
                if ($contract->min_commission_threshold > 0 && $platformCut < $contract->min_commission_threshold) {
                    continue;
                }

                // 3. حساب نصيب المضارب بناءً على المبلغ الفعلي لعمولة المنصة
                $investorCommission = $contract->calculateCommission($platformCut);
                if ($investorCommission <= 0) continue;

                $this->processBrokerAndInvestorCommission($investor, $task, $contract, $platformCut, $investorCommission, $personalWallet, "مضارب عام");

                $totalCommission += $investorCommission;
                $count++;
            }
        });

        return ['count' => $count, 'total_commission' => $totalCommission];
    }

    /**
     * إيداع عمولة المضارب في محفظته الشخصية بعد الدفع على مهمة.
     * مشتركة بين نوعي الاستثمار.
     */
    private function creditInvestorCommission(User $investor, Task $task, InvestmentContract $contract): void
    {
        $personalWallet = $investor->userWallet;
        if (!$personalWallet) return;

        // 1. حساب مبلغ عمولة المنصة الفعلي (Platform Cut)
        $platformCut = 0;
        if ($task->ad) {
            if ($task->ad->service_commission_type == 1) { 
                $platformCut = (float) $task->ad->service_commission;
            } else {
                $platformCut = ($task->total_price * $task->ad->service_commission) / 100;
            }
        } else {
            $platformCut = (float) $task->commission;
        }

        if ($platformCut <= 0) return;

        // 2. التحقق من الحد الأدنى (اختياري هنا لأن المهمة مدفوعة مسبقاً، ولكن نلتزم بالعقد)
        if ($contract->min_commission_threshold > 0 && $platformCut < $contract->min_commission_threshold) {
            return;
        }

        // 3. حساب نصيب المضارب من المبلغ الفعلي
        $investorCommission = $contract->calculateCommission($platformCut);
        if ($investorCommission <= 0) return;

        $this->processBrokerAndInvestorCommission($investor, $task, $contract, $platformCut, $investorCommission, $personalWallet);
    }

    /**
     * معالجة واحتساب وتوزيع عمولة الوسيط وعمولة المضارب النهائية.
     */
    private function processBrokerAndInvestorCommission(
        User $investor, 
        Task $task, 
        InvestmentContract $contract, 
        float $platformCut, 
        float $investorCommission, 
        UserWallet $personalWallet, 
        string $descSuffix = ""
    ): void {
        $brokerShare = 0;
        $finalInvestorCommission = $investorCommission;

        if ($contract->broker_id && $contract->broker_commission_value > 0) {
            // أ. حساب عمولة الوسيط
            if ($contract->broker_commission_type === 'percentage') {
                if ($contract->broker_commission_source === 'investor_commission') {
                    $brokerShare = ($investorCommission * $contract->broker_commission_value) / 100;
                } else { // task_commission
                    $brokerShare = ($platformCut * $contract->broker_commission_value) / 100;
                }
            } else { // fixed
                $brokerShare = (float) $contract->broker_commission_value;
            }

            // ب. تطبيق الخصم بناءً على المصدر
            if ($contract->broker_commission_source === 'investor_commission') {
                $finalInvestorCommission = max(0, $investorCommission - $brokerShare);
            }

            // ج. حماية المنصة ماليًا للتأكد من عدم تجاوز العمولات لإجمالي عمولة المهمة
            if (($finalInvestorCommission + $brokerShare) > $platformCut) {
                $brokerShare = max(0, $platformCut - $finalInvestorCommission);
            }

            // د. إيداع حصة الوسيط
            if ($brokerShare > 0) {
                $broker = $contract->broker;
                if ($broker) {
                    $brokerWallet = $broker->userWallet ?: (new \App\Http\Controllers\admin\UserWalletsController())->createWallet($broker->id, true);
                    UserWalletTransaction::create([
                        'user_wallet_id'   => $brokerWallet->id,
                        'task_id'          => $task->id,
                        'transaction_type' => 'credit',
                        'amount'           => $brokerShare,
                        'description'      => "عمولة وسيط: تسويق المضارب {$investor->name} للمهمة #{$task->id}",
                        'status'           => true,
                    ]);
                }
            }
        }

        // هـ. إيداع حصة المضارب النهائية
        UserWalletTransaction::create([
            'user_wallet_id'   => $personalWallet->id,
            'task_id'          => $task->id,
            'transaction_type' => 'credit',
            'amount'           => $finalInvestorCommission,
            'description'      => "عمولة المهمة #{$task->id}" . ($descSuffix ? " ({$descSuffix})" : "") . ($brokerShare > 0 && $contract->broker_commission_source === 'investor_commission' ? " (بعد خصم عمولة الوسيط)" : ""),
            'status'           => true,
        ]);
    }

    /**
     * إيداع مبلغ في محفظة المضاربة (يستخدمه Admin).
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
                throw new \Exception('لا توجد محفظة مضاربة لهذا المضارب.');
            }

            $newBalance = $wallet->balance + $amount;

            InvestorWalletTransaction::create([
                'investor_wallet_id' => $wallet->id,
                'transaction_type'   => 'credit',
                'source_type'        => 'capital',
                'amount'             => $amount,
                'description'        => $description,
                'performed_by'       => auth()->id(),
                'balance_after'      => $newBalance,
            ]);
        });
    }
}
