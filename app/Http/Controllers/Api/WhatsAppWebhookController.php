<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Settings;
use App\Services\AdminNotificationDispatcher;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify the webhook via GET request (used by Meta to verify webhook url)
     */
    public function verify(Request $request)
    {
        $verifyToken = Settings::getValue('whatsapp_verify_token', env('WHATSAPP_VERIFY_TOKEN'));

        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp Webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
            'expected_token' => $verifyToken ? 'SET' : 'NOT_SET'
        ]);

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
            // Check for Saei specific event structure: { "event": "message.received|message.sent|...", "data": { ... } }
            if (isset($data['event']) && is_string($data['event']) && isset($data['data'])) {
                $this->processSaeiEvent($data['event'], $data['data']);
                return response('EVENT_RECEIVED', 200);
            }

            $valuesToProcess = [];

            if (isset($data['entry']) && is_array($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    if (isset($entry['changes']) && is_array($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            if (isset($change['value'])) {
                                $valuesToProcess[] = $change['value'];
                            }
                        }
                    }
                }
            } elseif (isset($data['meta_raw']['value'])) {
                $valuesToProcess[] = $data['meta_raw']['value'];
            } elseif (isset($data['data']['messages']) || isset($data['data']['statuses'])) {
                $valuesToProcess[] = $data['data'];
            } elseif (isset($data['messages']) || isset($data['statuses'])) {
                $valuesToProcess[] = $data;
            }

            foreach ($valuesToProcess as $value) {
                // 1. Process Status Updates (Delivery Receipts)
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $statusUpdate) {
                        $metaId = $statusUpdate['id'];
                        $status = $statusUpdate['status']; // sent, delivered, read, failed

                        $message = WhatsappMessage::where('meta_message_id', $metaId)
                            ->orWhere('saei_message_id', $metaId)
                            ->first();
                        
                        if ($message) {
                            $updateData = ['status' => $status];
                            if ($status === 'sent' && !$message->sent_at) $updateData['sent_at'] = now();
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
                    $contactProfile = $value['contacts'][0]['profile']['name'] ?? null;

                    foreach ($value['messages'] as $msg) {
                        $rawPhone = $msg['from'];
                        $metaId = $msg['id'];
                        $type = $msg['type'];
                        
                        $mediaUrl = null;
                        $mediaFilename = null;
                        $mediaMimeType = null;

                        // Extract content based on type
                        $content = '';
                        if ($type === 'text') {
                            $content = $msg['text']['body'] ?? '';
                        } elseif ($type === 'image') {
                            $caption = $msg['image']['caption'] ?? '';
                            $content = '📷 صورة' . ($caption ? ": {$caption}" : '');
                            $mediaUrl = $msg['image']['link'] ?? ($msg['image']['url'] ?? null);
                            $mediaMimeType = $msg['image']['mime_type'] ?? null;
                        } elseif ($type === 'document') {
                            $mediaFilename = $msg['document']['filename'] ?? null;
                            $caption = $msg['document']['caption'] ?? '';
                            $content = '📄 مستند' . ($mediaFilename ? ": {$mediaFilename}" : ($caption ? ": {$caption}" : ''));
                            $mediaUrl = $msg['document']['link'] ?? ($msg['document']['url'] ?? null);
                            $mediaMimeType = $msg['document']['mime_type'] ?? null;
                        } elseif ($type === 'audio' || $type === 'voice') {
                            $content = '🎤 رسالة صوتية';
                            $mediaUrl = $msg['audio']['link'] ?? ($msg['audio']['url'] ?? null);
                        } elseif ($type === 'video') {
                            $caption = $msg['video']['caption'] ?? '';
                            $content = '🎥 مقطع فيديو' . ($caption ? ": {$caption}" : '');
                            $mediaUrl = $msg['video']['link'] ?? ($msg['video']['url'] ?? null);
                        } elseif ($type === 'location') {
                            $locName = $msg['location']['name'] ?? ($msg['location']['address'] ?? '');
                            $content = '📍 موقع جغرافي' . ($locName ? ": {$locName}" : '');
                        } elseif ($type === 'button') {
                            $content = $msg['button']['text'] ?? 'زر تفاعلي';
                        } elseif ($type === 'interactive') {
                            $content = $msg['interactive']['button_reply']['title'] 
                                ?? ($msg['interactive']['list_reply']['title'] ?? 'رد تفاعلي');
                        } else {
                            $content = "رسالة ({$type})";
                        }

                        // Prevent duplicate processing
                        $exists = WhatsappMessage::where('meta_message_id', $metaId)
                            ->orWhere('saei_message_id', $metaId)
                            ->exists();

                        if (!$exists) {
                            $normalizedPhone = $this->cleanPhoneNumber($rawPhone);

                            // Auto-resolve user (Customer or Driver)
                            $resolvedUser = $this->resolveUserByPhone($normalizedPhone);

                            $conversation = WhatsappConversation::firstOrCreate(
                                ['phone_number' => $normalizedPhone],
                                [
                                    'user_type' => $resolvedUser['user_type'],
                                    'user_id' => $resolvedUser['user_id'],
                                    'unread_count' => 0
                                ]
                            );

                            // Update user link if not previously assigned
                            if (!$conversation->user_id && $resolvedUser['user_id']) {
                                $conversation->update([
                                    'user_type' => $resolvedUser['user_type'],
                                    'user_id' => $resolvedUser['user_id'],
                                ]);
                            }

                            $conversation->update([
                                'last_message_preview' => Str::limit($content, 80),
                                'last_message_time' => now(),
                                'reply_window_expires_at' => now()->addHours(24),
                                'unread_count' => DB::raw('unread_count + 1')
                            ]);

                            $messageRecord = WhatsappMessage::create([
                                'conversation_id' => $conversation->id,
                                'meta_message_id' => $metaId,
                                'saei_message_id' => str_starts_with($metaId, 'imsg_') || str_starts_with($metaId, 'msg_') ? $metaId : null,
                                'direction' => 'inbound',
                                'message_type' => $type,
                                'content' => $content,
                                'media_url' => $mediaUrl,
                                'media_filename' => $mediaFilename,
                                'media_mime_type' => $mediaMimeType,
                                'status' => 'delivered',
                                'delivered_at' => now(),
                            ]);

                            // ─────────────────────────────────────────────────────────
                            // DISPATCH REAL-TIME ADMIN NOTIFICATION
                            // ─────────────────────────────────────────────────────────
                            $displayName = $resolvedUser['name'] ?: ($contactProfile ?: "+{$normalizedPhone}");
                            $typeLabel = $resolvedUser['type_label'];
                            $title = "رسالة واتساب جديدة من: {$displayName} [{$typeLabel}]";
                            $preview = Str::limit($content, 120);
                            $actionUrl = url('admin/whatsapp-chat?conversation_id=' . $conversation->id);

                            AdminNotificationDispatcher::dispatch(
                                'whatsapp_message_received',
                                $title,
                                $preview,
                                $actionUrl,
                                'ti-brand-whatsapp text-success',
                                [
                                    'conversation_id' => $conversation->id,
                                    'phone'           => $normalizedPhone,
                                    'user_type'       => $resolvedUser['user_type'],
                                    'user_id'         => $resolvedUser['user_id'],
                                    'message_id'      => $messageRecord->id,
                                    'content'         => $content,
                                ],
                                'high'
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp Webhook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Clean phone number into digits only.
     */
    protected function cleanPhoneNumber(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        return $digits;
    }

    /**
     * Resolve Customer or Driver by checking phone variations.
     */
    protected function resolveUserByPhone(string $phone): array
    {
        $variations = [$phone];
        $variations[] = '+' . $phone;

        // If Saudi number e.g. 9665xxxxxxxx
        if (str_starts_with($phone, '966') && strlen($phone) === 12) {
            $local = '0' . substr($phone, 3);
            $bare  = substr($phone, 3);
            $variations[] = $local;
            $variations[] = $bare;
        } elseif (str_starts_with($phone, '05') && strlen($phone) === 10) {
            $intl = '966' . substr($phone, 1);
            $variations[] = $intl;
            $variations[] = '+' . $intl;
        }

        // 1. Check Customer
        $customer = Customer::where(function ($q) use ($variations, $phone) {
            $q->whereIn('phone', $variations)
              ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', ''), '(', '') = ?", [$phone]);
        })->first();

        if ($customer) {
            return [
                'user_type'  => 'customer',
                'user_id'    => $customer->id,
                'name'       => $customer->name,
                'type_label' => 'عميل',
                'user'       => $customer
            ];
        }

        // 2. Check Driver
        $driver = Driver::where(function ($q) use ($variations, $phone) {
            $q->whereIn('phone', $variations)
              ->orWhereIn('whatsapp_number', $variations)
              ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', ''), '(', '') = ?", [$phone])
              ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(whatsapp_number, '-', ''), ' ', ''), '+', ''), '(', '') = ?", [$phone]);
        })->first();

        if ($driver) {
            return [
                'user_type'  => 'driver',
                'user_id'    => $driver->id,
                'name'       => $driver->name,
                'type_label' => 'سائق',
                'user'       => $driver
            ];
        }

        return [
            'user_type'  => null,
            'user_id'    => null,
            'name'       => null,
            'type_label' => 'غير مسجل',
            'user'       => null
        ];
    }

    /**
     * Process native Saei webhook events:
     * - message.received
     * - message.sent
     * - message.delivered
     * - message.read
     * - message.failed
     */
    protected function processSaeiEvent(string $event, array $messageData): void
    {
        $saeiId    = $messageData['id'] ?? null;
        $metaId    = $messageData['provider_message_id'] ?? null;
        $reference = $messageData['reference'] ?? null;

        // 1. Status Events: message.sent, message.delivered, message.read, message.failed
        if (str_starts_with($event, 'message.') && $event !== 'message.received') {
            $statusName = explode('.', $event)[1] ?? 'delivered';
            
            $query = WhatsappMessage::query();
            if ($saeiId) {
                $query->where('saei_message_id', $saeiId);
            } elseif ($metaId) {
                $query->where('meta_message_id', $metaId);
            } elseif ($reference) {
                $query->where('reference', $reference);
            } else {
                return;
            }

            $message = $query->first();
            if ($message) {
                $update = ['status' => $statusName];
                if ($statusName === 'sent' && !$message->sent_at) $update['sent_at'] = now();
                if ($statusName === 'delivered') $update['delivered_at'] = now();
                if ($statusName === 'read') $update['read_at'] = now();

                if ($statusName === 'failed' && isset($messageData['error'])) {
                    $update['error_code']  = $messageData['error']['code'] ?? null;
                    $update['error_title'] = $messageData['error']['title'] ?? null;
                    $update['error_log']   = json_encode($messageData['error'], JSON_UNESCAPED_UNICODE);
                }

                $message->update($update);
            }
            return;
        }

        // 2. Inbound Message Event: message.received
        if ($event === 'message.received') {
            $rawPhone = $messageData['from'] ?? null;
            if (!$rawPhone) return;

            $type = $messageData['type'] ?? 'text';
            $mediaUrl = null;
            $mediaFilename = null;
            $mediaMimeType = null;
            $content = '';

            if ($type === 'text') {
                $content = $messageData['text']['body'] ?? '';
            } elseif ($type === 'image') {
                $caption = $messageData['image']['caption'] ?? '';
                $content = '📷 صورة' . ($caption ? ": {$caption}" : '');
                $mediaUrl = $messageData['image']['link'] ?? ($messageData['image']['url'] ?? null);
            } elseif ($type === 'document') {
                $mediaFilename = $messageData['document']['filename'] ?? null;
                $caption = $messageData['document']['caption'] ?? '';
                $content = '📄 مستند' . ($mediaFilename ? ": {$mediaFilename}" : ($caption ? ": {$caption}" : ''));
                $mediaUrl = $messageData['document']['link'] ?? ($messageData['document']['url'] ?? null);
            } elseif ($type === 'audio') {
                $content = '🎤 رسالة صوتية';
                $mediaUrl = $messageData['audio']['link'] ?? null;
            } elseif ($type === 'video') {
                $caption = $messageData['video']['caption'] ?? '';
                $content = '🎥 مقطع فيديو' . ($caption ? ": {$caption}" : '');
                $mediaUrl = $messageData['video']['link'] ?? null;
            } elseif ($type === 'interactive') {
                $content = $messageData['interactive']['reply']['title'] ?? 'رد تفاعلي';
            } elseif ($type === 'reaction') {
                $emoji = $messageData['reaction']['emoji'] ?? '';
                $content = $emoji ? "تفاعل بـ: {$emoji}" : 'أزال التفاعل';
            } else {
                $content = "رسالة ({$type})";
            }

            // Check duplicate
            $exists = WhatsappMessage::where(function($q) use ($saeiId, $metaId) {
                if ($saeiId) $q->where('saei_message_id', $saeiId);
                if ($metaId) $q->orWhere('meta_message_id', $metaId);
            })->exists();

            if (!$exists) {
                $normalizedPhone = $this->cleanPhoneNumber($rawPhone);
                $resolvedUser = $this->resolveUserByPhone($normalizedPhone);

                $conversation = WhatsappConversation::firstOrCreate(
                    ['phone_number' => $normalizedPhone],
                    [
                        'user_type' => $resolvedUser['user_type'],
                        'user_id'   => $resolvedUser['user_id'],
                        'unread_count' => 0
                    ]
                );

                if (!$conversation->user_id && $resolvedUser['user_id']) {
                    $conversation->update([
                        'user_type' => $resolvedUser['user_type'],
                        'user_id'   => $resolvedUser['user_id'],
                    ]);
                }

                $conversation->update([
                    'saei_conversation_id'    => $messageData['conversation_id'] ?? $conversation->saei_conversation_id,
                    'last_message_preview'    => Str::limit($content, 80),
                    'last_message_time'       => now(),
                    'reply_window_expires_at' => now()->addHours(24),
                    'unread_count'            => DB::raw('unread_count + 1')
                ]);

                $messageRecord = WhatsappMessage::create([
                    'conversation_id'     => $conversation->id,
                    'saei_message_id'     => $saeiId,
                    'meta_message_id'     => $metaId,
                    'direction'           => 'inbound',
                    'message_type'        => $type,
                    'content'             => $content,
                    'media_url'           => $mediaUrl,
                    'media_filename'      => $mediaFilename,
                    'status'              => 'delivered',
                    'delivered_at'        => now(),
                ]);

                // Dispatch notification
                $displayName = $resolvedUser['name'] ?: ($messageData['contact']['name'] ?? "+{$normalizedPhone}");
                $typeLabel   = $resolvedUser['type_label'];
                $title       = "رسالة واتساب جديدة من: {$displayName} [{$typeLabel}]";

                AdminNotificationDispatcher::dispatch(
                    'whatsapp_message_received',
                    $title,
                    Str::limit($content, 120),
                    url('admin/whatsapp-chat?conversation_id=' . $conversation->id),
                    'ti-brand-whatsapp text-success',
                    [
                        'conversation_id' => $conversation->id,
                        'phone'           => $normalizedPhone,
                        'user_type'       => $resolvedUser['user_type'],
                        'user_id'         => $resolvedUser['user_id'],
                        'message_id'      => $messageRecord->id,
                        'content'         => $content,
                    ],
                    'high'
                );
            }
        }
    }
}
