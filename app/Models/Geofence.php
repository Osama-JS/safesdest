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

  public function containsPoint($latitude, $longitude): bool
  {
    // هنا افتراض أنك تستخدم PostGIS وبيانات polygon مخزنة في العمود 'coordinates'

    $pointWKT = "POINT($longitude $latitude)";

    // استعلام بسيط للتحقق داخل قاعدة البيانات مباشرة (مباشر على هذا الجيوفينس)
    $result = DB::table('geofences')
      ->where('id', $this->id)
      ->whereRaw("ST_Contains(coordinates, ST_GeomFromText(? , 4326))", [$pointWKT])
      ->exists();

    return $result;
  }
}
