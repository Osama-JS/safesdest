<?php

namespace App\Notifications;

use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GeneralPushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->queue = 'noti';
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage())
            ->title($this->data['title'] ?? 'إشعار جديد')
            ->body($this->data['body'] ?? 'لديك رسالة جديدة')
            ->icon($this->data['icon'] ?? '/icon.png')
            ->image($this->data['image'] ?? null)
            ->action('عرض', 'open_url')
            ->data([
                'url' => $this->data['url'] ?? '/',
                'type' => $this->data['type'] ?? 'general'
            ])
            ->vibrate([200, 100, 200])
            ->requireInteraction();
    }
}
