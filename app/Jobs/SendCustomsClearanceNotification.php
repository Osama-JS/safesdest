<?php

namespace App\Jobs;

use App\Mail\CustomsClearanceNotification;
use App\Models\CustomsClearance;
use App\Models\CustomsClearanceOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCustomsClearanceNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $clearance;
    public $notificationType;
    public $recipients;
    public $additionalData;
    public $offer;

    /**
     * Create a new job instance.
     */
    public function __construct(
        CustomsClearance $clearance,
        string $notificationType,
        array $recipients,
        array $additionalData = [],
        CustomsClearanceOffer $offer = null
    ) {
        $this->clearance = $clearance;
        $this->notificationType = $notificationType;
        $this->recipients = $recipients;
        $this->additionalData = $additionalData;
        $this->offer = $offer;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            foreach ($this->recipients as $recipient) {
                if (!empty($recipient['email'])) {
                    Mail::to($recipient['email'], $recipient['name'] ?? '')
                        ->send(new CustomsClearanceNotification(
                            $this->clearance,
                            $this->notificationType,
                            array_merge($this->additionalData, ['recipient' => $recipient]),
                            $this->offer
                        ));

                    Log::info('Customs clearance notification sent', [
                        'clearance_id' => $this->clearance->id,
                        'notification_type' => $this->notificationType,
                        'recipient_email' => $recipient['email'],
                        'recipient_name' => $recipient['name'] ?? 'Unknown'
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send customs clearance notification', [
                'clearance_id' => $this->clearance->id,
                'notification_type' => $this->notificationType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Customs clearance notification job failed', [
            'clearance_id' => $this->clearance->id,
            'notification_type' => $this->notificationType,
            'recipients_count' => count($this->recipients),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
