<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
  protected $fillable = [
    'amount',
    '1',
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
    'checkout_at'
  ];

  public function payable()
  {
    return $this->morphTo();
  }
}
