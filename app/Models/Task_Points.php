<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task_Points extends Model
{
  protected $table = 'tasks_points';
  protected $fillable = [
    'task_id',
    'type',
    'sequence',
    'contact_name',
    'contact_phone',
    'contact_emil',
    'address',
    'latitude',
    'longitude',
    'scheduled_time',
    'note',
    'image'
  ];

  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }
}
