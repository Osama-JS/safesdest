<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Team_Wallet_Transaction extends Model
{
    use LogsActivity;

  protected $table = 'team_wallet_transactions';
  protected $fillable = [
    'amount',
    'transaction_type',
    'description',
    'image',
    'team_wallet_id',
    'task_id',
    'user_id',
    'maturity_time',
    'sequence',
  ];

  protected static function booted()
  {
    static::creating(function ($transaction) {
      $last = self::where('team_wallet_id', $transaction->team_wallet_id)
        ->lockForUpdate()
        ->orderByDesc('sequence')
        ->first();

      $transaction->sequence = optional($last)->sequence + 1 ?? 1;
    });
  }

  public function teamWallet()
  {
    return $this->belongsTo(Team_Wallet::class, 'team_wallet_id');
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
