<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing_Method extends Model
{
  protected $table = 'pricing_methods';
  protected $fillable = [
    'name',
    'description',
    'status',
    'distance_calculation'
  ];
}
