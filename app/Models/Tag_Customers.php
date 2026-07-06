<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Tag_Customers extends Model
{
    use LogsActivity;

  protected $table = 'tags_customers';
  protected $fillable = [
    'tag_id',
    'customer_id',
  ];

  public function tag()
  {
    return $this->belongsTo(Tag::class, 'tag_id');
  }

  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }
}
