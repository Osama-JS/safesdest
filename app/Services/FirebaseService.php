<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $messaging;
    protected $factory;

    public function __construct()
    {
        try {
            $this->factory = (new Factory())
                ->withServiceAccount(storage_path(config('services.firebase.credentials')))
                ->withProjectId(config('services.firebase.project_id'));

            $this->messaging = $this->factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization error: ' . $e->getMessage());
        }
    }



    /**
     * Send notification to a single device
     */
    public function sendToDevice(string $fcmToken, string $title, string $body, array $data = [])
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData($data);

            $result = $this->messaging->send($message);

            Log::info('FCM notification sent successfully', [
                'token' => $fcmToken,
                'title' => $title,
                'result' => $result
            ]);

            return [
                'success' => true,
                'message' => 'Notification sent successfully',
                'result' => $result
            ];

        } catch (MessagingException $e) {
            Log::error('FCM messaging error', [
                'token' => $fcmToken,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to multiple devices
     */
    public function sendToMultipleDevices(array $fcmTokens, string $title, string $body, array $data = [])
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);

            $result = $this->messaging->sendMulticast($message, $fcmTokens);

            Log::info('FCM multicast notification sent', [
                'tokens_count' => count($fcmTokens),
                'title' => $title,
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ]);

            return [
                'success' => true,
                'message' => 'Notifications sent successfully',
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count(),
                'failures' => $result->failures()->getItems()
            ];

        } catch (MessagingException $e) {
            Log::error('FCM multicast messaging error', [
                'tokens_count' => count($fcmTokens),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notifications: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [])
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification($notification)
                ->withData($data);

            $result = $this->messaging->send($message);

            Log::info('FCM topic notification sent', [
                'topic' => $topic,
                'title' => $title,
                'result' => $result
            ]);

            return [
                'success' => true,
                'message' => 'Topic notification sent successfully',
                'result' => $result
            ];

        } catch (MessagingException $e) {
            Log::error('FCM topic messaging error', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send topic notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Subscribe tokens to a topic
     */
    public function subscribeToTopic(array $fcmTokens, string $topic)
    {
        try {
            $result = $this->messaging->subscribeToTopic($topic, $fcmTokens);

            Log::info('FCM topic subscription', [
                'topic' => $topic,
                'tokens_count' => count($fcmTokens),
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ]);

            return [
                'success' => true,
                'message' => 'Subscribed to topic successfully',
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ];

        } catch (MessagingException $e) {
            Log::error('FCM topic subscription error', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to subscribe to topic: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Unsubscribe tokens from a topic
     */
    public function unsubscribeFromTopic(array $fcmTokens, string $topic)
    {
        try {
            $result = $this->messaging->unsubscribeFromTopic($topic, $fcmTokens);

            Log::info('FCM topic unsubscription', [
                'topic' => $topic,
                'tokens_count' => count($fcmTokens),
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ]);

            return [
                'success' => true,
                'message' => 'Unsubscribed from topic successfully',
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count()
            ];

        } catch (MessagingException $e) {
            Log::error('FCM topic unsubscription error', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to unsubscribe from topic: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate FCM token
     */
    public function validateToken(string $fcmToken)
    {
        try {
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withData(['validate' => 'true']);

            $this->messaging->validate($message);

            return [
                'success' => true,
                'message' => 'Token is valid'
            ];

        } catch (MessagingException $e) {
            return [
                'success' => false,
                'message' => 'Token is invalid: ' . $e->getMessage()
            ];
        }
    }

    /**
      * main function to Send notification to users
    */
    public function mainSendNotification($fcmToken, $title, $body, $type)
    {
        try {
            Log::info('FCM main notification sent', [
                       'token' => $fcmToken,
                       'title' => $title,
                       'body' => $body,
                       'type' => $type
                   ]);
            $data = [
                'type' => $type,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ];

            return $this->sendToDevice($fcmToken, $title, $body, $data);
        } catch (\Exception $e) {
            Log::error('FCM main notification error', [
                'token' => $fcmToken,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ];
        }
    }
    /**
     * Send notification for new task
     */
    public function sendNewTaskNotification($driver, $task)
    {
        if (!$driver->fcm_token) {
            return ['success' => false, 'message' => 'Driver has no FCM token'];
        }

        $title = 'مهمة جديدة متاحة';
        $body = "مهمة جديدة من {$task->pickup->address} إلى {$task->delivery->address}";
        $data = [
            'type' => 'new_task',
            'task_id' => (string) $task->id,
            'pickup_address' => $task->pickup->address ?? '',
            'delivery_address' => $task->delivery->address ?? '',
            'price' => (string) $task->price,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];

        return $this->sendToDevice($driver->fcm_token, $title, $body, $data);
    }

    /**
     * Send notification for task update
     */
    public function sendTaskUpdateNotification($driver, $task, $status)
    {
        if (!$driver->fcm_token) {
            return ['success' => false, 'message' => 'Driver has no FCM token'];
        }

        $statusMessages = [
            'accepted' => 'تم قبول المهمة',
            'in_progress' => 'المهمة قيد التنفيذ',
            'completed' => 'تم إكمال المهمة',
            'cancelled' => 'تم إلغاء المهمة'
        ];

        $title = 'تحديث المهمة';
        $body = $statusMessages[$status] ?? 'تم تحديث حالة المهمة';
        $data = [
            'type' => 'task_update',
            'task_id' => (string) $task->id,
            'status' => $status,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];

        return $this->sendToDevice($driver->fcm_token, $title, $body, $data);
    }

    /**
     * Send payment notification
     */
    public function sendPaymentNotification($driver, $amount, $type = 'payment_received')
    {
        if (!$driver->fcm_token) {
            return ['success' => false, 'message' => 'Driver has no FCM token'];
        }

        $title = 'إشعار مالي';
        $body = "تم إضافة {$amount} ريال إلى محفظتك";
        $data = [
            'type' => $type,
            'amount' => (string) $amount,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];

        return $this->sendToDevice($driver->fcm_token, $title, $body, $data);
    }
}
