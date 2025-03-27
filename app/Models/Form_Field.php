<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form_Field extends Model
{
  protected $table = 'form_fields';
  protected $fillable = [
    'form_template_id',
    'name',
    'label',
    'type',
    'required',
    'value',
    'driver_can',
    'customer_can'
  ];

  public function form_template()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }
}
