<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task_Ad extends Model
{
  protected $table = 'tasks_ads';
  protected $fillable = [
    'description',
    'status',
    'highest_price',
    'lowest_price',
    'task_id',
  ];

  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }
}
