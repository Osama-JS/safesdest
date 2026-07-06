<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Customer_Clearance_Pricing_Template extends Model
{
    use LogsActivity;

  protected $table = "customer_clearance_pricing_template";
  protected $fillable = [
    'customer_id',
    'clearance_pricing_template_id'
  ];
  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }
  public function clearancePricingTemplate()
  {
    return $this->belongsTo(Clearance_Pricing_Template::class, 'clearance_pricing_template_id');
  }
}
