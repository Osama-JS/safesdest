<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HyperpayPayout extends Model
{
    use LogsActivity;

    use HasFactory;

    protected $fillable = [
        'reference_id',
        'payout_id',
        'bulk_id',
        'wallet_id',
        'driver_id',
        'amount',
        'transaction_details',
        'status',
        'failure_reason',
        'webhook_payload',
        'payout_type', // e.g. 'MT' (Manual), 'WP' (Wallet Payment), 'WD' (Withdrawal)
        'source_withdrawal_id',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'transaction_details' => 'array',
        'webhook_payload' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function withdrawal()
    {
        return $this->belongsTo(WithdrawalRequest::class, 'source_withdrawal_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function getPayoutTypeNameAttribute(): string
    {
        return match ($this->payout_type) {
            'MT' => __('حركة يدوية (سحب/خصم)'),
            'WP' => __('تسوية مستحقات'),
            'WD' => __('طلب سحب رصيد'),
            'UWP' => __('سحب عمولات مستخدم'),
            'INV' => __('سحب أرباح مستثمر'),
            'TWM', 'TWP' => __('محفظة الفريق'),
            default => $this->payout_type ?: __('غير محدد'),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending_approval' => '<span class="badge bg-label-warning"><i class="ti ti-clock me-1"></i>' . __('بانتظار المصادقة') . '</span>',
            'pending', 'processing' => '<span class="badge bg-label-info"><i class="ti ti-loader me-1"></i>' . __('قيد المعالجة بالبنك') . '</span>',
            'completed' => '<span class="badge bg-label-success"><i class="ti ti-check me-1"></i>' . __('مكتمل بنجاح') . '</span>',
            'rejected' => '<span class="badge bg-label-danger"><i class="ti ti-x me-1"></i>' . __('مرفوض') . '</span>',
            'failed' => '<span class="badge bg-label-secondary"><i class="ti ti-alert-triangle me-1"></i>' . __('فشل التحويل') . '</span>',
            default => '<span class="badge bg-label-dark">' . e($this->status) . '</span>',
        };
    }
}
