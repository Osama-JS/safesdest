<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use LogsActivity;

  protected $fillable = [
    'status',
    'customer_id',
    'user_id'
  ];

  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function tasks()
  {
    return $this->hasMany(Task::class, 'order_id');
  }
}
