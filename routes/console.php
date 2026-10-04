<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 1. فحص استحقاق وتأخر فواتير العملاء وإرسال الإشعارات (يومياً 08:00 صباحاً)
Schedule::command('invoices:check-due-dates')->dailyAt('08:00');

// 2. إعادة معالجة الإشعارات والبريد الإلكتروني العالق أو المتعثر (كل 15 دقيقة)
Schedule::command('notifications:process-pending')->everyFifteenMinutes();

// 3. تنظيف وأرشفة سجلات الإشعارات القديمة لتخفيف قاعدة البيانات (أسبوعياً)
Schedule::command('notifications:cleanup')->weekly();

