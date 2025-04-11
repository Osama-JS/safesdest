<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag_Drivers extends Model
{
  protected $table = 'tags_drivers';
  protected $fillable = [
    'tag_id',
    'driver_id',
  ];
  public function tag()
  {
    return $this->belongsTo(Tag::class, 'tag_id');
  }
  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
}
