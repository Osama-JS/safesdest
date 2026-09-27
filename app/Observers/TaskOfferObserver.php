<?php

namespace App\Observers;

use App\Models\Task_Offire;
use App\Services\AdminNotificationDispatcher;
use Illuminate\Support\Facades\Log;

class TaskOfferObserver
{
    /**
     * Handle the Task_Offire "created" event.
     */
    public function created(Task_Offire $offer)
    {
        Log::info("TaskOfferObserver: New offer created for task ad #{$offer->task_ad_id} by driver #{$offer->driver_id}");

        $task = $offer->ad?->task;
        if (!$task) return;

        $driverName = $offer->driver->name ?? 'غير محدد';
        $content = "تمت إضافة عرض جديد لمهمة برقم #{$task->id}.\n" .
                   "- اسم السائق: {$driverName}\n" .
                   "- السعر المعروض: {$offer->price} ر.س\n" .
                   "- ملاحظات: " . ($offer->description ?? 'لا يوجد');

        AdminNotificationDispatcher::dispatch(
            eventKey: 'task_offer_created',
            title: "عرض جديد على مهمة #{$task->id}",
            message: $content,
            actionUrl: url("/admin/tasks/{$task->id}"),
            extraData: [
                'task_id'     => $task->id,
                'offer_id'    => $offer->id,
                'driver_id'   => $offer->driver_id,
                'driver_name' => $driverName,
                'price'       => $offer->price,
            ]
        );
    }

    /**
     * Handle the Task_Offire "updated" event.
     */
    public function updated(Task_Offire $offer)
    {
        // Check if offer was accepted
        if ($offer->isDirty('accepted') && $offer->accepted) {
            Log::info("TaskOfferObserver: Offer accepted for task ad #{$offer->task_ad_id}");

            $task = $offer->ad?->task;
            if (!$task) return;

            $driverName = $offer->driver->name ?? 'غير محدد';
            $content = "تم قبول عرض السائق ({$driverName}) للمهمة رقم #{$task->id}.\n" .
                       "- السعر المتفق عليه: {$offer->price} ر.س";

            AdminNotificationDispatcher::dispatch(
                eventKey: 'task_offer_accepted',
                title: "قبول عرض مهمة #{$task->id}",
                message: $content,
                actionUrl: url("/admin/tasks/{$task->id}"),
                extraData: [
                    'task_id'     => $task->id,
                    'offer_id'    => $offer->id,
                    'driver_id'   => $offer->driver_id,
                    'driver_name' => $driverName,
                    'price'       => $offer->price,
                ]
            );
        }
    }
}
