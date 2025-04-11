<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing_Geofence extends Model
{
  protected $table = 'pricing_geofences';
  protected $fillable = [
    'pricing_template_id',
    'geofence_id',
  ];

  public function pricingTemplate()
  {
    return $this->belongsTo(Pricing_Template::class, 'pricing_template_id');
  }

  public function geofence()
  {
    return $this->belongsTo(Geofence::class, 'geofence_id');
  }
}
