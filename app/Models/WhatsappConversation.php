<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConversation extends Model
{
    protected $fillable = [
        'phone_number',
        'user_type',
        'user_id',
        'saei_conversation_id',
        'last_message_preview',
        'last_message_time',
        'reply_window_expires_at',
        'unread_count'
    ];

    protected $casts = [
        'last_message_time' => 'datetime',
        'reply_window_expires_at' => 'datetime',
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
        if ($this->reply_window_expires_at) {
            return $this->reply_window_expires_at->isFuture();
        }
        $lastInbound = $this->lastInboundMessage;
        if (!$lastInbound || !$lastInbound->created_at) {
            return false;
        }
        return $lastInbound->created_at->diffInHours(now()) < 24;
    }

    public function getWindowRemainingHoursAttribute(): int
    {
        if ($this->reply_window_expires_at) {
            return max(0, (int) now()->diffInHours($this->reply_window_expires_at, false));
        }
        $lastInbound = $this->lastInboundMessage;
        if (!$lastInbound || !$lastInbound->created_at) {
            return 0;
        }
        $diff = 24 - $lastInbound->created_at->diffInHours(now());
        return max(0, $diff);
    }

    /**
     * Normalize any phone string to clean international digits without '+'
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '05') && strlen($digits) === 10) {
            $digits = '966' . substr($digits, 1);
        }
        return $digits;
    }

    /**
     * Find or create conversation by phone number, matching with or without '+'
     */
    public static function findOrCreateByPhone(string $phone, array $attributes = []): self
    {
        $cleanPhone = self::normalizePhone($phone);

        $conversation = self::where('phone_number', $cleanPhone)
            ->orWhere('phone_number', '+' . $cleanPhone)
            ->first();

        if ($conversation) {
            // Ensure the stored phone number is consistently digits-only
            if ($conversation->phone_number !== $cleanPhone) {
                $conversation->update(['phone_number' => $cleanPhone]);
            }
            if (!empty($attributes)) {
                $updates = [];
                foreach ($attributes as $k => $v) {
                    if ($conversation->$k === null && $v !== null) {
                        $updates[$k] = $v;
                    }
                }
                if (!empty($updates)) {
                    $conversation->update($updates);
                }
            }
            return $conversation;
        }

        return self::create(array_merge([
            'phone_number' => $cleanPhone,
            'unread_count' => 0,
        ], $attributes));
    }
}
