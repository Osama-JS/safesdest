<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email_Verification_Resends extends Model
{
  protected $table = "email_verification_resends";
  protected $fillable = [
    'email',
    'ip_address',
    'resend_count',
    'last_sent_at'
  ];
}
