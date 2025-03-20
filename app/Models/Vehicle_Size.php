<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle_Size extends Model
{
  protected $table = 'vehicle_sizes';
  protected $fillable = [
    'name',
    'vehicle_type_id'
  ];

  public function type()
  {
    return $this->belongsTo(Vehicle_Type::class, 'vehicle_type_id');
  }

  public function drivers()
  {
    return $this->hasMany(Driver::class, 'vehicle_size_id');
  }
}
