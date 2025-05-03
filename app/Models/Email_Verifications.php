<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email_Verifications extends Model
{
  protected $table = 'email_verifications';
  protected $fillable = [
    'verifiable_id',
    'verifiable_type',
    'token'
  ];
}
