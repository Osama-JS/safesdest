<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConversation extends Model
{
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

    protected $appends = [
        'user_name',
        'user_type_label',
        'is_window_open',
        'window_remaining_hours'
    ];

    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class, 'conversation_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'user_id');
    }

    public function getUserAttribute()
    {
        if ($this->user_type === 'customer') {
            return $this->customer;
        } elseif ($this->user_type === 'driver') {
            return $this->driver;
        }
        return null;
    }

    public function getUserNameAttribute(): string
    {
        $user = $this->user;
        if ($user && !empty($user->name)) {
            return $user->name;
        }
        return '+' . ltrim($this->phone_number, '+');
    }

    public function getUserTypeLabelAttribute(): string
    {
        return match ($this->user_type) {
            'customer' => 'عميل',
            'driver'   => 'سائق',
            default    => 'رقم غير مسجل',
        };
    }

    public function lastInboundMessage()
    {
        return $this->hasOne(WhatsappMessage::class, 'conversation_id')
            ->where('direction', 'inbound')
            ->latest('created_at');
    }

    public function getIsWindowOpenAttribute(): bool
    {
        $lastInbound = $this->lastInboundMessage;
        if (!$lastInbound || !$lastInbound->created_at) {
            return false;
        }
        return $lastInbound->created_at->diffInHours(now()) < 24;
    }

    public function getWindowRemainingHoursAttribute(): int
    {
        $lastInbound = $this->lastInboundMessage;
        if (!$lastInbound || !$lastInbound->created_at) {
            return 0;
        }
        $diff = 24 - $lastInbound->created_at->diffInHours(now());
        return max(0, $diff);
    }
}
