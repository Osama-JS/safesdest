<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product_Pricing extends Model
{
    protected $table = 'products_pricing';
    protected $fillable = [
        'product_id',
        'customer_id',
        'price',
        'notes'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
