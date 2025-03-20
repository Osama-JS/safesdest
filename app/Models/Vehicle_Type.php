<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle_Type extends Model
{
  protected $table = 'vehicle_types';
  protected $fillable = [
    'name',
    'en_name',
    'vehicle_id'
  ];

  public function vehicle()
  {
    return $this->belongsTo(Vehicle::class, 'vehicle_id');
  }

  public function sizes()
  {
    return $this->hasMany(Vehicle_Size::class, 'vehicle_type_id');
  }
}
