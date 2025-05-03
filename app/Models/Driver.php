<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Driver extends Authenticatable
{
  use HasRoles;
  use SoftDeletes;
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
  protected $casts = [
    'additional_data' => 'array',
  ];

  protected $dates = ['deleted_at'];

  public function team()
  {
    return $this->belongsTo(Teams::class, 'team_id');
  }
  public function tags()
  {
    return $this->hasMany(Tag_Drivers::class, 'driver_id');
  }

  public function vehicle_size()
  {
    return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
  }
}
