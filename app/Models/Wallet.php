<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
  protected $table = 'wallets';
  protected $fillable = [
    'debt_ceiling',
    'user_type',
    'customer_id',
    'driver_id',
    'status',
    'preview'
  ];


  public function getOwnerAttribute()
  {
    if ($this->user_type === 'customer') {
      return $this->customer;
    }

    if ($this->user_type === 'driver') {
      return $this->driver;
    }

    return null;
  }

  public function getCreditAttribute()
  {
    $credit = $this->transactions()
      ->where('transaction_type', 'credit')
      ->sum('amount');

    return $credit;
  }

  public function getDebitAttribute()
  {

    $debit = $this->transactions()
      ->where('transaction_type', 'debit')
      ->sum('amount');

    return  $debit;
  }

  public function getBalanceAttribute()
  {
    $credit = $this->transactions()
      ->where('transaction_type', 'credit')
      ->sum('amount');

    $debit = $this->transactions()
      ->where('transaction_type', 'debit')
      ->sum('amount');

    return $credit - $debit;
  }

  public function getLastTransactionAttribute()
  {
    $last = $this->transactions()
      ->latest('created_at')
      ->value('created_at');

    return $last ? $last->format('Y-m-d H:i') : null;
  }



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
