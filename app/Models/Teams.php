<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teams extends Model
{
  protected $table = 'teams';
  protected $fillable = [
    'name',
    'address',
    'note',
    'team_commission_type',
    'team_commission_value',
    'location_update_interval'
  ];

  public function users()
  {
    return $this->hasMany(User_Teams::class, 'team_id')->with('user');
  }

  public function drivers()
  {
    return $this->hasMany(Driver::class, 'team_id');
  }

  public function tasks()
  {
    return $this->hasManyThrough(Task::class, Driver::class, 'team_id', 'driver_id');
  }

  public function wallets()
  {
    return $this->hasManyThrough(Task::class, Driver::class, 'team_id', 'driver_id');
  }

  public function walletTransactions()
  {
    return Wallet_Transaction::whereIn('wallet_id', function ($query) {
      $query->select('id')
        ->from('wallets')
        ->whereIn('driver_id', function ($query) {
          $query->select('id')
            ->from('drivers')
            ->where('team_id', $this->id);
        });
    });
  }
}
