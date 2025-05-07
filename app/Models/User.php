<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Http\Controllers\pages\UserTeams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
  use HasApiTokens;

  use HasFactory;
  use HasProfilePhoto;
  use Notifiable;
  use TwoFactorAuthenticatable;
  use HasRoles;
  use SoftDeletes;


  protected $fillable = [
    'name',
    'email',
    'password',
    'phone',
    'phone_code',
    'status',  // ['active', 'inactive', 'deleted', 'pending']
    'reset_password',
    'last_login',
    'additional_data',
    'form_template_id',
    'role_id'
  ];

  protected $casts = [
    'additional_data' => 'array',
  ];

  protected $dates = ['deleted_at'];

  protected $hidden = [
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
  ];


  protected $appends = [
    'profile_photo_url',
  ];


  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  public function role()
  {
    return $this->belongsTo(Role::class, 'role_id');
  }

  public function teams()
  {
    return $this->hasMany(User_Teams::class, 'user_id');
  }

  protected $guard_name = 'web';

  public function customers()
  {
    return $this->belongsToMany(Customer::class);
  }

  public function transactions()
  {
    return $this->morphMany(Transaction::class, 'payable');
  }
}
