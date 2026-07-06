<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Geofence_Driver extends Model
{
    use LogsActivity;

  protected $table = 'geofences_has_drivers';
  protected $fillable = ['geofence_id', 'driver_id'];

  public function geofence()
  {
    return $this->belongsTo(Geofence::class, 'geofence_id');
  }

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
}
