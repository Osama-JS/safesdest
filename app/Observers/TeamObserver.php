<?php

namespace App\Observers;

use App\Models\Teams;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;

class TeamObserver
{
    protected $adminEmail = 'info@safedest.com';

    public function created(Teams $team)
    {
        Log::info("TeamObserver: New team created #{$team->id}");

        $content = "تم إنشاء فريق جديد في المنصة:\n" .
                   "- اسم الفريق: {$team->name}\n" .
                   "- العنوان: " . ($team->address ?? 'N/A');

        $this->notifyManager("إنشاء فريق جديد: {$team->name}", $content, $team->id);
    }

    protected function notifyManager($subject, $content, $teamId)
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
                    'team_id' => $teamId,
                    'action_url' => url("/admin/teams")
                ]
            ];

            dispatch(new SendEmailNotificationJob($emailData));
        } catch (\Exception $e) {
            Log::error("TeamObserver Error: " . $e->getMessage());
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
            Log::error("TeamObserver: Failed to save notification to DB: " . $e->getMessage());
        }
    }
}
