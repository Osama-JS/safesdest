<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhatsappChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_whatsapp_logs')->only(['index', 'getMessages', 'sendMessage']);
    }
    public function index()
    {
        $conversations = \App\Models\WhatsappConversation::orderBy('last_message_time', 'desc')->get();
        
        $stats = [
            'total_conversations' => \App\Models\WhatsappConversation::count(),
            'unread_messages' => \App\Models\WhatsappConversation::sum('unread_count'),
            'messages_sent_today' => \App\Models\WhatsappMessage::where('direction', 'outbound')->whereDate('created_at', today())->count(),
            'messages_received_today' => \App\Models\WhatsappMessage::where('direction', 'inbound')->whereDate('created_at', today())->count(),
        ];

        return view('admin.whatsapp-chat.index', compact('conversations', 'stats'));
    }

    public function getMessages($id)
    {
        $conversation = \App\Models\WhatsappConversation::findOrFail($id);
        
        // Mark as read
        $conversation->update(['unread_count' => 0]);
        
        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();
        
        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages->map(function($msg) {
                return [
                    'id' => $msg->id,
                    'direction' => $msg->direction,
                    'content' => $msg->content,
                    'status' => $msg->status,
                    'time' => $msg->created_at->format('H:i')
                ];
            })
        ]);
    }

    public function sendMessage(Request $request, $id, \App\Services\CloudWhatsAppService $waService)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $conversation = \App\Models\WhatsappConversation::findOrFail($id);
        $phone = $conversation->phone_number;
        
        // Find the last inbound message
        $lastInbound = \App\Models\WhatsappMessage::where('conversation_id', $conversation->id)
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'desc')
            ->first();

        // Check if last inbound message is within 24 hours
        if (!$lastInbound || $lastInbound->created_at->diffInHours(now()) >= 24) {
            return response()->json([
                'status' => 'error',
                'code' => 'window_closed',
                'message' => 'عذراً، لقد مرت أكثر من 24 ساعة منذ آخر رسالة من العميل. سياسات واتساب تمنع الرد بنص عادي الآن.'
            ]);
        }

        // Send normal text message
        $waService->sendTextMessage($phone, $request->message);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال الرسالة النصية بنجاح.'
        ]);
    }

    public function sendOpenChatTemplate($id, \App\Services\CloudWhatsAppService $waService)
    {
        $conversation = \App\Models\WhatsappConversation::findOrFail($id);
        $phone = $conversation->phone_number;

        $templateExists = \App\Models\WhatsappTemplate::where('purpose', 'open_chat')
            ->where('status', 1)
            ->exists();

        if (!$templateExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'لم يتم العثور على قالب نشط مخصص لفتح المحادثة (يجب أن يكون الغرض Purpose: open_chat).'
            ]);
        }

        $waService->sendTemplateMessage($phone, 'open_chat', []);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال قالب فتح المحادثة بنجاح.'
        ]);
    }
}
