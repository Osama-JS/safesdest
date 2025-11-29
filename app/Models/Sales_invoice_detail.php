<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sales_invoice_detail extends Model
{
    protected $table = 'sales_invoice_details';

    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'line_total'
    ];

    public function invoice()
    {
        return $this->belongsTo(Sales_invoice::class, 'sales_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
