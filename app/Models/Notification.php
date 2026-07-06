<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use LogsActivity;

    protected $table = 'notifications';
    protected $fillable = [
        'title',
        'message',
        'group',
        'type',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the drivers that received this notification
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Notification_Drivers::class, 'notification_id');
    }

    /**
     * Get the customers that received this notification
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Notification_Customers::class, 'notification_id');
    }

    /**
     * Get the users that received this notification
     */
    public function users(): HasMany
    {
        return $this->hasMany(Notification_Users::class, 'notification_id');
    }

    /**
     * Scope for notifications sent to drivers
     */
    public function scopeForDrivers($query)
    {
        return $query->where('group', 'drivers');
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
}
