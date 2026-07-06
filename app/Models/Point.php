<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use LogsActivity;

  protected $table = 'pointes';
  protected $fillable = [
    'name',
    'contact_name',
    'contact_phone',
    'address',
    'latitude',
    'longitude',
    'status',
    'customer_id'
  ];

  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }
}
