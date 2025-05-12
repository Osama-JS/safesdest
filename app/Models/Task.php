<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
  protected $table = 'tasks';
  protected $fillable = [
    'status',
    'pricing_type',
    'total_price',
    'commission',
    'payment_method',
    'payment_status',
    'payment_paid',
    'payment_pending_amount',
    'payment_id',
    'additional_data',
    'distribution_attempts',
    'last_attempt_at',
    'pending_driver_id',
    'pricing_history',
    'closed',
    'order_id',
    'customer_id',
    'driver_id',
    'user_id',
    'form_template_id',
    'pricing_id',
    'vehicle_size_id'
  ];

  protected $casts = [
    'additional_data' => 'array',
    'pricing_history' => 'array',
    'last_attempt_at' => 'datetime',

  ];

  protected $appends = ['owner'];

  public function getOwnerAttribute()
  {
    return $this->customer_id ? 'customer' : 'admin';
  }

  public function order()
  {
    return $this->belongsTo(Order::class, 'order_id');
  }
  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
  public function formTemplate()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }
  public function pricingTemplate()
  {
    return $this->belongsTo(Pricing_Template::class, 'pricing_id');
  }

  public function payments()
  {
    return $this->hasMany(Payments::class, 'task_id');
  }

  public function point()
  {
    return $this->hasOne(Task_Points::class, 'task_id');
  }

  public function points()
  {
    return $this->hasMany(Task_Points::class, 'task_id');
  }

  public function history()
  {
    return $this->hasMany(Task_History::class, 'task_id');
  }

  public function pickup()
  {
    return $this->hasOne(Task_Points::class, 'task_id')->where('type', 'pickup');
  }

  public function delivery()
  {
    return $this->hasOne(Task_Points::class, 'task_id')->where('type', 'delivery');
  }

  public function ad()
  {
    return $this->hasOne(Task_Ad::class, 'task_id');
  }

  public function vehicle_size()
  {
    return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
  }
}
