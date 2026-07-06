<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Blockage extends Model
{
    use LogsActivity;

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
