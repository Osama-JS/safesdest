<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\FileExpirationNotification;
use App\Services\EmailNotificationService;

class CleanupFileExpirationNotifications implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;

  public function handle(): void
  {
    try {
      Log::info('🧹 Starting cleanup of old file expiration notifications');

      $successfulDeleted = FileExpirationNotification::where('status', 'sent')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();

      $suspendedDeleted = FileExpirationNotification::where('status', 'account_suspended')
        ->where('created_at', '<', now()->subDays(90))
        ->delete();

      $totalDeleted = $successfulDeleted + $suspendedDeleted;

      Log::info('✅ Cleanup completed', [
        'successful_deleted' => $successfulDeleted,
        'suspended_deleted' => $suspendedDeleted,
        'total_deleted' => $totalDeleted
      ]);

      if ($totalDeleted > 1000) {
        app(EmailNotificationService::class)->sendWithTemplate(
          'cleanup-notification',
          config('app.admin_email', 'admin@safedests.com'),
          'تقرير تنظيف سجلات انتهاء صلاحية الملفات',
          [
            'total_deleted' => $totalDeleted,
            'successful_deleted' => $successfulDeleted,
            'suspended_deleted' => $suspendedDeleted,
            'cleanup_date' => now()->format('Y-m-d H:i:s')
          ]
        );
      }
    } catch (\Exception $e) {
      Log::error('❌ Cleanup failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
    }
  }
}
