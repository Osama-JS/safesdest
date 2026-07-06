<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Pricing_Method extends Model
{
    use LogsActivity;

  protected $table = 'pricing_methods';
  protected $fillable = [
    'name',
    'description',
    'status',
    'type'
  ];


  public function pricing()
  {
    return $this->hasMany(Pricing::class, "pricing_method_id");
  }
}
