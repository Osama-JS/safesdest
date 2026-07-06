<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Product_Vehicles extends Model
{
    use LogsActivity;

    protected $table = 'products_vehicle';
    protected $fillable = [
        'product_id',
        'vehicle_size_id',
        'maximum_order',
        'notes'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
    }
}
