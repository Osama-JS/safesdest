<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification_Customers extends Model
{
  protected $table = 'notification_customers';
  protected $fillable = [
    'notification_id',
    'customer_id',
    'status',
  ];
  public function notification()
  {
    return $this->belongsTo(Notification::class, 'notification_id');
  }
  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }
}
