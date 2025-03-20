<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class Permissions_Type extends Model
{
  protected $table = 'permissions_types';
  protected $fillable = [
    'name',
    'guard_name'
  ];

  public function permissions()
  {
    return $this->hasMany(Permission::class, 'type_id');
  }
}
