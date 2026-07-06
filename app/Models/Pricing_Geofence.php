<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Pricing_Geofence extends Model
{
    use LogsActivity;

  protected $table = 'pricing_geofences';
  protected $fillable = [
    'type',
    'amount',
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
