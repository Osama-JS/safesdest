<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing_Customer extends Model
{
  protected $table = 'pricing_customer';
  protected $fillable = [
    'pricing_template_id',
    'customer_id',
  ];
  public function pricingTemplate()
  {
    return $this->belongsTo(Pricing_Template::class, 'pricing_template_id');
  }
  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }
}
