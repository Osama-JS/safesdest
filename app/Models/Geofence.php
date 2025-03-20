<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geofence extends Model
{
  protected $table = 'geofences';
  protected $fillable = [
    'name',
    'description',
    'coordinates',
  ];
}
