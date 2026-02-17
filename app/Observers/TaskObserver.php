<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\Customer;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TaskObserver
{
    protected $adminEmail = 'nawafmh81@gmail.com';

    /**
     * Handle the Task "created" event.
     *
     * @param  \App\Models\Task  $task
     * @return void
     */
    public function created(Task $task)
    {
        Log::info("TaskObserver: New task created #{$task->id}");

        // Auto-assign customer task number if the customer has custom numbering
        if ($task->customer_id) {
            try {
                DB::transaction(function () use ($task) {
                    $customer = Customer::where('id', $task->customer_id)->lockForUpdate()->first();

                    if ($customer && $customer->hasCustomTaskNumbering()) {
                        $nextNumber = $customer->task_number_next ?? $customer->task_number_start;

                        $task->customer_task_number = $nextNumber;
                        $task->saveQuietly(); // Avoid re-triggering observer

                        $customer->task_number_next = $nextNumber + 1;
                        $customer->saveQuietly();

                        Log::info("TaskObserver: Assigned customer_task_number={$nextNumber} to task #{$task->id} for customer #{$customer->id}");
                    }
                });
            } catch (\Exception $e) {
                Log::error("TaskObserver: Failed to assign customer task number for task #{$task->id}: " . $e->getMessage());
            }
        }

        $this->notifyManager($task, 'إنشاء مهمة جديدة', "تم إنشاء مهمة جديدة رقم #{$task->id} في المنصة.");
    }

    /**
     * Handle the Task "updated" event.
     *
     * @param  \App\Models\Task  $task
     * @return void
     */
    public function updated(Task $task)
    {
        $changes = [];

        // 1. Check for status change
        if ($task->isDirty('status')) {
            $oldStatus = $task->getOriginal('status');
            $newStatus = $task->status;

            if ($oldStatus === 'advertised' && $newStatus === 'assign') {
                $changes[] = "تم قبول المهمة من قبل السائق (أو تم تعيينها له).";
            } elseif ($newStatus === 'accepted') {
                $changes[] = "تم قبول المهمة رسمياً من قبل السائق.";
            } else {
                $changes[] = "تغيرت حالة المهمة من '{$oldStatus}' إلى '{$newStatus}'.";
            }
        }

        // 2. Check for driver assignment specifically
        if ($task->isDirty('driver_id') && $task->driver_id) {
            $changes[] = "تم تعيين السائق ID: {$task->driver_id} للمهمة.";
        }

        // 3. Check for payment status/method changes
        if ($task->isDirty('payment_status') || $task->isDirty('payment_method')) {
            $changes[] = "تحديث في تفاصيل الدفع (الحالة: {$task->payment_status}، الطريقة: {$task->payment_method}).";
        }

        // 4. Check for closing
        if ($task->isDirty('closed') && $task->closed) {
            $changes[] = "تم إقفال المهمة نهائياً.";
        }

        // 5. Check for refund/return (based on status or other fields if available)
        // Note: 'refunded' was identified earlier
        if ($task->isDirty('status') && $task->status === 'refunded') {
            $changes[] = "تم إرجاع المهمة (Refunded).";
        }

        // 6. Check for driver cancellation request
        if ($task->isDirty('driver_cancel') && $task->driver_cancel) {
            $reason = $task->driver_cancel_reason ?? 'لم يتم تحديد سبب';
            $driverName = optional($task->driver)->name ?? 'غير معروف';
            $changes[] = "⚠️ طلب السائق ({$driverName}) إلغاء المهمة. السبب: {$reason}";
        }

        // 7. Check for customer cancellation request
        if ($task->isDirty('customer_cancel') && $task->customer_cancel) {
            $reason = $task->customer_cancel_reason ?? 'لم يتم تحديد سبب';
            $customerName = optional($task->customer)->name ?? 'غير معروف';
            $changes[] = "⚠️ طلب العميل ({$customerName}) إلغاء المهمة. السبب: {$reason}";
        }

        // 8. Generic update if other important info changed but no specific event caught
        if (empty($changes) && $task->isDirty(['total_price', 'commission', 'additional_data'])) {
            $changes[] = "تم تعديل بيانات أساسية في المهمة (السعر، العمولة، أو البيانات الإضافية).";
        }

        if (!empty($changes)) {
            Log::info("TaskObserver: Task updated #{$task->id}", ['changes' => $changes]);
            $content = "تم تحديث المهمة رقم #{$task->id}. التفاصيل:\n- " . implode("\n- ", $changes);
            $this->notifyManager($task, "تحديث مهمة #{$task->id}", $content);
        }
    }

    /**
     * Handle the Task "deleted" event.
     *
     * @param  \App\Models\Task  $task
     * @return void
     */
    public function deleted(Task $task)
    {
        Log::info("TaskObserver: Task deleted #{$task->id}");
        $this->notifyManager($task, "حذف مهمة #{$task->id}", "تم حذف المهمة رقم #{$task->id} من المنصة.");
    }

    /**
     * Send email notification to manager.
     *
     * @param Task $task
     * @param string $subject
     * @param string $content
     */
    protected function notifyManager(Task $task, $subject, $content)
    {
        try {
            Log::info("TaskObserver: Dispatching email notification for task #{$task->id}");
            $emailData = [
                'to' => $this->adminEmail,
                'subject' => "[Safedest Admin] " . $subject,
                'content' => $content,
                'user_name' => 'مدير المنصة',
                'template' => 'emails.notification',
                'type' => 'admin_alert',
                'priority' => 'high',
                'additional_data' => [
                    'task_id' => $task->id,
                    'status' => $task->status,
                    'customer' => $task->customer_id ? ($task->customer->name ?? 'N/A') : 'N/A',
                    'action_url' => url("/admin/tasks/{$task->id}")
                ]
            ];

            // Use the existing Job to send email in background
            dispatch(new SendEmailNotificationJob($emailData));

        } catch (\Exception $e) {
            Log::error("TaskObserver Error dispatching email for task #{$task->id}: " . $e->getMessage());
        }
    }
}
