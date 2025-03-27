<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form_Template extends Model
{
  protected $table = 'form_templates';
  protected $fillable = ['name', 'description'];

  public function fields()
  {
    return $this->hasMany(Form_Field::class, 'form_template_id');
  }

  public function pricing_templates()
  {
    return $this->hasMany(Pricing_Template::class, 'form_template_id');
  }
}
