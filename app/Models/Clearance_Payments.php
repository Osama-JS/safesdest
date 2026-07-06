<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Clearance_Payments extends Model
{
    use LogsActivity;

  protected $table = 'custom_clearance_payments';
  protected $fillable = [
    'amount',
    'payment_method',
    'status',
    'transaction_reference',
    'gateway_name',
    'gateway_response',
    'customs_clearance_id',
    'customer_id',
  ];

  public function customsClearance()
  {
    return $this->belongsTo(Customs_Clearance::class, 'customs_clearance_id');
  }

  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }
}
