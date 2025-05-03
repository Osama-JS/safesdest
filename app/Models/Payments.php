<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
  protected $table = 'payments';
  protected $fillable = [
    'task_id',
    'customer_id',
    'amount',
    'payment_method',
    'status',
    'transaction_reference',
    'gateway_name',
    'gateway_response',
  ];

  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }

  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }
}
