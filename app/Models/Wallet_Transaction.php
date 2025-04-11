<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet_Transaction extends Model
{
  protected $table = 'wallet_transactions';
  protected $fillable = [
    'user_id',
    'amount',
    'transaction_type',
    'wallet_id',
    'task_id',
  ];

  public function wallet()
  {
    return $this->belongsTo(Wallet::class, 'wallet_id');
  }
  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
