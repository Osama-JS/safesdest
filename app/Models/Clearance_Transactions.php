<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clearance_Transactions extends Model
{
  protected $table = 'custom_clearance_transactions';
  protected $fillable = [
    'amount',
    'type',
    'note',
    'status',
    'reference_id',
    'checkout_id',
    'payment_type',
    'receipt_image',
    'receipt_number',
    'user_check',
    'user_ip',
    'checkout_at',
  ];

  public function payable()
  {
    return $this->morphTo();
  }
  public function  user()
  {
    return $this->belongsTo(User::class, 'user_check');
  }
}
