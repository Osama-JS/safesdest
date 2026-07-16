<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify the webhook via GET request (used by Meta to verify webhook url)
     */
    public function verify(Request $request)
    {
        $verifyToken = env('WHATSAPP_VERIFY_TOKEN');

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhook events via POST request
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        
        Log::info('WhatsApp Webhook Received:', $data);

        try {
            if (isset($data['entry'][0]['changes'][0]['value'])) {
                $value = $data['entry'][0]['changes'][0]['value'];

                // 1. Process Status Updates (Delivery Receipts)
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $statusUpdate) {
                        $metaId = $statusUpdate['id'];
                        $status = $statusUpdate['status']; // sent, delivered, read, failed

                        $message = \App\Models\WhatsappMessage::where('meta_message_id', $metaId)->first();
                        
                        if ($message) {
                            $updateData = ['status' => $status];
                            if ($status === 'sent') $updateData['sent_at'] = now();
                            if ($status === 'delivered') $updateData['delivered_at'] = now();
                            if ($status === 'read') $updateData['read_at'] = now();
                            
                            if (isset($statusUpdate['errors'])) {
                                $updateData['error_log'] = json_encode($statusUpdate['errors']);
                                $updateData['status'] = 'failed';
                            }
                            
                            $message->update($updateData);
                        }
                    }
                }

                // 2. Process Incoming Messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $msg) {
                        $phone = $msg['from'];
                        $metaId = $msg['id'];
                        $type = $msg['type'];
                        
                        // Extract content based on type
                        $content = '';
                        if ($type === 'text') {
                            $content = $msg['text']['body'] ?? '';
                        } elseif ($type === 'image') {
                            $content = 'Image: ' . ($msg['image']['id'] ?? '');
                        } elseif ($type === 'document') {
                            $content = 'Document: ' . ($msg['document']['id'] ?? '');
                        } else {
                            $content = "Unsupported type: {$type}";
                        }

                        // Prevent duplicate processing
                        $exists = \App\Models\WhatsappMessage::where('meta_message_id', $metaId)->exists();
                        if (!$exists) {
                            $conversation = \App\Models\WhatsappConversation::firstOrCreate(
                                ['phone_number' => $phone]
                            );

                            $conversation->update([
                                'last_message_preview' => \Illuminate\Support\Str::limit($content, 50),
                                'last_message_time' => now(),
                                'unread_count' => \Illuminate\Support\Facades\DB::raw('unread_count + 1')
                            ]);

                            \App\Models\WhatsappMessage::create([
                                'conversation_id' => $conversation->id,
                                'meta_message_id' => $metaId,
                                'direction' => 'inbound',
                                'message_type' => $type,
                                'content' => $content,
                                'status' => 'delivered' // we received it
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp Webhook: ' . $e->getMessage());
        }

        return response('EVENT_RECEIVED', 200);
    }
}
