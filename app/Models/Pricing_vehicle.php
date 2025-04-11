<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing_vehicle extends Model
{
  protected $table = 'pricing_vehicle';
  protected $fillable = [
    'pricing_template_id',
    'vehicle_size_id',
  ];
  public function pricingTemplate()
  {
    return $this->belongsTo(Pricing_Template::class, 'pricing_template_id');
  }
  public function vehicle()
  {
    return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
  }
}
