<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageId;
    protected $payload;

    /**
     * Create a new job instance.
     *
     * @param int $messageId The ID of the WhatsappMessage in the database
     * @param array $payload The payload to send to Meta API
     */
    public function __construct($messageId, $payload)
    {
        $this->messageId = $messageId;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Dynamic Sleep based on queue size
        $queueSize = Queue::size('whatsapp');
        if ($queueSize > 50) { // Consider crowded if > 50
            sleep(3);
        } else {
            sleep(5);
        }

        // 2. Fetch the message record
        $message = WhatsappMessage::find($this->messageId);
        if (!$message) {
            return; // Message deleted or not found
        }

        // 3. Send the request
        $url = env('WHATSAPP_CLOUD_URL');
        $phoneId = env('WHATSAPP_CLOUD_PHONE_ID');
        $token = env('WHATSAPP_CLOUD_TOKEN');
        $endpoint = "{$url}{$phoneId}/messages";

        try {
            $response = Http::withToken($token)->post($endpoint, $this->payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Get the Meta Message ID from the response (usually in messages[0].id)
                $metaId = $responseData['messages'][0]['id'] ?? null;
                
                $message->update([
                    'status' => 'sent',
                    'meta_message_id' => $metaId,
                    'sent_at' => now(),
                    'error_log' => null
                ]);

                Log::info("WhatsApp Cloud message sent successfully to {$this->payload['to']}");
            } else {
                $message->update([
                    'status' => 'failed',
                    'error_log' => $response->body()
                ]);
                Log::error("WhatsApp Cloud Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $message->update([
                'status' => 'failed',
                'error_log' => $e->getMessage()
            ]);
            Log::error('Error sending WhatsApp Cloud message: ' . $e->getMessage());
        }
    }
}
