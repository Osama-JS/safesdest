<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    use LogsActivity;

  protected $table = 'pricing';
  protected $fillable = [
    'status',
    'pricing_template_id',
    'pricing_method_id'
  ];

  public function template()
  {
    return $this->belongsTo(Pricing_Template::class, 'pricing_template_id');
  }

  public function method()
  {
    return $this->belongsTo(Pricing_Method::class, 'pricing_method_id');
  }

  public function parametars()
  {
    return $this->hasMany(Pricing_Parametar::class, 'pricing_id');
  }
}
