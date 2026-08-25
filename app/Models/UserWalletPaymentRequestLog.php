<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWalletPaymentRequestLog extends Model
{
    use LogsActivity;
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_wallet_payment_request_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_wallet_id',
        'user_id',
        'printed_by',
        'amount',
        'payment_request_number',
        'payment_method',
        'bank_name',
        'account_number',
        'iban_number',
        'other_payment_method',
        'notes',
        'ip_address',
        'printed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'printed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user wallet that owns the payment request log.
     */
    public function userWallet(): BelongsTo
    {
        return $this->belongsTo(UserWallet::class, 'user_wallet_id');
    }

    /**
     * Get the user (beneficiary) who owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin user who printed the request.
     */
    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    /**
     * Scope a query to only include logs for a specific wallet.
     */
    public function scopeForWallet($query, $walletId)
    {
        return $query->where('user_wallet_id', $walletId);
    }

    /**
     * Scope a query to only include logs for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
