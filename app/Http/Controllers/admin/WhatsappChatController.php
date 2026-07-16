<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhatsappChatController extends Controller
{
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
}
