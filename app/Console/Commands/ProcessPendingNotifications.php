<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailNotificationLog;
use App\Jobs\SendEmailNotificationJob;
use Carbon\Carbon;

class ProcessPendingNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process-pending 
                            {--limit=50 : Maximum number of notifications to process}
                            {--max-attempts=3 : Maximum retry attempts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending email notifications that failed or got stuck';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $maxAttempts = $this->option('max-attempts');

        $this->info('Processing pending email notifications...');

        // Get pending notifications that are older than 5 minutes
        $pendingNotifications = EmailNotificationLog::where('status', 'pending')
            ->where('created_at', '<', Carbon::now()->subMinutes(5))
            ->where('attempts', '<', $maxAttempts)
            ->limit($limit)
            ->get();

        if ($pendingNotifications->isEmpty()) {
            $this->info('No pending notifications found.');
            return;
        }

        $this->info("Found {$pendingNotifications->count()} pending notifications.");

        $processed = 0;
        $failed = 0;

        foreach ($pendingNotifications as $notification) {
            try {
                // Prepare email data
                $emailData = [
                    'to' => $notification->to_email,
                    'from_email' => $notification->from_email,
                    'subject' => $notification->subject,
                    'content' => $notification->content,
                    'template' => $notification->template,
                    'type' => $notification->type,
                    'priority' => $notification->priority,
                    'additional_data' => $notification->additional_data
                ];

                // Dispatch the job again
                dispatch(new SendEmailNotificationJob($emailData, []));

                $notification->incrementAttempts();
                $processed++;

                $this->line("Reprocessed notification ID: {$notification->id}");

            } catch (\Exception $e) {
                $notification->markAsFailed($e->getMessage());
                $failed++;

                $this->error("Failed to reprocess notification ID: {$notification->id} - {$e->getMessage()}");
            }
        }

        $this->info("Processing completed. Processed: {$processed}, Failed: {$failed}");

        // Mark old failed notifications as permanently failed
        $oldFailed = EmailNotificationLog::where('status', 'pending')
            ->where('attempts', '>=', $maxAttempts)
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->update(['status' => 'failed']);

        if ($oldFailed > 0) {
            $this->warn("Marked {$oldFailed} old notifications as permanently failed.");
        }
    }
}
