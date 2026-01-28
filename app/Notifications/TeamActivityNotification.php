<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TeamActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;

        // Ensure this notification is processed on the 'noti' queue
        $this->queue = 'noti';
    }

    public function via($notifiable)
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->data['title'] ?? 'تنبيه من الفريق',
            'body' => $this->data['body'] ?? '',
            'url' => $this->data['url'] ?? '/',
            'type' => $this->data['type'] ?? 'team_activity',
            'task_id' => $this->data['task_id'] ?? null,
        ];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage())
            ->title($this->data['title'] ?? 'تنبيه من الفريق')
            ->body($this->data['body'] ?? '')
            ->icon($this->data['icon'] ?? '/images/admin-icon.png')
            ->action('عرض التفاصيل', 'open_url')
            ->data([
                'url' => $this->data['url'] ?? '/',
                'type' => $this->data['type'] ?? 'team_activity'
            ])
            ->vibrate([200, 100, 200])
            ->requireInteraction();
    }
}
