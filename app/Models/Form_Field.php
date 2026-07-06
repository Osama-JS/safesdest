<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Form_Field extends Model
{
    use LogsActivity;

  protected $table = 'form_fields';
  protected $fillable = [
    'form_template_id',
    'name',
    'label',
    'type',
    'required',
    'value',
    'ocr_prompt',
    'is_auto_filled',
    'driver_can',
    'customer_can',
    'order'
  ];

  public function form_template()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }
}
