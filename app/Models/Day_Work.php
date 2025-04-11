<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Day_Work extends Model
{
  protected $table = 'day_work';
  protected $fillable = [
    'driver_id',
    'day_id',
    'start_time',
    'end_time',
    'day_off',
  ];

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
  public function day()
  {
    return $this->belongsTo(Day::class, 'day_id');
  }
}
