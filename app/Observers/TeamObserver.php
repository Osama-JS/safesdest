<?php

namespace App\Observers;

use App\Models\Teams;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;

class TeamObserver
{
    protected $adminEmail = 'nawafr81aug@gmail.com';

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
}
