<?php

namespace App\Models\mongo;

use Illuminate\Database\Eloquent\Model;

class mUsers extends Model
{
  protected $connection = 'mongodb';
  protected $collection = 'users';
  protected $fillable = ['fields'];
}
