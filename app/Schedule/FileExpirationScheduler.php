<?php

namespace App\Schedule;

use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\CleanupFileExpirationNotifications;
use App\Jobs\SendWeeklyFileExpirationReport;

class FileExpirationScheduler
{
    public function __invoke(Schedule $schedule): void
    {
        // فحص انتهاء صلاحية الملفات يومياً الساعة 10:00 صباحاً
        $schedule->command('files:check-expirations')
            ->dailyAt('10:00')
            ->timezone('Asia/Riyadh')
            ->withoutOverlapping(10)
            ->emailOutputOnFailure(config('app.admin_email', 'admin@safedests.com'));
        // ✅ لم نستخدم onSuccess أو onFailure هنا لتجنب مشكلة scheduled closures

        // تنظيف التنبيهات القديمة أسبوعياً يوم الأحد الساعة 02:00
        $schedule->job(new CleanupFileExpirationNotifications())
            ->weeklyOn(0, '02:00')
            ->timezone('Asia/Riyadh')
            ->name('cleanup-file-expiration-notifications')
            ->withoutOverlapping(); // ✅ هذا آمن

        // إرسال تقرير أسبوعي يوم الأحد الساعة 09:00
        $schedule->job(new SendWeeklyFileExpirationReport())
            ->weeklyOn(0, '09:00')
            ->timezone('Asia/Riyadh')
            ->name('weekly-file-expiration-report')
            ->withoutOverlapping(); // ✅ هذا آمن أيضًا
    }
}
