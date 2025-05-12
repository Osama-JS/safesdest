<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskDriverAttempt extends Model
{
  protected $table = 'task_driver_attempts';
  protected $fillable = [
    'task_id',
    'driver_id',
    'status',
    'attempted_at'
  ];
}
