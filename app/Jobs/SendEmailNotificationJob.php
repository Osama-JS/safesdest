<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\NotificationMail;
use App\Models\EmailNotificationLog;
use Exception;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [30, 60, 120];

    protected $emailData;
    protected $attachments;

    /**
     * Create a new job instance.
     *
     * @param array $emailData
     * @param array $attachments
     */
    public function __construct(array $emailData, array $attachments = [])
    {
        $this->emailData = $emailData;
        $this->attachments = $attachments;

        // Set queue name based on priority
        $this->onQueue($emailData['priority'] ?? 'default');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Create log entry
        $logEntry = EmailNotificationLog::create([
            'to_email' => $this->emailData['to'],
            'from_email' => $this->emailData['from_email'] ?? config('mail.from.address'),
            'subject' => $this->emailData['subject'],
            'content' => $this->emailData['content'] ?? '',
            'template' => $this->emailData['template'] ?? 'emails.notification',
            'type' => $this->emailData['type'] ?? 'general',
            'priority' => $this->emailData['priority'] ?? 'normal',
            'status' => 'pending',
            'additional_data' => $this->emailData['additional_data'] ?? null,
            'has_attachments' => !empty($this->attachments),
            'attempts' => $this->attempts()
        ]);

        try {
            Log::info('Starting email notification job', [
                'log_id' => $logEntry->id,
                'to' => $this->emailData['to'],
                'subject' => $this->emailData['subject'],
                'template' => $this->emailData['template'] ?? 'default'
            ]);

            // Create and send the email
            $mailable = new NotificationMail($this->emailData, $this->attachments);

            Mail::to($this->emailData['to'])->send($mailable);

            // Mark as sent
            $logEntry->markAsSent();

            Log::info('Email notification sent successfully', [
                'log_id' => $logEntry->id,
                'to' => $this->emailData['to'],
                'subject' => $this->emailData['subject']
            ]);
        } catch (Exception $e) {
            // Mark as failed
            $logEntry->markAsFailed($e->getMessage());

            Log::error('Failed to send email notification', [
                'log_id' => $logEntry->id,
                'to' => $this->emailData['to'],
                'subject' => $this->emailData['subject'],
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        Log::error('Email notification job failed permanently', [
            'to' => $this->emailData['to'],
            'subject' => $this->emailData['subject'],
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // You can add additional failure handling here
        // For example, save to failed_notifications table
        // or send an alert to administrators
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return [
            'email',
            'notification',
            $this->emailData['type'] ?? 'general',
            'to:' . $this->emailData['to']
        ];
    }
}
