<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailNotificationLog extends Model
{
    use HasFactory;

    protected $table = 'email_notifications_log';

    protected $fillable = [
        'to_email',
        'from_email',
        'subject',
        'content',
        'template',
        'type',
        'priority',
        'status',
        'error_message',
        'attempts',
        'sent_at',
        'additional_data',
        'has_attachments'
    ];

    protected $casts = [
        'additional_data' => 'array',
        'sent_at' => 'datetime',
        'has_attachments' => 'boolean'
    ];

    /**
     * Scope for sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Scope for notifications by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for notifications to specific email
     */
    public function scopeToEmail($query, $email)
    {
        return $query->where('to_email', $email);
    }

    /**
     * Get notifications sent today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Get notifications sent this week
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Get notifications sent this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'attempts' => $this->attempts + 1
        ]);
    }

    /**
     * Increment attempts
     */
    public function incrementAttempts()
    {
        $this->increment('attempts');
    }

    /**
     * Get formatted sent date
     */
    public function getFormattedSentDateAttribute()
    {
        return $this->sent_at ? $this->sent_at->format('Y-m-d H:i:s') : 'لم يتم الإرسال';
    }

    /**
     * Get status in Arabic
     */
    public function getStatusInArabicAttribute()
    {
        $statuses = [
            'pending' => 'في الانتظار',
            'sent' => 'تم الإرسال',
            'failed' => 'فشل الإرسال'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get priority in Arabic
     */
    public function getPriorityInArabicAttribute()
    {
        $priorities = [
            'high' => 'عالية',
            'normal' => 'عادية',
            'low' => 'منخفضة'
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }
}
