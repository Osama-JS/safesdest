<?php

namespace App\Schedule;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use App\Jobs\CleanupFileExpirationNotifications;
use App\Jobs\SendWeeklyFileExpirationReport;

class FileExpirationScheduler
{
  public function __invoke(Schedule $schedule): void
  {
    // فحص انتهاء صلاحية الملفات يومياً الساعة 10:00 صباحاً
    // $schedule->command('files:check-expirations')
    //   ->dailyAt('10:00')
    //   ->timezone('Asia/Riyadh')
    //   ->withoutOverlapping(10)
    //   ->runInBackground()
    //   ->emailOutputOnFailure(config('app.admin_email', 'admin@safedests.com'))
    //   ->onSuccess(function () {
    //     Log::info('✅ File expiration check completed successfully', [
    //       'scheduled_at' => now()->format('Y-m-d H:i:s'),
    //       'timezone' => 'Asia/Riyadh'
    //     ]);
    //   })
    //   ->onFailure(function () {
    //     Log::error('❌ File expiration check failed', [
    //       'scheduled_at' => now()->format('Y-m-d H:i:s'),
    //       'timezone' => 'Asia/Riyadh'
    //     ]);
    //   });

    // // تنظيف التنبيهات القديمة
    // $schedule->job(new CleanupFileExpirationNotifications)
    //   ->weeklyOn(0, '02:00')
    //   ->timezone('Asia/Riyadh')
    //   ->name('cleanup-file-expiration-notifications')
    //   ->withoutOverlapping()
    //   ->runInBackground();

    // // إرسال تقرير أسبوعي
    // $schedule->job(new SendWeeklyFileExpirationReport)
    //   ->weeklyOn(0, '09:00')
    //   ->timezone('Asia/Riyadh')
    //   ->name('weekly-file-expiration-report')
    //   ->withoutOverlapping()
    //   ->runInBackground();
  }
}
