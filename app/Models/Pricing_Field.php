<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing_Field extends Model
{
  protected $table = 'pricing_fields';
  protected $fillable = [
    'value',
    'option',
    'type',
    'amount',
    'field_id',
    'pricing_id'
  ];

  public function form_field()
  {
    return $this->belongsTo(Form_Field::class, 'field_id');
  }
  public function pricing_template()
  {
    return $this->belongsTo(Pricing_Template::class, 'pricing_id');
  }
}
