<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Sales_invoice extends Model
{
    use LogsActivity;

    protected $table = 'sales_invoice';
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'status',
        'payment_method',
        'paid_at',
        'total_amount',
        'tax_amount',
        'delivery_fee',
        'final_total',
        'notes',
        'created_by'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function details()
    {
        return $this->hasMany(Sales_invoice_detail::class, 'sales_invoice_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'sales_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
