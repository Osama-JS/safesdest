<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sales_invoice extends Model
{
    protected $table = 'sales_invoice';
    protected $fillable = [
        'quantity',
        'unit_price',
        'total_price',
        'customer_id',
        'product_id',
        'vehicle_size_id'
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
    }
}
