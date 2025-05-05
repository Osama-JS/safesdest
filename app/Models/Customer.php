<?php

namespace App\Models;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
  use HasRoles;
  use HasFactory;
  use SoftDeletes;

  protected $guard_name = 'customer';
  protected $table = 'customers';
  protected $fillable = [
    'name',
    'email',
    'password',
    'phone',
    'phone_code',
    'image',
    'status',
    'company_name',
    'company_address',
    'additional_data',
    'form_template_id',
    'role_id',
    'team_id',
  ];

  protected $casts = [
    'additional_data' => 'array',
  ];

  protected $dates = ['deleted_at'];


  public function form_template()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }

  public function role()
  {
    return $this->belongsTo(Role::class, 'role_id');
  }

  public function tags()
  {
    return $this->hasMany(Tag_Customers::class, 'customer_id');
  }

  public function points()
  {
    return $this->hasMany(Point::class, 'customer_id');
  }

  public function tasks()
  {
    return $this->hasMany(Task::class, 'customer_id');
  }

  public function users()
  {
    return $this->belongsToMany(User::class);
  }
}
