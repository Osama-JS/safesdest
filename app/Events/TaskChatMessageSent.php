<?php

namespace App\Events;

use App\Models\TaskChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(TaskChatMessage $message)
    {
        $this->message = [
            'id' => $message->id,
            'task_id' => $message->task_id,
            'sender_type' => $message->sender_type,
            'sender_id' => $message->sender_id,
            'receiver_type' => $message->receiver_type,
            'receiver_id' => $message->receiver_id,
            'message' => $message->message,
            'attachment_url' => $message->attachment_url ? url($message->attachment_url) : null,
            'is_read' => (bool) $message->is_read,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('task.chat.' . $this->message['task_id']),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
