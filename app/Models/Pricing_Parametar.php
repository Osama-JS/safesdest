<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing_Parametar extends Model
{
  protected $table = 'pricing_parametars';
  protected $fillable = [
    'from_val',
    'to_val',
    'price',
    'pricing_id'
  ];

  public function pricing()
  {
    return $this->belongsTo(Pricing::class, 'pricing_id');
  }
}
