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
      'is_public',
      'bank_name',
      'account_number',
      'iban_number',
      'bic_code',
      'beneficiary_name',
      'bank_address1',
      'bank_address2',
      'bank_city',
      'bank_country'
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
        return $this->hasMany(Task::class, 'team_id');
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

    /**
     * Get the team leader (first user in user_has_teams)
     */
    public function getTeamLeaderAttribute()
    {
        $firstUserTeam = $this->users()->orderBy('id', 'asc')->first();
        return $firstUserTeam ? $firstUserTeam->user : null;
    }

    /**
     * Check if team has a leader
     */
    public function hasTeamLeader()
    {
        return $this->users()->exists();
    }

    /**
     * Get team leader with bank details validation
     */
    public function getTeamLeaderWithBankDetails()
    {
        $teamLeader = $this->teamLeader;

        if (!$teamLeader) {
            return null;
        }

        // Check if bank details are complete
        if (!$teamLeader->bank_name || !$teamLeader->account_number || !$teamLeader->iban_number) {
            return null;
        }

        return $teamLeader;
    }
}
