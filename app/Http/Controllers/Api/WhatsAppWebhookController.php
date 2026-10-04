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
use App\Services\AdminNotificationDispatcher;

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

                        $message = WhatsappMessage::where('meta_message_id', $metaId)->first();
                        
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
                        
                        // Extract content based on type
                        $content = '';
                        if ($type === 'text') {
                            $content = $msg['text']['body'] ?? '';
                        } elseif ($type === 'image') {
                            $caption = $msg['image']['caption'] ?? '';
                            $content = '📷 صورة' . ($caption ? ": {$caption}" : '');
                        } elseif ($type === 'document') {
                            $docName = $msg['document']['filename'] ?? ($msg['document']['caption'] ?? '');
                            $content = '📄 مستند' . ($docName ? ": {$docName}" : '');
                        } elseif ($type === 'audio' || $type === 'voice') {
                            $content = '🎤 رسالة صوتية';
                        } elseif ($type === 'video') {
                            $caption = $msg['video']['caption'] ?? '';
                            $content = '🎥 مقطع فيديو' . ($caption ? ": {$caption}" : '');
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
                        $exists = WhatsappMessage::where('meta_message_id', $metaId)->exists();
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
                                'unread_count' => DB::raw('unread_count + 1')
                            ]);

                            $messageRecord = WhatsappMessage::create([
                                'conversation_id' => $conversation->id,
                                'meta_message_id' => $metaId,
                                'direction' => 'inbound',
                                'message_type' => $type,
                                'content' => $content,
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
}
