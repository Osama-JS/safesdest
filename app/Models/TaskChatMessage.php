<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskChatMessage extends Model
{
    use HasFactory;

    protected $table = 'task_chat_messages';

    protected $fillable = [
        'task_id',
        'sender_type',
        'sender_id',
        'receiver_type',
        'receiver_id',
        'message',
        'attachment_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function driverSender()
    {
        return $this->belongsTo(Driver::class, 'sender_id');
    }

    public function customerSender()
    {
        return $this->belongsTo(Customer::class, 'sender_id');
    }
}

