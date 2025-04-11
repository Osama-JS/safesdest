<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task_Points extends Model
{
  protected $table = 'task_points';
  protected $fillable = [
    'task_id',
    'type',
    'sequence',
    'contact_name',
    'contact_phone',
    'address',
    'latitude',
    'longitude',
    'scheduled_time',
  ];
  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }
}
