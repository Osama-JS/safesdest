<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Team_Wallet extends Model
{
    use LogsActivity;

  protected $table = 'team_wallet';
  protected $fillable = [
    'team_id',
  ];

  public function team()
  {
    return $this->belongsTo(Teams::class, 'team_id');
  }

  public function transactions()
  {
    return $this->hasMany(Team_Wallet_Transaction::class, 'team_wallet_id');
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
}
