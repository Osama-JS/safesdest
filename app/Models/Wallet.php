<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
  protected $table = 'wallets';
  protected $fillable = [
    'balance',
    'user_type',
    'customer_id',
    'driver_id',
    'status',
    'preview'
  ];

  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }
  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
  public function transactions()
  {
    return $this->hasMany(Wallet_Transaction::class, 'wallet_id');
  }
}
