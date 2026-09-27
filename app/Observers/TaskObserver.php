<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\Customer;
use App\Services\AdminNotificationDispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
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

        $customerName = optional($task->customer)->name ?? 'غير محدد';
        $content = "تم إنشاء مهمة جديدة برقم #{$task->id}.\n" .
                   "- العميل: {$customerName}\n" .
                   "- الحالة: {$task->status}";

        AdminNotificationDispatcher::dispatch(
            eventKey: 'task_created',
            title: "إنشاء مهمة جديدة #{$task->id}",
            message: $content,
            actionUrl: url("/admin/tasks/{$task->id}"),
            extraData: [
                'task_id'     => $task->id,
                'customer_id' => $task->customer_id,
                'status'      => $task->status,
            ]
        );
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task)
    {
        $changes = [];
        $isCancellation = false;

        // 1. Check for cancellation requests first (High priority)
        if ($task->isDirty('driver_cancel') && $task->driver_cancel) {
            $isCancellation = true;
            $reason = $task->driver_cancel_reason ?? 'لم يتم تحديد سبب';
            $driverName = optional($task->driver)->name ?? 'غير معروف';
            $changes[] = "⚠️ طلب السائق ({$driverName}) إلغاء المهمة. السبب: {$reason}";
        }

        if ($task->isDirty('customer_cancel') && $task->customer_cancel) {
            $isCancellation = true;
            $reason = $task->customer_cancel_reason ?? 'لم يتم تحديد سبب';
            $customerName = optional($task->customer)->name ?? 'غير معروف';
            $changes[] = "⚠️ طلب العميل ({$customerName}) إلغاء المهمة. السبب: {$reason}";
        }

        // 2. Check for status change
        if ($task->isDirty('status')) {
            $oldStatus = $task->getOriginal('status');
            $newStatus = $task->status;

            if ($oldStatus === 'advertised' && $newStatus === 'assign') {
                $changes[] = "تم تعيين المهمة للسائق.";
            } elseif ($newStatus === 'accepted') {
                $changes[] = "تم قبول المهمة رسمياً من قبل السائق.";
            } elseif ($newStatus === 'refunded') {
                $changes[] = "تم إرجاع المهمة (Refunded).";
            } else {
                $changes[] = "تغيرت حالة المهمة من '{$oldStatus}' إلى '{$newStatus}'.";
            }
        }

        // 3. Check for driver assignment specifically
        if ($task->isDirty('driver_id') && $task->driver_id) {
            $driverName = optional($task->driver)->name ?? "ID: {$task->driver_id}";
            $changes[] = "تم تعيين السائق ({$driverName}) للمهمة.";
        }

        // 4. Check for payment status/method changes
        if ($task->isDirty('payment_status') || $task->isDirty('payment_method')) {
            $changes[] = "تحديث في تفاصيل الدفع (الحالة: {$task->payment_status}، الطريقة: {$task->payment_method}).";
        }

        // 5. Check for closing
        if ($task->isDirty('closed') && $task->closed) {
            $changes[] = "تم إقفال المهمة نهائياً.";
        }

        // 6. Generic update if other important info changed
        if (empty($changes) && $task->isDirty(['total_price', 'commission', 'additional_data'])) {
            $changes[] = "تم تعديل بيانات أساسية في المهمة (السعر، العمولة، أو البيانات الإضافية).";
        }

        if (!empty($changes)) {
            Log::info("TaskObserver: Task updated #{$task->id}", ['changes' => $changes]);
            $content = "تم تحديث المهمة رقم #{$task->id}. التفاصيل:\n- " . implode("\n- ", $changes);

            $eventKey = $isCancellation ? 'task_cancellation_requested' : 'task_status_changed';
            $title = $isCancellation ? "⚠️ طلب إلغاء للمهمة #{$task->id}" : "تحديث مهمة #{$task->id}";

            AdminNotificationDispatcher::dispatch(
                eventKey: $eventKey,
                title: $title,
                message: $content,
                actionUrl: url("/admin/tasks/{$task->id}"),
                priority: $isCancellation ? 'high' : 'normal',
                extraData: [
                    'task_id'     => $task->id,
                    'status'      => $task->status,
                    'is_cancel'   => $isCancellation,
                    'changes'     => $changes,
                ]
            );
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task)
    {
        Log::info("TaskObserver: Task deleted #{$task->id}");

        AdminNotificationDispatcher::dispatch(
            eventKey: 'task_status_changed',
            title: "حذف مهمة #{$task->id}",
            message: "تم حذف المهمة رقم #{$task->id} من المنصة.",
            actionUrl: url("/admin/tasks"),
            extraData: [
                'task_id' => $task->id,
                'action'  => 'deleted'
            ]
        );
    }
}
