<?php

namespace App\Services;

use App\Models\User;
use App\Models\Driver;
use App\Models\Customer;
use App\Notifications\GeneralPushNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // public function send($type, array $ids, $title, $body, $icon = null, $image = null, $url = null, $notif_type = 'general')
    // {
    //     $modelMap = [
    //         'user' => User::class,
    //         'driver' => Driver::class,
    //         'customer' => Customer::class,
    //     ];

    //     if (!isset($modelMap[$type])) {
    //         throw new \Exception("نوع المستلم غير صحيح");
    //     }

    //     $recipients = $modelMap[$type]::whereIn('id', $ids)->get();

    //     foreach ($recipients as $recipient) {
    //         $recipient->notify(new GeneralPushNotification([
    //             'title' => $title,
    //             'body' => $body,
    //             'icon' => $icon ?? '/images/admin-icon.png',
    //             'image' => $image ?? '/images/banner.png',
    //             'url' => $url ?? '/',
    //             'type' => $notif_type,
    //         ]));
    //     }

    //     return true;
    // }

    public function send($type, array $ids, $title, $body, $icon = null, $image = null, $url = null, $notif_type = 'general')
    {
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
            // التأكد أن الموديل يدعم notify ومشترك في الإشعارات
            if (method_exists($recipient, 'notify')) {
                $recipient->notify(new GeneralPushNotification([
                    'title' => $title,
                    'body' => $body,
                    'icon' => $icon ?? '/images/admin-icon.png',
                    'image' => $image ?? '/images/banner.png',
                    'url' => $url ?? '/',
                    'type' => $notif_type,
                ]));
            }
            Log::alert("message: ".$recipient->name);

        }

        return true;
    }

}
