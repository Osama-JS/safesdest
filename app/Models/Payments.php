<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use LogsActivity;

    protected $table = 'payments';

    protected $fillable = [
        'task_id',
        'customer_id',
        'owner_type',
        'owner_id',
        'amount',
        'payment_method',
        'purpose',
        'payment_paid',
        'reference_id',
        'status',
        'payment_token',
        'transaction_reference',
        'gateway_name',
        'gateway_response',
        'gateway_code',
        'gateway_msg',
        'gateway_reference',
        'description',
        'receipt_image',
        'receipt_number',
        'cancellation_reason',
        'canceled_at',
        'completed_at',
        'processed_at',
        'expires_at',
        'return_url',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'processed_at' => 'datetime',
        'canceled_at'  => 'datetime',
        'expires_at'   => 'datetime',
        'amount'       => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function owner()
    {
        if ($this->owner_type === 'customer') {
            return $this->belongsTo(Customer::class, 'owner_id');
        }
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    /**
     * Check if the payment link has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if payment is in a terminal state.
     */
    public function isFinished(): bool
    {
        return in_array($this->status, ['paid', 'failed', 'canceled', 'refunded']);
    }
}
