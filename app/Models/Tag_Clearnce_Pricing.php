<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Tag_Clearnce_Pricing extends Model
{
    use LogsActivity;

  protected $table = 'tags_clearance_pricing';
  protected $fillable = [
    'tag_id',
    'clearance_pricing_template_id',
  ];

  public function tag()
  {
    return $this->belongsTo(Tag::class, 'tag_id');
  }

  public function clearancePricingTemplate()
  {
    return $this->belongsTo(Clearance_Pricing_Template::class, 'clearance_pricing_template_id');
  }
}
