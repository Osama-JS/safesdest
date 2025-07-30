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
    'description',
    'image',
    'status',
    'wallet_id',
    'team_id',
    'maturity_time',
    'sequence',
    'task_id',
    'team_id',
    'clearance_id',

  ];


  protected static function booted()
  {
    static::creating(function ($transaction) {
      $last = self::where('wallet_id', $transaction->wallet_id)
        ->lockForUpdate()
        ->orderByDesc('sequence')
        ->first();

      $transaction->sequence = optional($last)->sequence + 1 ?? 1;
    });
  }

  public function wallet()
  {
    return $this->belongsTo(Wallet::class, 'wallet_id');
  }
  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }

  public function clearance()
  {
    return $this->belongsTo(Customs_Clearance::class, 'clearance_id');
  }


  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
