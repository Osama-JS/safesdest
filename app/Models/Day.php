<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
  protected $table = 'days';
  protected $fillable = [
    'name',
  ];

  public function dayWork()
  {
    return $this->hasMany(Day_Work::class, 'day_id');
  }
}
