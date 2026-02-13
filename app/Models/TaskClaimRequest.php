<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskClaimRequest extends Model
{
    protected $table = 'task_claim_requests';

    protected $fillable = [
        'task_id',
        'driver_id',
        'status',
        'driver_note',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Reject all pending claim requests for a task.
     * Called when a task is assigned through any channel.
     */
    public static function rejectAllPending(int $taskId, ?string $reason = null, ?int $reviewerId = null): int
    {
        return self::where('task_id', $taskId)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'admin_note' => $reason ?? 'تم رفض الطلب تلقائياً لأن المهمة تم إسنادها',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);
    }
}
