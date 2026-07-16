<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConversation extends Model
{
    //
    protected $fillable = [
        'phone_number',
        'user_type',
        'user_id',
        'last_message_preview',
        'last_message_time',
        'unread_count'
    ];

    protected $casts = [
        'last_message_time' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class, 'conversation_id');
    }
}
