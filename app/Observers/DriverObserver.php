<?php

namespace App\Observers;

use App\Models\Driver;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;

class DriverObserver
{
    protected $adminEmail = 'nawafr81aug@gmail.com';

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
}
