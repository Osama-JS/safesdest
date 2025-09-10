<?php

namespace App\Services;

use App\Models\User;
use App\Models\Driver;
use App\Models\Customer;
use App\Notifications\GeneralPushNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $firebaseService;

    public function __construct()
    {
        $this->firebaseService = new FirebaseService();
    }

    //     foreach ($recipients as $recipient) {
    //         $recipient->notify(new GeneralPushNotification([
    //             'title' => $title,
    //             'body' => $body,
    //             'icon' => $icon ?? '/images/admin-icon.png',
    //             'image' => $image ?? '/images/banner.png',
    //             'url' => $url ?? '/',
    //             'type' => $notif_type,
    //         ]));
    //     }

    //     return true;
    // }

    public function send($type, array $ids, $title, $body, $icon = null, $image = null, $url = null, $notif_type = 'general')
    {
        $modelMap = [
            'user' => User::class,
            'driver' => Driver::class,
            'customer' => Customer::class,
        ];

        if (!isset($modelMap[$type])) {
            throw new \Exception("نوع المستلم غير صحيح");
        }

        $recipients = $modelMap[$type]::whereIn('id', $ids)->get();

        foreach ($recipients as $recipient) {
            Log::alert('start send notification');

            if (isset($recipient->fcm_token) && $recipient->fcm_token) {
                Log::alert('start send notification from firebase');
                $result = $this->firebaseService->mainSendNotification($recipient->fcm_token, $title, $body, $notif_type);
            }
            // التأكد أن الموديل يدعم notify ومشترك في الإشعارات
            // if (method_exists($recipient, 'notify')) {
            //     $recipient->notify(new GeneralPushNotification([
            //         'title' => $title,
            //         'body' => $body,
            //         'icon' => $icon ?? '/images/admin-icon.png',
            //         'image' => $image ?? '/images/banner.png',
            //         'url' => $url ?? '/',
            //         'type' => $notif_type,
            //     ]));
            // }



            Log::alert("message: ".$recipient->name);

        }

        return true;
    }

    /**
     * Send Firebase notification to driver for new task
     */
    public function sendNewTaskNotificationToDriver($driver, $task)
    {
        try {
            $result = $this->firebaseService->sendNewTaskNotification($driver, $task);

            if ($result['success']) {
                Log::info('New task notification sent to driver', [
                    'driver_id' => $driver->id,
                    'task_id' => $task->id
                ]);
            } else {
                Log::error('Failed to send new task notification', [
                    'driver_id' => $driver->id,
                    'task_id' => $task->id,
                    'error' => $result['message']
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception in sendNewTaskNotificationToDriver', [
                'driver_id' => $driver->id,
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send Firebase notification to driver for task update
     */
    public function sendTaskUpdateNotificationToDriver($driver, $task, $status)
    {
        try {
            $result = $this->firebaseService->sendTaskUpdateNotification($driver, $task, $status);

            if ($result['success']) {
                Log::info('Task update notification sent to driver', [
                    'driver_id' => $driver->id,
                    'task_id' => $task->id,
                    'status' => $status
                ]);
            } else {
                Log::error('Failed to send task update notification', [
                    'driver_id' => $driver->id,
                    'task_id' => $task->id,
                    'status' => $status,
                    'error' => $result['message']
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception in sendTaskUpdateNotificationToDriver', [
                'driver_id' => $driver->id,
                'task_id' => $task->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send Firebase notification to driver for payment
     */
    public function sendPaymentNotificationToDriver($driver, $amount, $type = 'payment_received')
    {
        try {
            $result = $this->firebaseService->sendPaymentNotification($driver, $amount, $type);

            if ($result['success']) {
                Log::info('Payment notification sent to driver', [
                    'driver_id' => $driver->id,
                    'amount' => $amount,
                    'type' => $type
                ]);
            } else {
                Log::error('Failed to send payment notification', [
                    'driver_id' => $driver->id,
                    'amount' => $amount,
                    'type' => $type,
                    'error' => $result['message']
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception in sendPaymentNotificationToDriver', [
                'driver_id' => $driver->id,
                'amount' => $amount,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

}
