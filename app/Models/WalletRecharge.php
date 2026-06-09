<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletRecharge extends Model
{
    use HasFactory;

    protected $table = 'wallet_recharges';

    protected $fillable = [
        'customer_id',
        'wallet_id',
        'amount',
        'payment_method',
        'checkout_id',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
