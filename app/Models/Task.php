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
    'additional_data',
    'pricing_history',
    'order_id',
    'customer_id',
    'driver_id',
    'form_template_id',
    'pricing_id',
  ];

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
  public function formTemplate()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }
  public function pricingTemplate()
  {
    return $this->belongsTo(Pricing_Template::class, 'pricing_id');
  }
}
