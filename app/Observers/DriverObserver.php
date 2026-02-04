<?php

namespace App\Observers;

use App\Models\Driver;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;

class DriverObserver
{
    protected $adminEmail = 'info@safedest.com';

    public function created(Driver $driver)
    {
        Log::info("DriverObserver: New driver registered #{$driver->id}");

        $content = "تم تسجيل سائق جديد في المنصة:\n" .
                   "- الاسم: {$driver->name}\n" .
                   "- الجوال: {$driver->phone}\n" .
                   "- البريد الإلكتروني: " . ($driver->email ?? 'N/A');

        $this->notifyManager("تسجيل سائق جديد: {$driver->name}", $content, $driver->id);
    }

    protected function notifyManager($subject, $content, $driverId)
    {
        try {
            // 1. Create notification in database
            $this->createNotificationRecord($subject, $content);

            $emailData = [
                'to' => $this->adminEmail,
                'subject' => "[Safedest Admin] " . $subject,
                'content' => $content,
                'user_name' => 'مدير المنصة',
                'template' => 'emails.notification',
                'type' => 'admin_alert',
                'priority' => 'high',
                'additional_data' => [
                    'driver_id' => $driverId,
                    'action_url' => url("/admin/drivers")
                ]
            ];

            dispatch(new SendEmailNotificationJob($emailData));
        } catch (\Exception $e) {
            Log::error("DriverObserver Error: " . $e->getMessage());
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
            Log::error("DriverObserver: Failed to save notification to DB: " . $e->getMessage());
        }
    }
}
