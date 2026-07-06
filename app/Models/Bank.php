<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use LogsActivity;

    use HasFactory;

    protected $fillable = ['name', 'code', 'is_active'];
}
