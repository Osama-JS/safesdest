<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Guest extends Authenticatable
{
    use LogsActivity;

  protected $table = 'guest';
}
