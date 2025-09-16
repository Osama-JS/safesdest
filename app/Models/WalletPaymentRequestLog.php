<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPaymentRequestLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_payment_request_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'user_id',
        'driver_id',
        'amount',
        'payment_request_number',
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
     * Get the wallet that owns the payment request log.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the user who printed the payment request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the driver who owns the wallet.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Scope to get logs for a specific wallet.
     */
    public function scopeForWallet($query, $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }

    /**
     * Scope to get logs for a specific driver.
     */
    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Scope to get logs within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('printed_at', [$startDate, $endDate]);
    }

    /**
     * Scope to order by printed date (newest first).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('printed_at', 'desc');
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ريال';
    }

    /**
     * Get formatted printed date in Arabic.
     */
    public function getFormattedPrintedAtAttribute(): string
    {
        return $this->printed_at->format('d/m/Y H:i');
    }

    /**
     * Get the printer's name.
     */
    public function getPrinterNameAttribute(): string
    {
        return $this->user->name ?? 'غير محدد';
    }

    /**
     * Get the driver's name.
     */
    public function getDriverNameAttribute(): string
    {
        return $this->driver->name ?? 'غير محدد';
    }
}
