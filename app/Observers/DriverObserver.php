<?php

namespace App\Observers;

use App\Models\Driver;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class DriverObserver implements ShouldHandleEventsAfterCommit
{
    protected $adminEmail = 'info@safedest.com';

    public function created(Driver $driver)
    {
        // Generate Driver Code (S00001)
        $driver->update([
            'driver_code' => 'S' . str_pad($driver->id, 5, '0', STR_PAD_LEFT)
        ]);

        Log::info("DriverObserver: New driver registered #{$driver->id} with code {$driver->driver_code}");

        $content = "تم تسجيل سائق جديد في المنصة:\n" .
                   "- الاسم: {$driver->name}\n" .
                   "- الكود: {$driver->driver_code}\n" .
                   "- الجوال: {$driver->phone}\n" .
                   "- البريد الإلكتروني: " . ($driver->email ?? 'N/A');

        $this->notifyManager("تسجيل سائق جديد: {$driver->name} ({$driver->driver_code})", $content, $driver->id);
    }

    protected function notifyManager($subject, $content, $driverId)
    {
        try {
            // 1. Create notification in database (in separate transaction)
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

            dispatch(new SendEmailNotificationJob($emailData))->afterCommit();
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
            // Use a separate database connection/transaction
            DB::transaction(function () use ($title, $message) {
                // 1. Create the notification
                $notification = \App\Models\Notification::create([
                    'title' => $title,
                    'message' => $message,
                    'group' => 'users',
                    'type' => 'bay person'
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
            });
        } catch (\Exception $e) {
            // Log error but don't throw - this should not affect driver creation
            Log::error("DriverObserver: Failed to save notification to DB: " . $e->getMessage());
        }
    }
}
