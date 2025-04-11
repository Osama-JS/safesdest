<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task_Offire extends Model
{
  protected $table = 'tasks_offers';
  protected $fillable = [
    'task_id',
    'driver_id',
    'accepted',
    'price',
    'description'
  ];

  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }
  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
}
