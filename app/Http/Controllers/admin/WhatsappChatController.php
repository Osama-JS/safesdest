<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Notification;
use App\Models\Notification_Users;
use App\Services\Interfaces\WhatsAppServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsappChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_whatsapp_chat')->only(['index', 'getMessages', 'pollMessages', 'widgetSummary']);
        $this->middleware('permission:send_whatsapp_chat')->only(['sendMessage', 'sendTemplate', 'sendOpenChatTemplate', 'startNewChat']);
    }

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $search = $request->query('search');
        $activeConversationId = $request->query('conversation_id');

        $query = WhatsappConversation::with(['customer', 'driver'])
            ->orderBy('last_message_time', 'desc');

        if ($filter === 'customers') {
            $query->where('user_type', 'customer');
        } elseif ($filter === 'drivers') {
            $query->where('user_type', 'driver');
        } elseif ($filter === 'unread') {
            $query->where('unread_count', '>', 0);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('phone_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('email', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('driver', function($dq) use ($search) {
                      $dq->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        $conversations = $query->get();

        $stats = [
            'total_conversations'     => WhatsappConversation::count(),
            'unread_messages'         => WhatsappConversation::sum('unread_count'),
            'messages_sent_today'     => WhatsappMessage::where('direction', 'outbound')->whereDate('created_at', today())->count(),
            'messages_received_today' => WhatsappMessage::where('direction', 'inbound')->whereDate('created_at', today())->count(),
        ];

        // Active approved templates for quick sending
        $approvedTemplates = WhatsappTemplate::where('status', 1)
            ->where(function($q) {
                $q->where('meta_status', 'APPROVED')
                  ->orWhereNull('meta_status');
            })
            ->get();

        return view('admin.whatsapp-chat.index', compact(
            'conversations',
            'stats',
            'filter',
            'search',
            'activeConversationId',
            'approvedTemplates'
        ));
    }

    public function getMessages($id)
    {
        $conversation = WhatsappConversation::with(['customer.wallet', 'driver.wallet'])->findOrFail($id);
        
        // Mark conversation as read
        $conversation->update(['unread_count' => 0]);

        // Mark system notification as read for current user
        if (auth()->check()) {
            try {
                $notifIds = Notification::where('event_key', 'whatsapp_message_received')
                    ->where(function($q) use ($id) {
                        $q->where('action_url', 'LIKE', "%conversation_id={$id}%")
                          ->orWhere('data->conversation_id', $id);
                    })
                    ->pluck('id');

                if ($notifIds->isNotEmpty()) {
                    Notification_Users::where('user_id', auth()->id())
                        ->whereIn('notification_id', $notifIds)
                        ->where('status', false)
                        ->update(['status' => true, 'is_shown' => true]);
                }
            } catch (\Throwable $e) {
                Log::warning('Error marking whatsapp notification as read: ' . $e->getMessage());
            }
        }
        
        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        // 24-hour window status
        $lastInbound = $conversation->lastInboundMessage;
        $isWindowOpen = false;
        $remainingHours = 0;
        $lastInboundTimeFormatted = null;

        if ($lastInbound && $lastInbound->created_at) {
            $diffHours = $lastInbound->created_at->diffInHours(now());
            if ($diffHours < 24) {
                $isWindowOpen = true;
                $remainingHours = 24 - $diffHours;
            }
            $lastInboundTimeFormatted = $lastInbound->created_at->diffForHumans();
        }

        // Build User Profile Info
        $userInfo = [
            'type'           => $conversation->user_type ?? 'unregistered',
            'type_label'     => $conversation->user_type_label,
            'name'           => $conversation->user_name,
            'phone'          => $conversation->phone_number,
            'email'          => null,
            'avatar'         => null,
            'wallet_balance' => 0,
            'tasks_count'    => 0,
            'profile_url'    => null,
            'registered_at'  => null,
        ];

        if ($conversation->user_type === 'customer' && $conversation->customer) {
            $c = $conversation->customer;
            $userInfo['email'] = $c->email;
            $userInfo['avatar'] = $c->image ? asset('storage/' . $c->image) : null;
            $userInfo['wallet_balance'] = $c->wallet ? $c->wallet->balance : 0;
            $userInfo['tasks_count'] = $c->tasks()->count();
            $userInfo['profile_url'] = url("admin/customers/{$c->id}");
            $userInfo['registered_at'] = $c->created_at ? $c->created_at->format('Y-m-d') : null;
        } elseif ($conversation->user_type === 'driver' && $conversation->driver) {
            $d = $conversation->driver;
            $userInfo['email'] = $d->email;
            $userInfo['avatar'] = $d->image ? asset('storage/' . $d->image) : null;
            $userInfo['wallet_balance'] = $d->wallet ? $d->wallet->balance : 0;
            $userInfo['tasks_count'] = $d->tasks()->count();
            $userInfo['profile_url'] = url("admin/drivers/{$d->id}");
            $userInfo['registered_at'] = $d->created_at ? $d->created_at->format('Y-m-d') : null;
        }

        $unreadMessagesTotal = WhatsappConversation::sum('unread_count');
        $unreadConversationsCount = WhatsappConversation::where('unread_count', '>', 0)->count();

        return response()->json([
            'status' => 'success',
            'conversation' => [
                'id'                     => $conversation->id,
                'phone_number'           => $conversation->phone_number,
                'user_name'              => $conversation->user_name,
                'user_type'              => $conversation->user_type,
                'user_type_label'        => $conversation->user_type_label,
                'is_window_open'         => $isWindowOpen,
                'window_remaining_hours' => $remainingHours,
                'last_inbound_time'      => $lastInboundTimeFormatted,
            ],
            'user_info' => $userInfo,
            'unread_stats' => [
                'unread_messages'      => (int) $unreadMessagesTotal,
                'unread_conversations' => (int) $unreadConversationsCount,
            ],
            'messages' => $messages->map(function($msg) {
                return [
                    'id'           => $msg->id,
                    'direction'    => $msg->direction,
                    'message_type' => $msg->message_type,
                    'content'      => $msg->content,
                    'status'       => $msg->status,
                    'time'         => $msg->created_at ? $msg->created_at->format('h:i A') : '',
                    'date'         => $msg->created_at ? $msg->created_at->format('Y-m-d') : '',
                    'is_today'     => $msg->created_at ? $msg->created_at->isToday() : false,
                ];
            })
        ]);
    }

    public function pollMessages($id, Request $request)
    {
        $afterId = (int) $request->query('after_id', 0);
        $conversation = WhatsappConversation::findOrFail($id);

        // Mark as read
        $conversation->update(['unread_count' => 0]);

        $newMessages = $conversation->messages()
            ->where('id', '>', $afterId)
            ->orderBy('created_at', 'asc')
            ->get();

        // 24-hour window status
        $lastInbound = $conversation->lastInboundMessage;
        $isWindowOpen = false;
        $remainingHours = 0;

        if ($lastInbound && $lastInbound->created_at) {
            $diffHours = $lastInbound->created_at->diffInHours(now());
            if ($diffHours < 24) {
                $isWindowOpen = true;
                $remainingHours = 24 - $diffHours;
            }
        }

        $unreadMessagesTotal = WhatsappConversation::sum('unread_count');

        return response()->json([
            'status'                 => 'success',
            'has_new'                => $newMessages->count() > 0,
            'is_window_open'         => $isWindowOpen,
            'window_remaining_hours' => $remainingHours,
            'unread_stats' => [
                'unread_messages' => (int) $unreadMessagesTotal,
            ],
            'messages' => $newMessages->map(function($msg) {
                return [
                    'id'           => $msg->id,
                    'direction'    => $msg->direction,
                    'message_type' => $msg->message_type,
                    'content'      => $msg->content,
                    'status'       => $msg->status,
                    'time'         => $msg->created_at ? $msg->created_at->format('h:i A') : '',
                    'date'         => $msg->created_at ? $msg->created_at->format('Y-m-d') : '',
                    'is_today'     => $msg->created_at ? $msg->created_at->isToday() : false,
                ];
            })
        ]);
    }

    public function sendMessage(Request $request, $id, WhatsAppServiceInterface $waService)
    {
        $request->validate([
            'message' => 'required|string|max:4000'
        ]);

        $conversation = WhatsappConversation::findOrFail($id);
        $phone = $conversation->phone_number;
        
        $lastInbound = $conversation->lastInboundMessage;

        // Check 24hr window
        if (!$lastInbound || $lastInbound->created_at->diffInHours(now()) >= 24) {
            return response()->json([
                'status'  => 'error',
                'code'    => 'window_closed',
                'message' => 'عذراً، لقد مرت أكثر من 24 ساعة منذ آخر رسالة وردت من العميل. تفرض سياسات ميتا إرسال قالب رسمي أولاً لإعادة فتح نافذة المحادثة.'
            ]);
        }

        // Send direct text message
        $result = $waService->sendTextMessage($phone, $request->message);

        if (is_array($result) && !($result['success'] ?? false)) {
            return response()->json([
                'status'  => 'error',
                'code'    => $result['code'] ?? 'send_failed',
                'message' => $result['message'] ?? 'فشل إرسال الرسالة إلى واتساب'
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إرسال الرسالة بنجاح.',
            'time'    => now()->format('h:i A'),
        ]);
    }

    public function sendTemplate(Request $request, $id, WhatsAppServiceInterface $waService)
    {
        $request->validate([
            'template_name' => 'required|string',
            'variables'     => 'nullable|array'
        ]);

        $conversation = WhatsappConversation::findOrFail($id);
        $phone = $conversation->phone_number;

        $templateName = $request->template_name;
        $variables = $request->input('variables', []);

        $result = $waService->sendTemplateMessage($phone, $templateName, $variables);

        if (is_array($result) && !($result['success'] ?? false)) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message'] ?? 'فشل إرسال القالب عبر واتساب'
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إرسال القالب بنجاح وإعادة تفعيل المحادثة.',
            'time'    => now()->format('h:i A')
        ]);
    }

    public function sendOpenChatTemplate($id, WhatsAppServiceInterface $waService)
    {
        $conversation = WhatsappConversation::findOrFail($id);
        $phone = $conversation->phone_number;

        // Try open_chat, or fallback to first active approved template
        $template = WhatsappTemplate::where('purpose', 'open_chat')
            ->where('status', 1)
            ->first();

        if (!$template) {
            $template = WhatsappTemplate::where('status', 1)
                ->where(function($q) {
                    $q->where('meta_status', 'APPROVED')
                      ->orWhereNull('meta_status');
                })
                ->first();
        }

        if (!$template) {
            return response()->json([
                'status'  => 'error',
                'message' => 'لا توجد قوالب نشطة معتمدة في النظام لإرسالها.'
            ]);
        }

        $result = $waService->sendTemplateMessage($phone, $template->template_name, []);

        if (is_array($result) && !($result['success'] ?? false)) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message'] ?? 'فشل إرسال القالب'
            ]);
        }

        return response()->json([
            'status'        => 'success',
            'template_name' => $template->template_name,
            'message'       => "تم إرسال القالب ({$template->template_name}) بنجاح لفتح المحادثة.",
            'time'          => now()->format('h:i A')
        ]);
    }

    public function startNewChat(Request $request, WhatsAppServiceInterface $waService)
    {
        $request->validate([
            'phone'         => 'required|string',
            'template_name' => 'required|string',
        ]);

        // Clean phone
        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }
        if (str_starts_with($phone, '05') && strlen($phone) === 10) {
            $phone = '966' . substr($phone, 1);
        }

        if (empty($phone)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'يرجى إدخال رقم هاتف صحيح.'
            ]);
        }

        // Auto-resolve user (Customer or Driver)
        $resolved = $this->resolveUserByPhone($phone);

        // Find or create conversation
        $conversation = WhatsappConversation::firstOrCreate(
            ['phone_number' => $phone],
            [
                'user_type'    => $resolved['user_type'],
                'user_id'      => $resolved['user_id'],
                'unread_count' => 0
            ]
        );

        if (!$conversation->user_id && $resolved['user_id']) {
            $conversation->update([
                'user_type' => $resolved['user_type'],
                'user_id'   => $resolved['user_id'],
            ]);
        }

        // Send the template message
        $result = $waService->sendTemplateMessage($phone, $request->template_name, []);

        if (is_array($result) && !($result['success'] ?? false)) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message'] ?? 'فشل إرسال القالب عبر واتساب'
            ]);
        }

        return response()->json([
            'status'          => 'success',
            'message'         => 'تم إرسال القالب وبدء المحادثة بنجاح.',
            'conversation_id' => $conversation->id
        ]);
    }

    protected function resolveUserByPhone(string $phone): array
    {
        $variations = [$phone, '+' . $phone];

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
            ];
        }

        return [
            'user_type'  => null,
            'user_id'    => null,
            'name'       => null,
            'type_label' => 'غير مسجل',
        ];
    }

    /**
     * Get lightweight summary data for the floating chat widget
     */
    public function widgetSummary(Request $request)
    {
        $search = $request->query('search');
        $filter = $request->query('filter', 'all');

        $query = WhatsappConversation::with(['customer', 'driver'])
            ->orderBy('last_message_time', 'desc');

        if ($filter === 'customers') {
            $query->where('user_type', 'customer');
        } elseif ($filter === 'drivers') {
            $query->where('user_type', 'driver');
        } elseif ($filter === 'unread') {
            $query->where('unread_count', '>', 0);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('phone_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('driver', function($dq) use ($search) {
                      $dq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $conversations = $query->limit(40)->get()->map(function($c) {
            $avatar = null;
            if ($c->user_type === 'customer' && $c->customer && $c->customer->image) {
                $avatar = asset('storage/' . $c->customer->image);
            } elseif ($c->user_type === 'driver' && $c->driver && $c->driver->image) {
                $avatar = asset('storage/' . $c->driver->image);
            }

            // Check 24h window
            $lastInbound = $c->lastInboundMessage;
            $isWindowOpen = false;
            if ($lastInbound && $lastInbound->created_at) {
                $isWindowOpen = $lastInbound->created_at->diffInHours(now()) < 24;
            }

            return [
                'id' => $c->id,
                'phone_number' => $c->phone_number,
                'user_name' => $c->user_name,
                'user_type' => $c->user_type,
                'user_type_label' => $c->user_type_label,
                'avatar' => $avatar,
                'last_message_preview' => $c->last_message_preview,
                'last_message_time' => $c->last_message_time ? $c->last_message_time->diffForHumans() : '',
                'unread_count' => (int) $c->unread_count,
                'is_window_open' => $isWindowOpen,
            ];
        });

        $unreadTotal = (int) WhatsappConversation::sum('unread_count');
        $unreadConversations = (int) WhatsappConversation::where('unread_count', '>', 0)->count();

        // Get approved templates for quick sending
        $approvedTemplates = WhatsappTemplate::where('status', 1)
            ->where(function($q) {
                $q->where('meta_status', 'APPROVED')->orWhereNull('meta_status');
            })
            ->get(['id', 'template_name', 'category', 'language', 'body_text'])
            ->map(function($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->template_name,
                    'category' => $t->category,
                    'language' => $t->language,
                    'body' => $t->body_text,
                ];
            });

        return response()->json([
            'status' => 'success',
            'unread_total' => $unreadTotal,
            'unread_conversations' => $unreadConversations,
            'conversations' => $conversations,
            'approved_templates' => $approvedTemplates,
        ]);
    }
}
