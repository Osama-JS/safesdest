<?php

namespace App\Observers;

use App\Models\Task_Offire;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;

class TaskOfferObserver
{
    protected $adminEmail = 'info@safedest.com';

    /**
     * Handle the Task_Offire "created" event.
     *
     * @param  \App\Models\Task_Offire  $offer
     * @return void
     */
    public function created(Task_Offire $offer)
    {
        Log::info("TaskOfferObserver: New offer created for task ad #{$offer->task_ad_id} by driver #{$offer->driver_id}");

        $task = $offer->ad->task;
        $content = "تمت إضافة عرض جديد لمهمة برقم #{$task->id}.\n" .
                   "- اسم السائق: " . ($offer->driver->name ?? 'N/A') . "\n" .
                   "- السعر المعروض: {$offer->price}\n" .
                   "- ملاحظات: " . ($offer->description ?? 'لا يوجد');

        $this->notifyManager($offer, "عرض جديد على مهمة #{$task->id}", $content);
    }

    /**
     * Handle the Task_Offire "updated" event.
     *
     * @param  \App\Models\Task_Offire  $offer
     * @return void
     */
    public function updated(Task_Offire $offer)
    {
        // Check if offer was accepted
        if ($offer->isDirty('accepted') && $offer->accepted) {
            Log::info("TaskOfferObserver: Offer accepted for task ad #{$offer->task_ad_id}");

            $task = $offer->ad->task;
            $content = "تم قبول عرض السائق (" . ($offer->driver->name ?? 'N/A') . ") للمهمة رقم #{$task->id}.\n" .
                       "- السعر المتفق عليه: {$offer->price}";

            $this->notifyManager($offer, "قبول عرض مهمة #{$task->id}", $content);
        }
    }

    /**
     * Send email notification to manager.
     */
    /**
     * Send email notification to manager.
     */
    protected function notifyManager($offer, $subject, $content)
    {
        try {
            // 1. Create notification in database
            $this->createNotificationRecord($subject, $content);

            $task = $offer->ad->task;
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
                    'offer_id' => $offer->id,
                    'price' => $offer->price,
                    'action_url' => url("/admin/tasks/{$task->id}")
                ]
            ];

            dispatch(new SendEmailNotificationJob($emailData));
        } catch (\Exception $e) {
            Log::error("TaskOfferObserver Error: " . $e->getMessage());
        }
    }

    /**
     * Create notification record in database.
     */
    protected function createNotificationRecord($title, $message)
    {
        try {
            // 1. Create the notification
            $notification = \App\Models\Notification::create([
                'title' => $title,
                'message' => $message,
                'group' => 'users',
                'type' => 'by person'
            ]);

            // 2. Link to admin user
            $adminUser = \App\Models\User::where('email', $this->adminEmail)->first();

            if ($adminUser) {
                \App\Models\Notification_Users::create([
                    'notification_id' => $notification->id,
                    'user_id' => $adminUser->id,
                    'status' => false // Unread
                ]);
            }
        } catch (\Exception $e) {
            Log::error("TaskOfferObserver: Failed to save notification to DB: " . $e->getMessage());
        }
    }
}
