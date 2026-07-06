<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Email_Verifications extends Model
{
    use LogsActivity;

  protected $table = 'email_verifications';
  protected $fillable = [
    'verifiable_id',
    'verifiable_type',
    'token'
  ];
}
