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
        'source_withdrawal_id'
    ];

    protected $casts = [
        'transaction_details' => 'array',
        'webhook_payload' => 'array',
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
}
