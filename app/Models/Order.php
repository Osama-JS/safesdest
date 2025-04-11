<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
  protected $fillable = [
    'status',
    'customer_id',
  ];
  protected $casts = [
    'status' => 'string',
  ];
  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }
  public function tasks()
  {
    return $this->hasMany(Task::class, 'order_id');
  }
}
