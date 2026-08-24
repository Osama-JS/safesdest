<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestorCapitalWithdrawal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'investor_capital_withdrawals';

    protected $fillable = [
        'user_id',
        'investor_wallet_id',
        'amount',
        'status',
        'agreed_terms',
        'investor_notes',
        'admin_notes',
        'request_date',
        'scheduled_disbursement_date',
        'processed_by',
        'processed_at',
        'disbursed_at',
        'investor_wallet_transaction_id',
    ];

    protected $casts = [
        'amount'                      => 'float',
        'agreed_terms'                => 'boolean',
        'request_date'                => 'datetime',
        'scheduled_disbursement_date' => 'datetime',
        'processed_at'                => 'datetime',
        'disbursed_at'                => 'datetime',
    ];

    /**
     * Get the investor (user).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the investment wallet.
     */
    public function wallet()
    {
        return $this->belongsTo(InvestorWallet::class, 'investor_wallet_id');
    }

    /**
     * Get the admin who processed the request.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the wallet transaction linked to this withdrawal.
     */
    public function transaction()
    {
        return $this->belongsTo(InvestorWalletTransaction::class, 'investor_wallet_transaction_id');
    }

    /**
     * Check if the disbursement date has arrived.
     */
    public function getIsDueForDisbursementAttribute(): bool
    {
        if ($this->status !== 'approved' || !$this->scheduled_disbursement_date) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo($this->scheduled_disbursement_date);
    }

    /**
     * Get remaining days until scheduled disbursement.
     */
    public function getRemainingDaysAttribute(): int
    {
        if (!$this->scheduled_disbursement_date) {
            return 0;
        }

        if (Carbon::now()->greaterThanOrEqualTo($this->scheduled_disbursement_date)) {
            return 0;
        }

        return (int) Carbon::now()->diffInDays($this->scheduled_disbursement_date, false);
    }

    /**
     * Get remaining months and days as a formatted string.
     */
    public function getRemainingDurationHumanAttribute(): string
    {
        if (!$this->scheduled_disbursement_date) {
            return '—';
        }

        $now = Carbon::now();
        if ($now->greaterThanOrEqualTo($this->scheduled_disbursement_date)) {
            return __('Due for disbursement');
        }

        $diff = $now->diff($this->scheduled_disbursement_date);
        $parts = [];
        if ($diff->m > 0) {
            $parts[] = "{$diff->m} " . __('month');
        }
        if ($diff->d > 0) {
            $parts[] = "{$diff->d} " . __('day');
        }

        return empty($parts) ? __('Less than a day') : implode(' ' . __('and') . ' ', $parts);
    }

    /**
     * Get localized status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'   => '<span class="badge bg-label-warning"><i class="ti ti-clock me-1"></i>' . __('Under Review') . '</span>',
            'approved'  => '<span class="badge bg-label-info"><i class="ti ti-calendar-time me-1"></i>' . __('Approved (Scheduled for Disbursement)') . '</span>',
            'completed' => '<span class="badge bg-label-success"><i class="ti ti-check me-1"></i>' . __('Disbursed & Debited') . '</span>',
            'rejected'  => '<span class="badge bg-label-danger"><i class="ti ti-x me-1"></i>' . __('Rejected') . '</span>',
            'cancelled' => '<span class="badge bg-label-secondary"><i class="ti ti-ban me-1"></i>' . __('Cancelled') . '</span>',
            default     => '<span class="badge bg-label-primary">' . e($this->status) . '</span>',
        };
    }
}
