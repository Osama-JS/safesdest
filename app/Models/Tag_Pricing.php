<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag_Pricing extends Model
{
  protected $table = 'tags_pricing';
  protected $fillable = [
    'tag_id',
    'pricing_template_id',

  ];
  public function tag()
  {
    return $this->belongsTo(Tag::class, 'tag_id');
  }
  public function pricingTemplate()
  {
    return $this->belongsTo(Pricing_Template::class, 'pricing_template_id');
  }
}
