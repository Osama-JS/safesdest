<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class Permissions_Type extends Model
{
    use LogsActivity;

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
