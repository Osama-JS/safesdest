<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Driver extends Authenticatable
{
  use HasRoles;

  protected $guard_name = 'driver';

  protected $table = 'drivers';
  protected $fillable = [
    'name',
    'phone',
    'phone_code',
    'email',
    'image',
    'username',
    'password',
    'status',
    'address',
    'online',
    'longitude',
    'altitude',
    'commission_type',
    'commission_value',
    'location_update_interval',
    'additional_data',
    'form_template_id',
    'team_id',
    'vehicle_size_id',
    'role_id'
  ];

  public function team()
  {
    return $this->belongsTo(Teams::class, 'team_id');
  }
}
