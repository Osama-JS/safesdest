<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Geofence_Team extends Model
{
    use LogsActivity;

  protected $table = 'geofences_has_team';
  protected $fillable = ['geofence_id', 'team_id'];

  public $timestamps = false;

  public function geofence()
  {
    return $this->belongsTo(Geofence::class, 'geofence_id');
  }

  public function team()
  {
    return $this->belongsTo(Teams::class, 'team_id');
  }
}
