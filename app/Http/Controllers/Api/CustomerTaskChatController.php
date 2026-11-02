<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CustomerTaskChatController extends Controller
{
    /**
     * Get chat messages for a customer's task
     */
    public function index(Request $request, $taskId)
    {
        try {
            $customer = $request->user();
            $task = Task::with(['driver.vehicleSize', 'driver.team'])
                ->where('customer_id', $customer->id)
                ->findOrFail($taskId);

            // Fetch messages
            $messages = TaskChatMessage::where('task_id', $task->id)
                ->orderBy('created_at', 'asc')
                ->get();

            // Mark unread messages sent by driver as read
            TaskChatMessage::where('task_id', $task->id)
                ->where('sender_type', 'driver')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            $formatted = $messages->map(function ($msg) use ($customer, $task) {
                $isMe = ($msg->sender_type === 'customer' && $msg->sender_id == $customer->id);
                return [
                    'id'             => $msg->id,
                    'message'        => $msg->message,
                    'attachment_url' => $msg->attachment_url ? url($msg->attachment_url) : null,
                    'sender_type'    => $msg->sender_type,
                    'is_me'          => $isMe,
                    'is_driver'      => $msg->sender_type === 'driver',
                    'sender_name'    => $isMe ? 'أنا' : ($task->driver?->name ?? 'السائق'),
                    'is_read'        => (bool) $msg->is_read,
                    'created_at'     => $msg->created_at ? $msg->created_at->format('H:i') : '',
                    'timestamp'      => $msg->created_at ? $msg->created_at->toIso8601String() : null,
                ];
            });

            return response()->json([
                'success'      => true,
                'task_id'      => $task->id,
                'driver_name'  => $task->driver?->name ?? 'السائق',
                'driver_phone' => $task->driver?->phone ?? '',
                'task_status'  => $task->status,
                'is_active'    => !in_array($task->status, ['completed', 'cancelled', 'refund']),
                'has_driver'   => !empty($task->driver_id),
                'messages'     => $formatted,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Customer task chat index error', [
                'error'       => $e->getMessage(),
                'task_id'     => $taskId,
                'customer_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages',
            ], 500);
        }
    }

    /**
     * Send a message from customer to driver on a task
     */
    public function store(Request $request, $taskId)
    {
        try {
            $customer = $request->user();
            $task = Task::with(['driver'])
                ->where('customer_id', $customer->id)
                ->findOrFail($taskId);

            // Check if driver is assigned
            if (empty($task->driver_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم إسناد سائق لهذه المهمة بعد.',
                ], 400);
            }

            // Block messaging if task is completed or cancelled
            if (in_array($task->status, ['completed', 'cancelled', 'refund']) || $task->closed) {
                return response()->json([
                    'success' => false,
                    'message' => 'المهمة منتهية، لا يمكن إرسال رسائل جديدة.',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'message'    => 'required_without:attachment|nullable|string|max:2000',
                'attachment' => 'nullable|file|max:10240|mimes:jpeg,png,jpg,webp',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $attachmentUrl = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = 'chat_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('task_chat_attachments/' . $task->id, $filename, 'public');
                $attachmentUrl = 'storage/' . $path;
            }

            $chatMessage = TaskChatMessage::create([
                'task_id'        => $task->id,
                'sender_type'    => 'customer',
                'sender_id'      => $customer->id,
                'receiver_type'  => 'driver',
                'receiver_id'    => $task->driver_id,
                'message'        => $request->input('message', '') ?? '',
                'attachment_url' => $attachmentUrl,
                'is_read'        => false,
            ]);

            // Broadcast real-time event via Laravel Reverb WebSockets
            try {
                broadcast(new \App\Events\TaskChatMessageSent($chatMessage));
            } catch (\Throwable $e) {
                Log::warning('Failed to broadcast TaskChatMessageSent to Reverb: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data'    => [
                    'id'             => $chatMessage->id,
                    'message'        => $chatMessage->message,
                    'attachment_url' => $chatMessage->attachment_url ? url($chatMessage->attachment_url) : null,
                    'sender_type'    => 'customer',
                    'is_me'          => true,
                    'is_driver'      => false,
                    'sender_name'    => 'أنا',
                    'is_read'        => false,
                    'created_at'     => $chatMessage->created_at ? $chatMessage->created_at->format('H:i') : '',
                    'timestamp'      => $chatMessage->created_at ? $chatMessage->created_at->toIso8601String() : null,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Customer task chat store error', [
                'error'       => $e->getMessage(),
                'task_id'     => $taskId,
                'customer_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
            ], 500);
        }
    }
}

