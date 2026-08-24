<?php

namespace App\Services;

use App\Mail\InvestorWalletNotificationMail;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvestorNotificationService
{
    /**
     * إرسال إشعار تسوية واسترداد رأس مال مجمع للمستثمر (عند سداد العميل أو التسوية اليدوية)
     *
     * @param User   $investor
     * @param float  $totalAmount
     * @param array  $taskIds
     * @param float  $newBalance
     * @param string $source ('customer_payment', 'manual_admin', 'online_payment')
     * @param string|null $note
     */
    public function notifySettlement(
        User $investor,
        float $totalAmount,
        array $taskIds,
        float $newBalance,
        string $source = 'customer_payment',
        ?string $note = null
    ): void {
        if (empty($investor->email) || $totalAmount <= 0) {
            return;
        }

        $tasksCount = count($taskIds);
        $sourceTitles = [
            'customer_payment' => 'سداد العميل من المحفظة',
            'manual_admin'     => 'تسوية يدوية من الإدارة',
            'online_payment'   => 'سداد إلكتروني مباشر للمهمة',
        ];

        $sourceTitle = $sourceTitles[$source] ?? 'تسوية واسترداد رأس مال';
        $tasksText = $tasksCount > 1 ? "لعدد {$tasksCount} مهام" : ($tasksCount === 1 ? "للمهمة #{$taskIds[0]}" : "");

        $mailData = [
            'subject'          => "استرداد وتسوية رأس مال الاستثمار {$tasksText} بقيمة " . number_format($totalAmount, 2) . " ر.س",
            'investor_name'    => $investor->name,
            'intro_message'    => "نود إعلامك بأنه تم بنجاح تسوية واسترداد رأس المال {$tasksText} وإضافته إلى رصيد محفظة الاستثمار الخاصة بك.",
            'badge_title'      => 'تسوية واسترداد رأس مال',
            'amount'           => $totalAmount,
            'transaction_type' => 'credit',
            'new_balance'      => $newBalance,
            'operation_title'  => "استرداد رأس مال ({$sourceTitle})",
            'tasks_count'      => $tasksCount,
            'task_ids'         => $taskIds,
            'note'             => $note,
            'date_time'        => now()->format('Y-m-d H:i'),
            'action_url'       => url('/investor/investment-wallet'),
        ];

        $this->sendEmail($investor->email, $mailData);
    }

    /**
     * إرسال إشعار للمستثمر عند استثمار/تمويل مهام (مفردة أو جماعية)
     *
     * @param User   $investor
     * @param float  $totalAmount
     * @param array  $taskIds
     * @param float  $newBalance
     * @param string|null $note
     */
    public function notifyTaskInvestment(
        User $investor,
        float $totalAmount,
        array $taskIds,
        float $newBalance,
        ?string $note = null
    ): void {
        if (empty($investor->email) || $totalAmount <= 0) {
            return;
        }

        $tasksCount = count($taskIds);
        $tasksText = $tasksCount > 1 ? "لعدد {$tasksCount} مهام" : ($tasksCount === 1 ? "للمهمة #{$taskIds[0]}" : "");

        $mailData = [
            'subject'          => "تأكيد استثمار وتمويل {$tasksText} بقيمة " . number_format($totalAmount, 2) . " ر.س",
            'investor_name'    => $investor->name,
            'intro_message'    => "تم بنجاح خصم قيمة تمويل المهام {$tasksText} من محفظة الاستثمار الخاصة بك وبدء احتساب أرباح العقد.",
            'badge_title'      => 'استثمار في المهام',
            'amount'           => $totalAmount,
            'transaction_type' => 'debit',
            'new_balance'      => $newBalance,
            'operation_title'  => 'تمويل ودفع قيمة مهام',
            'tasks_count'      => $tasksCount,
            'task_ids'         => $taskIds,
            'note'             => $note,
            'date_time'        => now()->format('Y-m-d H:i'),
            'action_url'       => url('/investor/tasks/paid'),
        ];

        $this->sendEmail($investor->email, $mailData);
    }

    /**
     * إرسال إشعار إيداع رأس مال في المحفظة
     *
     * @param User   $investor
     * @param float  $amount
     * @param float  $newBalance
     * @param string $operationTitle
     * @param string|null $note
     */
    public function notifyDeposit(
        User $investor,
        float $amount,
        float $newBalance,
        string $operationTitle = 'إيداع رأس مال',
        ?string $note = null
    ): void {
        if (empty($investor->email) || $amount <= 0) {
            return;
        }

        $mailData = [
            'subject'          => "إيداع جديد في محفظة الاستثمار بقيمة " . number_format($amount, 2) . " ر.س",
            'investor_name'    => $investor->name,
            'intro_message'    => "تمت إضافة دفعة جديدة بنجاح إلى رصيد محفظة الاستثمار والمضاربة الخاصة بك.",
            'badge_title'      => 'إيداع رأس مال',
            'amount'           => $amount,
            'transaction_type' => 'credit',
            'new_balance'      => $newBalance,
            'operation_title'  => $operationTitle,
            'tasks_count'      => 0,
            'task_ids'         => [],
            'note'             => $note,
            'date_time'        => now()->format('Y-m-d H:i'),
            'action_url'       => url('/investor/investment-wallet'),
        ];

        $this->sendEmail($investor->email, $mailData);
    }

    /**
     * إرسال إشعار استرداد رأس مال لمهمة ملغاة / مستردة
     *
     * @param User   $investor
     * @param float  $amount
     * @param int    $taskId
     * @param float  $newBalance
     * @param string|null $note
     */
    public function notifyRefund(
        User $investor,
        float $amount,
        int $taskId,
        float $newBalance,
        ?string $note = null
    ): void {
        if (empty($investor->email) || $amount <= 0) {
            return;
        }

        $mailData = [
            'subject'          => "إرجاع رأس مال المهمة المستردة #{$taskId} بقيمة " . number_format($amount, 2) . " ر.س",
            'investor_name'    => $investor->name,
            'intro_message'    => "نظراً لإلغاء/استرداد المهمة #{$taskId}، تم إرجاع رأس المال كاملاً إلى رصيد محفظة الاستثمار الخاصة بك.",
            'badge_title'      => 'استرداد مهمة ملغاة',
            'amount'           => $amount,
            'transaction_type' => 'credit',
            'new_balance'      => $newBalance,
            'operation_title'  => "استرداد رأس مال للمهمة الملغاة #{$taskId}",
            'tasks_count'      => 1,
            'task_ids'         => [$taskId],
            'note'             => $note,
            'date_time'        => now()->format('Y-m-d H:i'),
            'action_url'       => url('/investor/investment-wallet'),
        ];

        $this->sendEmail($investor->email, $mailData);
    }

    /**
     * إرسال البريد الإلكتروني مع معالجة الأخطاء لضمان عدم تعطل المعاملات المالية
     *
     * @param string $recipientEmail
     * @param array  $mailData
     */
    protected function sendEmail(string $recipientEmail, array $mailData): void
    {
        try {
            Mail::to($recipientEmail)->send(new InvestorWalletNotificationMail($mailData));

            Log::info('Investor wallet notification email sent successfully', [
                'recipient' => $recipientEmail,
                'subject'   => $mailData['subject'] ?? '',
                'amount'    => $mailData['amount'] ?? 0,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send investor wallet notification email', [
                'recipient' => $recipientEmail,
                'subject'   => $mailData['subject'] ?? '',
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
