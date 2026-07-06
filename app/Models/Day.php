<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    use LogsActivity;

  protected $table = 'days';
  protected $fillable = [
    'name',
  ];

  public function dayWork()
  {
    return $this->hasMany(Day_Work::class, 'day_id');
  }
}
