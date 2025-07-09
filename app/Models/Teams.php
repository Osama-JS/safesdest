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
    'location_update_interval',
    'is_public'
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
    return $this->hasMany(Task::class,  'team_id');
  }

  public function wallets()
  {
    return $this->hasManyThrough(Task::class, Driver::class, 'team_id', 'driver_id');
  }

  /**
   * Scope للحصول على الفرق العامة فقط
   */
  public function scopePublic($query)
  {
    return $query->where('is_public', true);
  }

  /**
   * Scope للحصول على الفرق الخاصة فقط
   */
  public function scopePrivate($query)
  {
    return $query->where('is_public', false);
  }

  public function teamWallet()
  {
    return $this->hasOne(Team_Wallet::class, 'team_id');
  }

  public function teamWalletTransactions()
  {
    return $this->teamWallet()->with('transactions');
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

  public function geofences()
  {
    return $this->hasMany(Geofence_Team::class, 'team_id');
  }
}
