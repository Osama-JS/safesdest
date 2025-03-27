<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class Customer extends Authenticatable
{
  use HasRoles;

  protected $guard_name = 'customer';
  protected $table = 'customers';
  protected $fillable = [
    'name',
    'email',
    'password',
    'phone',
    'image',
    'status',
    'company_name',
    'company_address',
    'additional_data',
    'form_template_id',
    'role_id',
    'team_id',
  ];

  public function form_template()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }

  public function role()
  {
    return $this->belongsTo(Role::class, 'role_id');
  }

  public function team()
  {
    return $this->belongsTo(Teams::class, 'team_id');
  }
}
