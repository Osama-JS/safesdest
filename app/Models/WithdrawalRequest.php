<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawalRequest extends Model
{
    use LogsActivity;

    use HasFactory, SoftDeletes;

    protected $table = 'withdrawal_requests';

    protected $fillable = [
        'driver_id',
        'wallet_id',
        'amount_requested',
        'amount_paid',
        'status',
        'payment_method',
        'admin_notes',
        'receipt_image',
        'wallet_transaction_id',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount_requested' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the driver that owns the withdrawal request.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the wallet associated with the withdrawal request.
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the transaction associated with the withdrawal request.
     */
    public function transaction()
    {
        return $this->belongsTo(Wallet_Transaction::class, 'wallet_transaction_id');
    }

    /**
     * Get the user who processed the withdrawal request.
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed requests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
