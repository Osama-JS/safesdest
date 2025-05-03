<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task_History extends Model
{
  protected $table = 'task_histories';
  protected $fillable = [
    'action_type',
    'description',
    'file_path',
    'file_type',
    'task_id',
    'driver_id',
    'user_id'
  ];

  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
