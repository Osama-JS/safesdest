<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    //
    protected $fillable = [
        'conversation_id',
        'meta_message_id',
        'saei_message_id',
        'reference',
        'direction',
        'message_type',
        'content',
        'media_url',
        'media_filename',
        'media_mime_type',
        'status',
        'error_code',
        'error_title',
        'error_log',
        'sent_at',
        'delivered_at',
        'read_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }
}
