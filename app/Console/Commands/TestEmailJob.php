<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestEmailJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-job {email=osamomy@gmail.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email job functionality by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("🧪 Starting Email Job Test...");
        $this->info("📧 Target Email: {$email}");
        
        // فحص عدد Jobs قبل الإرسال
        $jobsCountBefore = DB::table('jobs')->count();
        $this->info("📊 Jobs in queue before: {$jobsCountBefore}");
        
        // إعداد بيانات الإيميل للاختبار
        $emailData = [
            'to' => $email,
            'subject' => 'Test Email from SafeDests System - ' . now()->format('Y-m-d H:i:s'),
            'template' => 'emails.notification',
            'type' => 'test_email',
            'priority' => 'high',
            'user_name' => 'Test User',
            'content' => 'This is a test email to verify that the email notification system is working correctly. If you receive this email, the system is functioning properly.',
            'action_url' => 'https://example.com/test',
            'action_text' => 'Test Action',
            'additional_data' => [
                'Test Time' => now()->format('Y-m-d H:i:s'),
                'Test ID' => uniqid('test_'),
                'System Status' => 'Testing',
                'Queue Connection' => config('queue.default'),
                'Environment' => app()->environment()
            ]
        ];
        
        $this->info("📝 Email data prepared:");
        $this->table(
            ['Field', 'Value'],
            [
                ['To', $emailData['to']],
                ['Subject', $emailData['subject']],
                ['Template', $emailData['template']],
                ['Type', $emailData['type']],
                ['Priority', $emailData['priority']]
            ]
        );
        
        try {
            // إرسال Job
            $this->info("🚀 Dispatching email job...");
            dispatch(new SendEmailNotificationJob($emailData));
            
            // انتظار قصير للسماح للنظام بمعالجة Job
            sleep(2);
            
            // فحص عدد Jobs بعد الإرسال
            $jobsCountAfter = DB::table('jobs')->count();
            $this->info("📊 Jobs in queue after: {$jobsCountAfter}");
            
            if ($jobsCountAfter > $jobsCountBefore) {
                $this->info("✅ Job successfully added to queue!");
                
                // عرض تفاصيل Job الجديد
                $latestJob = DB::table('jobs')->orderBy('id', 'desc')->first();
                if ($latestJob) {
                    $this->info("📋 Latest job details:");
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Job ID', $latestJob->id],
                            ['Queue', $latestJob->queue],
                            ['Attempts', $latestJob->attempts],
                            ['Available At', date('Y-m-d H:i:s', $latestJob->available_at)],
                            ['Created At', date('Y-m-d H:i:s', $latestJob->created_at)],
                            ['Current Time', date('Y-m-d H:i:s')],
                            ['Ready to Process', $latestJob->available_at <= time() ? 'YES' : 'NO']
                        ]
                    );
                    
                    // فحص محتوى Job
                    $payload = json_decode($latestJob->payload, true);
                    if (isset($payload['data']['command'])) {
                        $this->info("📦 Job payload contains valid command data");
                    }
                }
                
                $this->newLine();
                $this->info("🔄 Now testing queue worker...");
                
                // محاولة تشغيل Job واحد
                $this->info("⚡ Running: php artisan queue:work --once --verbose");
                $exitCode = $this->call('queue:work', [
                    '--once' => true,
                    '--verbose' => true,
                    '--timeout' => 60
                ]);
                
                if ($exitCode === 0) {
                    $this->info("✅ Queue worker executed successfully!");
                } else {
                    $this->error("❌ Queue worker failed with exit code: {$exitCode}");
                }
                
                // فحص عدد Jobs بعد المعالجة
                $jobsCountFinal = DB::table('jobs')->count();
                $this->info("📊 Jobs in queue after processing: {$jobsCountFinal}");
                
                if ($jobsCountFinal < $jobsCountAfter) {
                    $this->info("✅ Job was processed successfully!");
                } else {
                    $this->warn("⚠️ Job is still in queue - may need manual processing");
                }
                
            } else {
                $this->error("❌ Job was not added to queue!");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error occurred: " . $e->getMessage());
            Log::error('Test email job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        $this->newLine();
        $this->info("🔍 Additional diagnostics:");
        
        // فحص إعدادات Queue
        $this->table(
            ['Setting', 'Value'],
            [
                ['Queue Connection', config('queue.default')],
                ['Queue Driver', config('queue.connections.' . config('queue.default') . '.driver')],
                ['Queue Table', config('queue.connections.' . config('queue.default') . '.table')],
                ['Mail Driver', config('mail.default')],
                ['Mail Host', config('mail.mailers.smtp.host')],
                ['App Environment', app()->environment()]
            ]
        );
        
        // فحص Failed Jobs
        $failedJobs = DB::table('failed_jobs')->count();
        $this->info("💥 Failed jobs count: {$failedJobs}");
        
        if ($failedJobs > 0) {
            $latestFailed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->first();
            $this->warn("⚠️ Latest failed job: " . $latestFailed->exception);
        }
        
        $this->newLine();
        $this->info("📋 Test Summary:");
        $this->info("- Email target: {$email}");
        $this->info("- Jobs before: {$jobsCountBefore}");
        $this->info("- Jobs after dispatch: {$jobsCountAfter}");
        $this->info("- Jobs after processing: " . DB::table('jobs')->count());
        $this->info("- Failed jobs: {$failedJobs}");
        
        $this->newLine();
        $this->info("🎯 Next steps:");
        $this->info("1. Check your email inbox: {$email}");
        $this->info("2. Check spam folder if not received");
        $this->info("3. Monitor logs: tail -f storage/logs/laravel.log");
        $this->info("4. Run queue worker: php artisan queue:work --verbose");
        
        return 0;
    }
}
