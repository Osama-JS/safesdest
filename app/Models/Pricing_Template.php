<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing_Template extends Model
{
  protected $table = 'pricing_templates';
  protected $fillable = [
    'name',
    'decimal_places',
    'base_fare',
    'base_waiting_time',
    'waiting_fare',
    'base_distance',
    'distance_fare',
    'discount_percentage',
    'vat_commission',
    'service_tax_commission',
    'all_customer',
    'form_template_id'
  ];

  public function form_template()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }
}
