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
  public function pricing_methods()
  {
    return $this->hasMany(Pricing::class, 'pricing_template_id');
  }
  public function customers()
  {
    return $this->belongsToMany(Customer::class, 'pricing_customer', 'pricing_template_id', 'customer_id');
  }
  public function tags()
  {
    return $this->belongsToMany(Tag::class, 'tags_pricing', 'pricing_template_id', 'tag_id');
  }

  public function sizes()
  {
    return $this->belongsToMany(Vehicle_Size::class, 'pricing_vehicle', 'pricing_template_id', 'vehicle_size_id');
  }


  public function fields()
  {
    return $this->hasMany(Pricing_Field::class, 'pricing_id');
  }

  public function geoFences()
  {
    return $this->hasMany(Pricing_GeoFence::class, 'pricing_template_id');
  }
}
