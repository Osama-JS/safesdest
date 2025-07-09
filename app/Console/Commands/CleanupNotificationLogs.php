<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailNotificationLog;
use Carbon\Carbon;

class CleanupNotificationLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup 
                            {--success-days=7 : Days to keep successful notifications}
                            {--failed-days=30 : Days to keep failed notifications}
                            {--batch-size=1000 : Batch size for deletion}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old email notification logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $successDays = $this->option('success-days');
        $failedDays = $this->option('failed-days');
        $batchSize = $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        $this->info('Starting notification logs cleanup...');

        // Clean up successful notifications
        $successCutoff = Carbon::now()->subDays($successDays);
        $successQuery = EmailNotificationLog::where('status', 'sent')
            ->where('created_at', '<', $successCutoff);

        $successCount = $successQuery->count();

        if ($successCount > 0) {
            $this->info("Found {$successCount} successful notifications older than {$successDays} days");
            
            if (!$dryRun) {
                $deleted = 0;
                $successQuery->chunk($batchSize, function ($notifications) use (&$deleted) {
                    foreach ($notifications as $notification) {
                        $notification->delete();
                        $deleted++;
                    }
                });
                $this->info("Deleted {$deleted} successful notifications");
            } else {
                $this->warn("DRY RUN: Would delete {$successCount} successful notifications");
            }
        } else {
            $this->info("No successful notifications to clean up");
        }

        // Clean up failed notifications
        $failedCutoff = Carbon::now()->subDays($failedDays);
        $failedQuery = EmailNotificationLog::where('status', 'failed')
            ->where('created_at', '<', $failedCutoff);

        $failedCount = $failedQuery->count();

        if ($failedCount > 0) {
            $this->info("Found {$failedCount} failed notifications older than {$failedDays} days");
            
            if (!$dryRun) {
                $deleted = 0;
                $failedQuery->chunk($batchSize, function ($notifications) use (&$deleted) {
                    foreach ($notifications as $notification) {
                        $notification->delete();
                        $deleted++;
                    }
                });
                $this->info("Deleted {$deleted} failed notifications");
            } else {
                $this->warn("DRY RUN: Would delete {$failedCount} failed notifications");
            }
        } else {
            $this->info("No failed notifications to clean up");
        }

        // Clean up very old pending notifications (likely stuck)
        $pendingCutoff = Carbon::now()->subDays(7);
        $pendingQuery = EmailNotificationLog::where('status', 'pending')
            ->where('created_at', '<', $pendingCutoff);

        $pendingCount = $pendingQuery->count();

        if ($pendingCount > 0) {
            $this->warn("Found {$pendingCount} stuck pending notifications older than 7 days");
            
            if (!$dryRun) {
                $pendingQuery->update(['status' => 'failed', 'error_message' => 'Marked as failed due to being stuck']);
                $this->info("Marked {$pendingCount} stuck notifications as failed");
            } else {
                $this->warn("DRY RUN: Would mark {$pendingCount} stuck notifications as failed");
            }
        }

        $this->info('Cleanup completed successfully!');

        // Show current statistics
        $this->showStatistics();
    }

    /**
     * Show current notification statistics
     */
    private function showStatistics()
    {
        $this->info("\n--- Current Statistics ---");
        
        $total = EmailNotificationLog::count();
        $sent = EmailNotificationLog::where('status', 'sent')->count();
        $failed = EmailNotificationLog::where('status', 'failed')->count();
        $pending = EmailNotificationLog::where('status', 'pending')->count();

        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Sent', $sent, $total > 0 ? round(($sent / $total) * 100, 2) . '%' : '0%'],
                ['Failed', $failed, $total > 0 ? round(($failed / $total) * 100, 2) . '%' : '0%'],
                ['Pending', $pending, $total > 0 ? round(($pending / $total) * 100, 2) . '%' : '0%'],
                ['Total', $total, '100%']
            ]
        );

        // Show recent activity
        $today = EmailNotificationLog::whereDate('created_at', today())->count();
        $thisWeek = EmailNotificationLog::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        $this->info("Today: {$today} notifications");
        $this->info("This week: {$thisWeek} notifications");
    }
}
