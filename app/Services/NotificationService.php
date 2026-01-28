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


    /**
     * Send generic notification
     */
    public function send($type, array $ids, $title, $body, $icon = null, $image = null, $url = null, $notif_type = 'general')
    {
        // For general notifications, user wants "Process of sending notifications" to be a job.
        // However, this method iterates IDs and sends one by one (or uses multicast if rewritten).
        // Current implementation: loops and sends individually.
        // I will keep the loop but dispatch a job for each recipient to be safe and scalable.

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
            // 1. Firebase (FCM)
            try {
                if (isset($recipient->fcm_token) && $recipient->fcm_token) {
                    \App\Jobs\SendFirebaseNotification::dispatch(
                        'mainSendNotification',
                        [$recipient->fcm_token, $title, $body, $notif_type]
                    );
                }
            } catch (\Exception $e) {
                Log::error("Failed to dispatch Firebase notification to ID {$recipient->id}: " . $e->getMessage());
            }

            // 2. Web Push (Browser) - specifically for User/Customer/Driver who might have subscriptions
            try {
                if ($recipient instanceof \App\Models\User || $type === 'user') {
                    $recipient->notify(new \App\Notifications\GeneralPushNotification([
                        'title' => $title,
                        'body' => $body,
                        'icon' => $icon ?? '/images/admin-icon.png',
                        'image' => $image,
                        'url' => $url,
                        'type' => $notif_type,
                    ]));
                }
            } catch (\Exception $e) {
                Log::error("Failed to dispatch WebPush notification to ID {$recipient->id}: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Send Firebase notification to driver for new task
     */
    public function sendNewTaskNotificationToDriver($driver, $task)
    {
        try {
            // Dispatch the job
            // Note: driver and task models will be serialized
            \App\Jobs\SendFirebaseNotification::dispatch(
                'sendNewTaskNotification',
                [$driver, $task]
            );

            Log::info('New task notification queued for driver', [
                'driver_id' => $driver->id,
                'task_id' => $task->id
            ]);

            return [
                'success' => true,
                'message' => 'Notification queued successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Exception in sendNewTaskNotificationToDriver queueing', [
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
            \App\Jobs\SendFirebaseNotification::dispatch(
                'sendTaskUpdateNotification',
                [$driver, $task, $status]
            );

            Log::info('Task update notification queued for driver', [
                'driver_id' => $driver->id,
                'task_id' => $task->id,
                'status' => $status
            ]);

            return [
                'success' => true,
                'message' => 'Notification queued successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Exception in sendTaskUpdateNotificationToDriver queueing', [
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
            \App\Jobs\SendFirebaseNotification::dispatch(
                'sendPaymentNotification',
                [$driver, $amount, $type]
            );

            Log::info('Payment notification queued for driver', [
                'driver_id' => $driver->id,
                'amount' => $amount,
                'type' => $type
            ]);

            return [
                'success' => true,
                'message' => 'Notification queued successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Exception in sendPaymentNotificationToDriver queueing', [
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
    /**
     * Notify all users in the team about task activity
     */
    public function notifyTeamUsers(\App\Models\Task $task, $title, $body, $url = '/', $notifType = 'team_activity')
    {
        try {
            if (!$task->team_id) {
                return;
            }

            $team = \App\Models\Teams::find($task->team_id);
            if (!$team) {
                return;
            }

            // Get all user_has_teams entries and eager load users
            $teamUsers = $team->users()->with('user')->get();

            foreach ($teamUsers as $tu) {
                try {
                    $user = $tu->user;
                    if ($user) {
                        $user->notify(new \App\Notifications\TeamActivityNotification([
                            'title' => $title,
                            'body' => $body,
                            'url' => $url,
                            'type' => $notifType,
                            'task_id' => $task->id
                        ]));
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send WebPush/Database notification to User ID {$tu->user_id}: " . $e->getMessage());
                }
            }

            Log::info("Team activity notification sent to team #{$task->team_id} for Task #{$task->id}");
        } catch (\Exception $e) {
            Log::error("Error notifying team users: " . $e->getMessage());
        }
    }
}
