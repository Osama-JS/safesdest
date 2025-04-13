<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model;

class Geofence extends Model
{
  protected $table = 'geofences';
  protected $fillable = [
    'name',
    'description',
    'coordinates',
  ];

  protected $appends = ['coordinates_wkt'];

  public function getCoordinatesWktAttribute()
  {
    return DB::selectOne("SELECT ST_AsText(?) AS coordinates", [$this->coordinates])->coordinates;
  }

  public function teams()
  {
    return $this->hasMany(Geofence_Team::class, 'geofence_id');
  }
}
