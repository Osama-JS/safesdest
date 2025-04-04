<?php

namespace App\Models\mongo;

use Illuminate\Database\Eloquent\Model;

class mUsers extends Model
{
  protected $connection = 'pgsql';
  protected $collection = 'users';
  protected $guarded = [];
}
