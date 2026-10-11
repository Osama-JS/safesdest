<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateBackupJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create-job {jobId : The unique identifier for the backup job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a platform backup job asynchronously in the background with real-time progress tracking';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $jobId = $this->argument('jobId');
        $this->info("Starting background backup job: {$jobId}");

        $jobConfig = $backupService->getJobConfig($jobId);
        if (!$jobConfig) {
            $this->error("Job configuration not found for: {$jobId}");
            return 1;
        }

        try {
            // Remove execution and memory limits for this background task
            set_time_limit(0);
            ignore_user_abort(true);
            ini_set('memory_limit', '1024M');

            $options = $jobConfig['options'] ?? [];
            $options['job_id'] = $jobId;

            // Execute the backup with live progress callbacks
            $result = $backupService->createBackup($options, function (int $percent, string $title, ?string $details = null) use ($backupService, $jobId) {
                $backupService->updateJobProgress($jobId, [
                    'status' => 'running',
                    'percent' => $percent,
                    'step_title' => $title,
                    'details' => $details,
                ]);
            });

            // Mark job as completed
            $backupService->updateJobProgress($jobId, [
                'status' => 'completed',
                'percent' => 100,
                'step_title' => 'تم اكتمال النسخة الاحتياطية بنجاح!',
                'details' => 'الملف: ' . ($result['filename'] ?? '') . ' (' . ($result['size_human'] ?? '') . ')',
                'result' => $result,
            ]);

            $this->info("Backup job {$jobId} completed successfully.");
            return 0;

        } catch (\Throwable $e) {
            Log::error("Background backup job [{$jobId}] failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $backupService->updateJobProgress($jobId, [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'step_title' => 'فشل إنشاء النسخة الاحتياطية',
                'details' => $e->getMessage(),
            ]);

            $this->error("Backup job {$jobId} failed: " . $e->getMessage());
            return 1;
        }
    }
}
