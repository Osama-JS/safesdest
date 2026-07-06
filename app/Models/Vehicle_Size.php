<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Vehicle_Size extends Model
{
    use LogsActivity;

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

  public function getVehicleNameAttribute(){
    return $this->type->vehicle->name . ' - ' . $this->type->name . ' - ' . $this->name;
  }


}
