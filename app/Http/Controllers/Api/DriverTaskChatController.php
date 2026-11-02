<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DriverTaskChatController extends Controller
{
    /**
     * Get chat messages for an active task
     */
    public function index(Request $request, $taskId)
    {
        try {
            $driver = $request->user();
            $task = Task::with(['customer'])
                ->where('driver_id', $driver->id)
                ->findOrFail($taskId);

            // Fetch messages
            $messages = TaskChatMessage::where('task_id', $task->id)
                ->orderBy('created_at', 'asc')
                ->get();

            // Mark unread messages sent to driver as read
            TaskChatMessage::where('task_id', $task->id)
                ->where('sender_type', '!=', 'driver')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            $formatted = $messages->map(function ($msg) use ($driver, $task) {
                $isMe = ($msg->sender_type === 'driver' && $msg->sender_id == $driver->id);
                return [
                    'id'             => $msg->id,
                    'message'        => $msg->message,
                    'attachment_url' => $msg->attachment_url ? url($msg->attachment_url) : null,
                    'sender_type'    => $msg->sender_type,
                    'is_me'          => $isMe,
                    'is_driver'      => $msg->sender_type === 'driver',
                    'sender_name'    => $isMe ? $driver->name : ($task->customer?->name ?? 'العميل'),
                    'is_read'        => $msg->is_read,
                    'created_at'     => $msg->created_at ? $msg->created_at->format('H:i') : '',
                    'timestamp'      => $msg->created_at ? $msg->created_at->toIso8601String() : null,
                ];
            });

            return response()->json([
                'success'       => true,
                'task_id'       => $task->id,
                'customer_name' => $task->customer?->name ?? 'العميل',
                'customer_phone'=> $task->customer?->phone ?? '',
                'task_status'   => $task->status,
                'is_active'     => !in_array($task->status, ['completed', 'cancelled', 'refund']),
                'messages'      => $formatted,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Task chat index error', [
                'error'     => $e->getMessage(),
                'task_id'   => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages',
            ], 500);
        }
    }

    /**
     * Send a message from driver to customer on an active task
     */
    public function store(Request $request, $taskId)
    {
        try {
            $driver = $request->user();
            $task = Task::with(['customer'])
                ->where('driver_id', $driver->id)
                ->findOrFail($taskId);

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
                'sender_type'    => 'driver',
                'sender_id'      => $driver->id,
                'receiver_type'  => 'customer',
                'receiver_id'    => $task->customer_id,
                'message'        => $request->input('message', '') ?? '',
                'attachment_url' => $attachmentUrl,
                'is_read'        => false,
            ]);

            // Broadcast real-time event via Laravel Reverb WebSockets
            try {
                broadcast(new \App\Events\TaskChatMessageSent($chatMessage));
            } catch (\Throwable $e) {
                Log::warning('Failed to broadcast TaskChatMessageSent: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data'    => [
                    'id'             => $chatMessage->id,
                    'message'        => $chatMessage->message,
                    'attachment_url' => $chatMessage->attachment_url ? url($chatMessage->attachment_url) : null,
                    'sender_type'    => 'driver',
                    'is_me'          => true,
                    'is_driver'      => true,
                    'sender_name'    => $driver->name,
                    'is_read'        => false,
                    'created_at'     => $chatMessage->created_at->format('H:i'),
                    'timestamp'      => $chatMessage->created_at->toIso8601String(),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Task chat store error', [
                'error'     => $e->getMessage(),
                'task_id'   => $taskId,
                'driver_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
            ], 500);
        }
    }
}

