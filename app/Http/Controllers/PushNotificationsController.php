<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\GeneralPushNotification;
use Illuminate\Http\Request;

class PushNotificationsController extends Controller
{
    public function index()
    {
        $user = User::find(1); // أو Customer أو Driver حسب نوع المستخدم
        $user->notify(new GeneralPushNotification([
            'title' => 'تنبيه!',
            'body' => 'لديك رسالة جديدة من الإدارة',
            'icon' => '/images/admin-icon.png',
            'image' => '/images/banner.png',
            'url' => '/notifications/1',
            'type' => 'admin_message',
        ]));

    }
}
