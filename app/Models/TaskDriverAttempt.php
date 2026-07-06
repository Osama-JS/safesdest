<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class TaskDriverAttempt extends Model
{
    use LogsActivity;

  protected $table = 'task_driver_attempts';
  protected $fillable = [
    'task_id',
    'driver_id',
    'status',
    'attempted_at'
  ];
}
