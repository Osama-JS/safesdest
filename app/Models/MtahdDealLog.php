<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Task;

class MtahdDealLog extends Model
{
    use HasFactory;

    protected $table = 'mtahd_deal_logs';

    protected $fillable = [
        'task_id',
        'deal_number',
        'deal_id',
        'action',
        'status',
        'amount',
        'currency',
        'buyer_info',
        'seller_info',
        'request_payload',
        'response_payload',
        'http_status',
        'error_message',
        'ip_address',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to the Task
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Relationship to the User who performed or triggered the action
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope: Filter by deal number
     */
    public function scopeForDeal($query, $dealNumber)
    {
        return $query->where('deal_number', $dealNumber);
    }

    /**
     * Scope: Filter by task ID
     */
    public function scopeForTask($query, $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * Scope: Filter by action type
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get Arabic label for action
     */
    public function getActionLabelAttribute()
    {
        return match ($this->action) {
            'create_customer' => 'إنشاء عميل في أمن',
            'create_deal'     => 'إنشاء صفقة جديدة',
            'add_parties'     => 'إضافة أطراف الصفقة',
            'submit_deal'     => 'اعتماد الصفقة (طلب دفع)',
            'release_funds'   => 'تحرير الضمان المالي',
            'cancel_deal'     => 'إلغاء الصفقة واسترداد الضمان',
            'deliver_deal'    => 'تأكيد التسليم',
            'get_deal'        => 'استعلام عن الصفقة',
            'webhook_received'=> 'إشعار لحظي (Webhook)',
            default           => $this->action,
        };
    }

    /**
     * Get badge color class for status
     */
    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'success' => 'badge bg-label-success',
            'failed'  => 'badge bg-label-danger',
            'pending' => 'badge bg-label-warning',
            'info'    => 'badge bg-label-info',
            default   => 'badge bg-label-secondary',
        };
    }
}
