<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use LogsActivity;

    protected $table = 'products';
    protected $fillable = [
      'name',
      'code',
      'unit',
      'minimum_order',
      'increase',
      'price',
      'status',
      'image',
      'description',
      'contact_name',
      'contact_phone',
      'contact_email',
      'latitude',
      'longitude',
      'address',
      'notes'
    ];

    public function pricing()
    {
        return $this->hasMany(Product_Pricing::class, 'product_id');
    }
    public function vehicles()
    {
        return $this->hasMany(Product_Vehicles::class, 'product_id');
    }

    public function sales()
    {
        return $this->hasMany(Sales_invoice::class, 'product_id');
    }
}
