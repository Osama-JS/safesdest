<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
  protected $table = 'vehicles';
  protected $fillable = [
    'name',
    'en_name'
  ];

  public function types()
  {
    return $this->hasMany(Vehicle_Type::class, 'vehicle_id');
  }
}
