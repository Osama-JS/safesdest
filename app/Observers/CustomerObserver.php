<?php

namespace App\Observers;

use App\Models\Customer;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;

class CustomerObserver
{
    protected $adminEmail = 'info@safedest.com';

    public function created(Customer $customer)
    {
        Log::info("CustomerObserver: New customer registered #{$customer->id}");

        $content = "تم تسجيل عميل جديد في المنصة:\n" .
                   "- الاسم: {$customer->name}\n" .
                   "- الجوال: {$customer->phone}\n" .
                   "- الشركة: " . ($customer->company_name ?? 'N/A');

        $this->notifyManager("تسجيل عميل جديد: {$customer->name}", $content, $customer->id);
    }

    protected function notifyManager($subject, $content, $customerId)
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
                    'customer_id' => $customerId,
                    'action_url' => url("/admin/customers")
                ]
            ];

            dispatch(new SendEmailNotificationJob($emailData));
        } catch (\Exception $e) {
            Log::error("CustomerObserver Error: " . $e->getMessage());
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
            Log::error("CustomerObserver: Failed to save notification to DB: " . $e->getMessage());
        }
    }
}
