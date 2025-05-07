<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
  protected $fillable = [
    'amount',
    'status',
    'type',
    'note',
    'reference_id',
    'checkout_id'
  ];

  public function payable()
  {
    return $this->morphTo();
  }
}
