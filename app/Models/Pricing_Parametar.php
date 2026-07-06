<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Pricing_Parametar extends Model
{
    use LogsActivity;

  protected $table = 'pricing_parametars';
  protected $fillable = [
    'from_val',
    'to_val',
    'price',
    'driver_price',
    'pricing_id'
  ];


  public function pricing()
  {
    return $this->belongsTo(Pricing::class, 'pricing_id');
  }
}
