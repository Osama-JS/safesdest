<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification_Drivers extends Model
{
    use LogsActivity;

    protected $table = 'notifications_drivers';
    protected $fillable = [
        'notification_id',
        'driver_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the notification that this record belongs to
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

    /**
     * Get the driver that this record belongs to
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('status', false);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update(['status' => true]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update(['status' => false]);
    }
}
