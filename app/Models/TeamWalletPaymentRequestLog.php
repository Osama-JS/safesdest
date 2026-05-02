<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamWalletPaymentRequestLog extends Model
{
    protected $fillable = [
        'team_wallet_id',
        'user_id',
        'team_id',
        'team_leader_id',
        'amount',
        'payment_request_number',
        'payment_method',
        'transaction_id',
        'notes',
        'ip_address',
        'printed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'printed_at' => 'datetime',
    ];

    /**
     * Get the team wallet that owns the log
     */
    public function teamWallet()
    {
        return $this->belongsTo(Team_Wallet::class, 'team_wallet_id');
    }

    /**
     * Get the user who printed the request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team
     */
    public function team()
    {
        return $this->belongsTo(Teams::class, 'team_id');
    }

    /**
     * Get the team leader
     */
    public function teamLeader()
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    /**
     * Scope for filtering by team wallet
     */
    public function scopeForTeamWallet($query, $teamWalletId)
    {
        return $query->where('team_wallet_id', $teamWalletId);
    }

    /**
     * Scope for filtering by team
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ريال';
    }

    /**
     * Get formatted printed date
     */
    public function getFormattedPrintedAtAttribute()
    {
        return $this->printed_at->format('Y-m-d H:i:s');
    }
}
