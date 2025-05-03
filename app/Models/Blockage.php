<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blockage extends Model
{
  protected $table = 'blockages';
  protected $fillable = [
    'type',
    'coordinates',
    'description',
    'status'
  ];

  protected $casts = [
    'coordinates' => 'array',
    'status' => 'boolean'
  ];
}
