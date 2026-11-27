<?php

namespace App\Services;

use App\Models\InvestmentContract;
use App\Models\InvestorWallet;
use App\Models\InvestorWalletTransaction;
use App\Models\Task;
use App\Models\Task_History;
use App\Models\ActivityLog;
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
                throw new \Exception(__('Task already paid by another investor'));
            }
            if (in_array($task->gotech_status, ['pending_funding', 'funded'])) {
                throw new \Exception(__('لا يمكن دفع هذه المهمة لأنها مطروحة أو ممولة بالفعل عبر منصة Go-Tech.'));
            }
            if (!$task->closed || $task->status !== 'completed') {
                throw new \Exception(__('Cannot pay on an incomplete or unclosed task'));
            }

            $taskPrice = (float) $task->total_price;

            // ── 3. (تم إلغاء التحقق من سقف مديونية العميل) ─────────────────────────────

            // ── 4. التحقق من رصيد محفظة المضاربة ───────────────────────────
            $investorBalance = $investorWallet->balance;
            if ($investorBalance < $taskPrice) {
                throw new \Exception(__('Investment wallet balance insufficient', [
                    'balance' => $investorBalance,
                ]));
            }

            $balanceAfterDebit = $investorBalance - $taskPrice;

            // ── 5. خصم من محفظة المضاربة ────────────────────────────────────
            InvestorWalletTransaction::create([
                'investor_wallet_id' => $investorWallet->id,
                'task_id'            => $task->id,
                'transaction_type'   => 'debit',
                'source_type'        => 'capital',
                'amount'             => $taskPrice,
                'description'        => __('Pay Task Value') . " #{$task->id}",
                'performed_by'       => auth()->id() ?? $investor->id,
                'balance_after'      => $balanceAfterDebit,
            ]);

            // ── 6. تحديث حالة التمويل في المهمة (بدون تغيير حالة الدفع الأساسية) ───────────────────────────────
            $task->update([
                'investor_id'             => $investor->id,
                'investor_payment_status' => 'paid',
            ]);

            // ── 7. (تم إلغاء تسجيل دين على العميل في محفظته) ───────────────────────────

            // ── 8. احتساب عمولة المضارب وإيداعها في المحفظة الشخصية ────────
            $this->creditInvestorCommission($investor, $task, $contract);
        });

        // ── إرسال إشعار للمستثمر بنجاح استثمار المهمة ───────────
        $currentWalletBalance = (float) ($investor->investorWallet?->balance ?? 0);
        app(\App\Services\InvestorNotificationService::class)->notifyTaskInvestment(
            $investor,
            (float) $task->total_price,
            [$task->id],
            $currentWalletBalance
        );
    }

    /**
     * تسوية مبالغ الاستثمار (إعادة رأس المال للمستثمر)
     * تُستدعى عندما يقوم العميل بتسديد قيمة المهمة فعلياً
     * 
     * @param Task $task
     */
    public function settleTaskInvestment(Task $task): void
    {
        if (!$task->investor_id || $task->investor_payment_status !== 'paid') {
            return;
        }

        $settled = false;
        $settledTaskPrice = 0;
        $settledBalance = 0;
        $investorUser = null;

        DB::transaction(function () use ($task, &$settled, &$settledTaskPrice, &$settledBalance, &$investorUser) {
            $investorWallet = InvestorWallet::lockForUpdate()->where('user_id', $task->investor_id)->first();
            if (!$investorWallet) {
                return;
            }

            // منع التسوية المزدوجة
            $alreadySettled = InvestorWalletTransaction::where('investor_wallet_id', $investorWallet->id)
                ->where('task_id', $task->id)
                ->where('transaction_type', 'credit')
                ->where('source_type', 'capital_return')
                ->exists();

            if ($alreadySettled) {
                return;
            }

            $taskPrice = (float) $task->total_price;
            $balanceAfterCredit = $investorWallet->balance + $taskPrice;

            InvestorWalletTransaction::create([
                'investor_wallet_id' => $investorWallet->id,
                'task_id'            => $task->id,
                'transaction_type'   => 'credit',
                'source_type'        => 'capital_return',
                'amount'             => $taskPrice,
                'description'        => __('Return of invested capital for Task') . " #{$task->id}",
                'performed_by'       => auth()->id() ?? $task->investor_id,
                'balance_after'      => $balanceAfterCredit,
            ]);

            $settled = true;
            $settledTaskPrice = $taskPrice;
            $settledBalance = $balanceAfterCredit;
            $investorUser = $investorWallet->user;
        });

        if ($settled && $investorUser) {
            app(\App\Services\InvestorNotificationService::class)->notifySettlement(
                $investorUser,
                $settledTaskPrice,
                [$task->id],
                $settledBalance,
                'online_payment'
            );
        }
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
            throw new \Exception(__('General commissions feature only'));
        }

        if (!$contract->isActive()) {
            throw new \Exception(__('Contract not active or expired'));
        }

        $personalWallet = $investor->userWallet;
        if (!$personalWallet) {
            throw new \Exception(__('No personal wallet for investor'));
        }

        // قفل لمنع الـ Race Condition وتنفيذ الدالة مرتين متتاليتين في نفس الوقت لنفس المستثمر
        $lockKey = 'calc_general_commissions_investor_' . $investor->id;
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 15); // القفل يمتد لـ 15 ثانية كحد أقصى

        if (!$lock->get()) {
            throw new \Exception(__('Calculation is already in progress, please wait.'));
        }

        try {
            // جلب المهام المغلقة ضمن نطاق تاريخ العقد
            $tasksQuery = Task::with('ad')
                ->where('closed', true) // فقط المهام المغلقة والمكتملة
                ->whereNotIn('status', ['canceled', 'cancelled', 'canceld', 'refund', 'refound', 'refunded'])
                ->whereNotIn('payment_status', ['refund', 'canceled'])
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
                // استثناء المهام التي تم احتساب عمولتها لهذا المضارب مسبقاً ولديها إيداع سارٍ بعد أي خصم عكسي
                ->whereNotIn('id', function ($query) use ($personalWallet) {
                    $query->select('task_id')
                          ->from('user_wallet_transactions')
                          ->where('user_wallet_id', $personalWallet->id)
                          ->whereNotNull('task_id')
                          ->groupBy('task_id')
                          ->havingRaw("
                              COUNT(CASE WHEN transaction_type = 'credit' THEN 1 END) > 0
                              AND (
                                  COUNT(CASE WHEN transaction_type = 'debit' THEN 1 END) = 0
                                  OR MAX(CASE WHEN transaction_type = 'credit' THEN created_at END) >= MAX(CASE WHEN transaction_type = 'debit' THEN created_at END)
                                  OR SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) > 0
                              )
                          ");
                });

            $tasks = $tasksQuery->get();

        if ($tasks->isEmpty()) {
            return ['count' => 0, 'total_commission' => 0.0];
        }

        $totalCommission = 0.0;
        $count           = 0;

        DB::transaction(function () use ($tasks, $contract, $personalWallet, $investor, &$totalCommission, &$count) {
            foreach ($tasks as $task) {
                $earned = $this->processBrokerAndInvestorCommission($investor, $task, $contract, $personalWallet, "مضارب عام");
                if ($earned > 0) {
                    $totalCommission += $earned;
                    $count++;
                }
            }
        });

        return ['count' => $count, 'total_commission' => $totalCommission];
        
        } finally {
            // فك القفل دائماً بعد الانتهاء أو عند حدوث خطأ
            $lock->release();
        }
    }

    /**
     * احتساب عمولات المهام المحددة (للمضارب بالمهام).
     * تُستدعى عند الضغط على زر "احتساب عمولات المهام" من لوحة تحكم المستثمر.
     *
     * القواعد:
     * - تُحتسب فقط المهام الممولة من قبل هذا المستثمر (investor_id == user_id)
     * - لا يشترط حالة دفع معينة.
     * - لا تُحتسب عمولة على مهمة سبق احتساب عمولتها ولم يتم إلغاؤها
     *
     * @param  User               $investor
     * @param  InvestmentContract $contract
     * @return array ['count' => int, 'total_commission' => float]
     * @throws \Exception
     */
    public function calculateTasksCommissions(User $investor, InvestmentContract $contract): array
    {
        if ($contract->contract_type !== 'task_investment') {
            throw new \Exception(__('This feature is for task investors only.'));
        }

        if (!$contract->isActive()) {
            throw new \Exception(__('Contract not active or expired'));
        }

        $personalWallet = $investor->userWallet;
        if (!$personalWallet) {
            throw new \Exception(__('No personal wallet for investor'));
        }

        // قفل لمنع الـ Race Condition وتنفيذ الدالة مرتين متتاليتين في نفس الوقت لنفس المستثمر
        $lockKey = 'calc_tasks_commissions_investor_' . $investor->id;
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 15); // القفل يمتد لـ 15 ثانية كحد أقصى

        if (!$lock->get()) {
            throw new \Exception(__('Calculation is already in progress, please wait.'));
        }

        try {
            // جلب المهام المرتبطة بهذا المستثمر كممول (باستثناء المهام الملغاة أو المستردة)
            $tasksQuery = Task::with('ad')
                ->where('investor_id', $investor->id)
                ->whereNotIn('status', ['canceled', 'cancelled', 'canceld', 'refund', 'refound', 'refunded'])
                ->whereNotIn('payment_status', ['refund', 'canceled'])
                ->where(function($q) {
                    // المهمة يجب أن تكون لها عمولة (إما في جدول المهام أو الإعلان)
                    $q->where('commission', '>', 0)
                      ->orWhereHas('ad', fn($sub) => $sub->where('service_commission', '>', 0));
                })
                // استثناء المهام التي تم احتساب عمولتها مسبقاً ولديها إيداع سارٍ بعد أي خصم عكسي
                ->whereNotIn('id', function ($query) use ($personalWallet) {
                    $query->select('task_id')
                          ->from('user_wallet_transactions')
                          ->where('user_wallet_id', $personalWallet->id)
                          ->whereNotNull('task_id')
                          ->groupBy('task_id')
                          ->havingRaw("
                              COUNT(CASE WHEN transaction_type = 'credit' THEN 1 END) > 0
                              AND (
                                  COUNT(CASE WHEN transaction_type = 'debit' THEN 1 END) = 0
                                  OR MAX(CASE WHEN transaction_type = 'credit' THEN created_at END) >= MAX(CASE WHEN transaction_type = 'debit' THEN created_at END)
                                  OR SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) > 0
                              )
                          ");
                });

            $tasks = $tasksQuery->get();

            if ($tasks->isEmpty()) {
                return ['count' => 0, 'total_commission' => 0.0];
            }

            $totalCommission = 0.0;
            $count           = 0;

            DB::transaction(function () use ($tasks, $contract, $personalWallet, $investor, &$totalCommission, &$count) {
                foreach ($tasks as $task) {
                    $earned = $this->processBrokerAndInvestorCommission($investor, $task, $contract, $personalWallet, "");
                    if ($earned > 0) {
                        $totalCommission += $earned;
                        $count++;
                    }
                }
            });

            return ['count' => $count, 'total_commission' => $totalCommission];
            
        } finally {
            $lock->release();
        }
    }

    /**
     * إيداع عمولة المضارب في محفظته الشخصية بعد الدفع على مهمة.
     * مشتركة بين نوعي الاستثمار.
     */
    private function creditInvestorCommission(User $investor, Task $task, InvestmentContract $contract): void
    {
        $personalWallet = $investor->userWallet;
        if (!$personalWallet) return;

        $this->processBrokerAndInvestorCommission($investor, $task, $contract, $personalWallet);
    }

    /**
     * معالجة واحتساب وتوزيع عمولة الوسيط وعمولة المضارب النهائية بعد خصم عمولات المستخدمين والوسطاء.
     */
    private function processBrokerAndInvestorCommission(
        User $investor, 
        Task $task, 
        InvestmentContract $contract, 
        UserWallet $personalWallet, 
        string $descSuffix = ""
    ): float {
        $calc = $task->calculateInvestorCommission($contract);
        $finalInvestorCommission = (float) $calc['net_investor_commission'];
        $brokerPayouts = $calc['broker_payouts'];

        if ($finalInvestorCommission <= 0 && empty($brokerPayouts)) {
            return 0.0;
        }

        // إيداع حصص وسطاء المستثمر في محافظهم
        $walletsCtrl = new \App\Http\Controllers\admin\UserWalletsController();
        foreach ($brokerPayouts as $payout) {
            if ($payout['amount'] > 0) {
                $brokerUser = User::find($payout['broker_id']);
                if ($brokerUser) {
                    $brokerWallet = $brokerUser->userWallet ?: $walletsCtrl->createWallet($brokerUser->id, true);
                    UserWalletTransaction::create([
                        'user_wallet_id'   => $brokerWallet->id,
                        'task_id'          => $task->id,
                        'transaction_type' => 'credit',
                        'amount'           => $payout['amount'],
                        'description'      => "عمولة وسيط: تسويق المضارب {$investor->name} للمهمة #{$task->id}",
                        'status'           => true,
                    ]);
                }
            }
        }

        // إيداع حصة المضارب النهائية
        if ($finalInvestorCommission > 0) {
            $descNotes = [];
            if ($calc['user_commissions_total'] > 0) {
                $descNotes[] = "عمولات مستخدمين: -{$calc['user_commissions_total']}";
            }
            if ($calc['task_brokers_total'] > 0) {
                $descNotes[] = "عمولات وسطاء: -{$calc['task_brokers_total']}";
            }
            if ($calc['contract_task_brokers_total'] > 0 || $calc['contract_investor_brokers_total'] > 0) {
                $descNotes[] = "عمولة وسيط المستثمر";
            }
            $noteSuffix = !empty($descNotes) ? " (بعد خصم " . implode(' و ', $descNotes) . ")" : "";
            $suffixCombined = ($descSuffix ? " ({$descSuffix})" : "") . $noteSuffix;

            UserWalletTransaction::create([
                'user_wallet_id'   => $personalWallet->id,
                'task_id'          => $task->id,
                'transaction_type' => 'credit',
                'amount'           => $finalInvestorCommission,
                'description'      => "عمولة المهمة #{$task->id}{$suffixCombined}",
                'status'           => true,
            ]);
        }

        return $finalInvestorCommission;
    }

    /**
     * إعادة استثمار الأرباح: خصم من محفظة العمولات وإيداع في محفظة المضاربة كرأس مال.
     *
     * @throws \Exception
     */
    public function reinvestProfits(User $investor, float $amount, bool $viaAdmin = false, ?string $notes = null): void
    {
        if ($amount <= 0) {
            throw new \Exception(__('Amount must be greater than zero'));
        }

        $debitDescription = __('Profit reinvestment from commission wallet');
        $creditDescription = __('Profit reinvestment to investment wallet');

        if ($viaAdmin) {
            $debitDescription .= ' (' . __('via administration') . ')';
            $creditDescription .= ' (' . __('via administration') . ')';
        }

        if ($notes) {
            $debitDescription .= ' — ' . $notes;
            $creditDescription .= ' — ' . $notes;
        }

        DB::transaction(function () use ($investor, $amount, $debitDescription, $creditDescription) {
            $personalWallet = UserWallet::lockForUpdate()
                ->where('user_id', $investor->id)
                ->first();

            if (!$personalWallet) {
                throw new \Exception(__('No commission wallet for this investor'));
            }

            $investorWallet = InvestorWallet::lockForUpdate()
                ->where('user_id', $investor->id)
                ->first();

            if (!$investorWallet) {
                throw new \Exception(__('No investment wallet for this investor'));
            }

            $withdrawable = $personalWallet->withdrawable_balance;

            if ($amount > $withdrawable) {
                throw new \Exception(__('Amount exceeds withdrawable balance') . ' ' .
                    number_format($withdrawable, 2));
            }

            UserWalletTransaction::create([
                'user_wallet_id'   => $personalWallet->id,
                'user_id'          => $investor->id,
                'amount'           => $amount,
                'transaction_type' => 'debit',
                'description'      => $debitDescription,
                'status'           => true,
                'maturity_time'    => now(),
            ]);

            $newBalance = $investorWallet->balance + $amount;

            InvestorWalletTransaction::create([
                'investor_wallet_id' => $investorWallet->id,
                'transaction_type'   => 'credit',
                'source_type'        => 'capital',
                'amount'             => $amount,
                'description'        => $creditDescription,
                'performed_by'       => auth()->id() ?? $investor->id,
                'balance_after'      => $newBalance,
            ]);
        });

        $currentBalance = (float) ($investor->investorWallet?->balance ?? 0);
        app(\App\Services\InvestorNotificationService::class)->notifyDeposit(
            $investor,
            $amount,
            $currentBalance,
            'إعادة استثمار أرباح العمولات',
            $notes
        );
    }

    /**
     * إيداع مبلغ في محفظة المضاربة (يستخدمه Admin).
     *
     * @param  User   $investor
     * @param  float  $amount
     * @param  string $description
     */
    public function depositToInvestorWallet(User $investor, float $amount, ?string $description = null, string $sourceType = 'capital'): void
    {
        $description ??= __('Deposit from administration');

        DB::transaction(function () use ($investor, $amount, $description, $sourceType) {
            $wallet = $investor->investorWallet;

            if (!$wallet) {
                throw new \Exception(__('No investment wallet for this investor'));
            }

            $newBalance = $wallet->balance + $amount;

            InvestorWalletTransaction::create([
                'investor_wallet_id' => $wallet->id,
                'transaction_type'   => 'credit',
                'source_type'        => $sourceType,
                'amount'             => $amount,
                'description'        => $description,
                'performed_by'       => auth()->id(),
                'balance_after'      => $newBalance,
            ]);
        });

        $currentBalance = (float) ($investor->investorWallet?->balance ?? 0);
        app(\App\Services\InvestorNotificationService::class)->notifyDeposit(
            $investor,
            $amount,
            $currentBalance,
            $description
        );
    }

    /**
     * إلغاء تمويل المهمة وإجراء عملية عكسية لمحفظة الاستثمار وعمولات المستثمر والوسطاء والشركاء بشكل دقيق.
     *
     * @param  Task       $task
     * @param  User|null  $performer
     * @param  string     $reason
     * @return array
     */
    public function reverseAndRefundCanceledTask(Task $task, ?User $performer = null, string $reason = ''): array
    {
        return DB::transaction(function () use ($task, $performer, $reason) {
            // قفل سجل المهمة لمنع التضارب
            $lockedTask = Task::lockForUpdate()->find($task->id);
            if (!$lockedTask) {
                return ['success' => false, 'message' => 'Task not found'];
            }

            $performerId = $performer?->id ?? auth()->id();
            $refundedCapital = 0.0;
            $capitalTxId = null;
            $reversedCommissionsCount = 0;
            $totalReversedCommission = 0.0;
            $commissionDetails = [];

            // 1. تحديد المستثمر المرتبط بالمهمة (إما من investor_id أو من أول حركة خصم تمويل في محفظة الاستثمار)
            $investorId = $lockedTask->investor_id;
            if (!$investorId) {
                $firstDebit = InvestorWalletTransaction::where('task_id', $lockedTask->id)
                    ->where('transaction_type', 'debit')
                    ->with('wallet')
                    ->first();
                $investorId = $firstDebit?->wallet?->user_id;
            }

            $investor = $investorId ? User::find($investorId) : null;
            $investorWallet = $investorId ? InvestorWallet::lockForUpdate()->where('user_id', $investorId)->first() : null;

            // 2. فحص استرداد رأس المال في محفظة الاستثمار (مع مراعاة العمليات السابقة لضمان Idempotency)
            if ($investorWallet) {
                $iTxs = InvestorWalletTransaction::where('task_id', $lockedTask->id)
                    ->where('investor_wallet_id', $investorWallet->id)
                    ->get();
                $fundedAmount = (float) $iTxs->where('transaction_type', 'debit')->sum('amount');
                $alreadyRefunded = (float) $iTxs->where('transaction_type', 'credit')->sum('amount');
                $netLockedCapital = $fundedAmount - $alreadyRefunded;

                if ($netLockedCapital > 0.001) {
                    $currentBalance = $investorWallet->balance;
                    $balanceAfterCredit = $currentBalance + $netLockedCapital;

                    $reasonText = $reason ? " ({$reason})" : "";
                    $capitalTx = InvestorWalletTransaction::create([
                        'investor_wallet_id' => $investorWallet->id,
                        'task_id'            => $lockedTask->id,
                        'transaction_type'   => 'credit',
                        'source_type'        => 'refund',
                        'amount'             => $netLockedCapital,
                        'description'        => "استرداد رأس مال للمهمة رقم #{$lockedTask->id} بسبب إلغاء المهمة{$reasonText}",
                        'performed_by'       => $performerId,
                        'balance_after'      => $balanceAfterCredit,
                    ]);

                    $refundedCapital = $netLockedCapital;
                    $capitalTxId = $capitalTx->id;
                }
            }

            // 3. فحص وعكس عمولات محافظ المستخدمين (المستثمر، وسطاء العقد، وسطاء المهمة، مستخدمو العميل)
            $uTxs = UserWalletTransaction::where('task_id', $lockedTask->id)->get();
            $walletsAffected = $uTxs->pluck('user_wallet_id')->unique();

            foreach ($walletsAffected as $wId) {
                $wallet = UserWallet::lockForUpdate()->find($wId);
                if (!$wallet) {
                    continue;
                }

                $walletTxs = $uTxs->where('user_wallet_id', $wId);
                $credited = (float) $walletTxs->where('transaction_type', 'credit')->sum('amount');
                $debited = (float) $walletTxs->where('transaction_type', 'debit')->sum('amount');
                $netComm = $credited - $debited;

                if ($netComm > 0.001) {
                    // تحديد صفة صاحب المحفظة لصياغة الوصف المالي بدقة
                    $walletOwner = $wallet->user;
                    $roleLabel = 'عمولة';
                    if ($walletOwner && $investorId && $walletOwner->id == $investorId) {
                        $roleLabel = 'عمولة المستثمر';
                    } elseif ($walletOwner && $walletOwner->investor_broker) {
                        $roleLabel = 'عمولة وسيط المستثمر';
                    } elseif ($walletOwner && $walletOwner->is_broker) {
                        $roleLabel = 'عمولة الوسيط';
                    } elseif ($walletOwner) {
                        $roleLabel = 'عمولة المستخدم (' . $walletOwner->name . ')';
                    }

                    $reasonText = $reason ? " - السبب: {$reason}" : "";
                    $desc = "خصم عكسي لـ {$roleLabel} للمهمة #{$lockedTask->id} بسبب إلغاء المهمة{$reasonText}";

                    UserWalletTransaction::create([
                        'user_wallet_id'   => $wId,
                        'user_id'          => $performerId,
                        'task_id'          => $lockedTask->id,
                        'transaction_type' => 'debit',
                        'amount'           => $netComm,
                        'description'      => $desc,
                        'status'           => true,
                        'maturity_time'    => now(),
                    ]);

                    $totalReversedCommission += $netComm;
                    $reversedCommissionsCount++;
                    $commissionDetails[] = [
                        'wallet_id' => $wId,
                        'user_id'   => $wallet->user_id,
                        'amount'    => $netComm,
                        'role'      => $roleLabel,
                    ];
                }
            }

            // 4. فك ارتباط المستثمر وتحديث حالة التمويل للمهمة إذا كانت مرتبطة بمستثمر
            $oldInvestorId = $lockedTask->investor_id;
            $oldInvestorPaymentStatus = $lockedTask->investor_payment_status;

            if ($oldInvestorId !== null || $oldInvestorPaymentStatus !== 'none') {
                $lockedTask->update([
                    'investor_id'             => null,
                    'investor_payment_status' => 'none',
                ]);
            }

            // 5. تسجيل العملية في سجل تدقيق المهمة Task_History
            if ($refundedCapital > 0 || $totalReversedCommission > 0) {
                $historyNotes = [];
                if ($refundedCapital > 0) {
                    $investorName = $investor ? $investor->name : "المستثمر #{$investorId}";
                    $historyNotes[] = "استرداد رأس المال بمبلغ " . number_format($refundedCapital, 2) . " ر.س في محفظة الاستثمار ({$investorName})";
                }
                if ($totalReversedCommission > 0) {
                    $historyNotes[] = "خصم عكسي للعمولات بمبلغ إجمالي " . number_format($totalReversedCommission, 2) . " ر.س من {$reversedCommissionsCount} محفظة";
                }
                $historyNotes[] = "فك ارتباط المستثمر بالمهمة";

                $performerName = $performer ? $performer->name : (auth()->user()?->name ?? 'النظام التلقائي');
                $logDesc = "تمت المعالجة العكسية للمهمة الملغاة #{$lockedTask->id}: " . implode('، ', $historyNotes) . ". بواسطة: {$performerName}" . ($reason ? " ({$reason})" : "");

                Task_History::create([
                    'task_id'     => $lockedTask->id,
                    'action_type' => 'cancellation_financial_reversal',
                    'description' => $logDesc,
                    'user_id'     => $performerId,
                    'ip'          => class_exists('\App\Helpers\IpHelper') ? \App\Helpers\IpHelper::getUserIpAddress() : request()->ip(),
                ]);
            }

            return [
                'success'                    => true,
                'refunded_capital'           => $refundedCapital,
                'capital_tx_id'              => $capitalTxId,
                'reversed_commission_total'  => $totalReversedCommission,
                'reversed_commissions_count' => $reversedCommissionsCount,
                'commission_details'         => $commissionDetails,
                'already_settled'            => ($refundedCapital <= 0.001 && $totalReversedCommission <= 0.001),
            ];
        });
    }
}
