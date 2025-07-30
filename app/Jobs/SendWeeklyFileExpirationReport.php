<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\FileExpirationNotification;
use App\Services\EmailNotificationService;

class SendWeeklyFileExpirationReport implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;

  public function handle(): void
  {
    try {
      Log::info('📊 Generating weekly file expiration report');

      $startDate = now()->subWeek()->startOfWeek();
      $endDate = now()->subWeek()->endOfWeek();

      $weeklyStats = [
        'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
        'total_notifications' => FileExpirationNotification::whereBetween('created_at', [$startDate, $endDate])->count(),
        'accounts_suspended' => FileExpirationNotification::whereBetween('updated_at', [$startDate, $endDate])
          ->where('status', 'account_suspended')->count(),
        'by_user_type' => FileExpirationNotification::whereBetween('created_at', [$startDate, $endDate])
          ->selectRaw('user_type, COUNT(*) as count')
          ->groupBy('user_type')
          ->pluck('count', 'user_type')
          ->toArray(),
        'most_expired_files' => FileExpirationNotification::whereBetween('created_at', [$startDate, $endDate])
          ->selectRaw('field_label, COUNT(*) as count')
          ->groupBy('field_label')
          ->orderByDesc('count')
          ->limit(5)
          ->pluck('count', 'field_label')
          ->toArray()
      ];

      app(EmailNotificationService::class)->sendWithTemplate(
        'weekly-file-expiration-report',
        config('app.admin_email', 'admin@safedests.com'),
        'تقرير أسبوعي - نظام فحص انتهاء صلاحية الملفات',
        $weeklyStats
      );

      Log::info('✅ Weekly report sent successfully', [
        'recipient' => config('app.admin_email', 'admin@safedests.com'),
        'stats' => $weeklyStats
      ]);
    } catch (\Exception $e) {
      Log::error('❌ Weekly report failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
    }
  }
}
