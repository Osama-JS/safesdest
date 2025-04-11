<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification_Drivers extends Model
{
  protected $table = 'notification_drivers';
  protected $fillable = [
    'notification_id',
    'driver_id',
    'status',
  ];
  public function notification()
  {
    return $this->belongsTo(Notification::class, 'notification_id');
  }
  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
}
