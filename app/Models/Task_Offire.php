<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Task_Offire extends Model
{
    use LogsActivity;

  protected $table = 'tasks_offers';
  protected $fillable = [
    'task_ad_id',
    'driver_id',
    'accepted',
    'price',
    'description'
  ];

  public function ad()
  {
    return $this->belongsTo(Task_Ad::class, 'task_ad_id');
  }
  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
}
