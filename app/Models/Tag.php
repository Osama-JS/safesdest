<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
  protected $table = 'tags';
  protected $fillable = [
    'name',
    'description',
    'slug',
  ];

  public function drivers()
  {
    return $this->hasMany(Tag_Drivers::class, 'tag_id');
  }
  public function customers()
  {
    return $this->hasMany(Tag_Customers::class, 'tag_id');
  }

  public function pricing()
  {
    return $this->hasMany(Tag_Pricing::class, 'tag_id');
  }
}
