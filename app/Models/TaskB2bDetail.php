<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskB2bDetail extends Model
{
    protected $table = 'task_b2b_details';

    protected $fillable = [
        'task_id',
        'company_id',
        'warehouse_id',
        'end_client_id',
        'vehicle_size_id',
        'base_price',
        'commission',
        'vat_amount',
        'total_price',
        'pricing_rule',
        'pickup_name',
        'pickup_phone',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'delivery_name',
        'delivery_phone',
        'delivery_address',
        'delivery_lat',
        'delivery_lng',
    ];

    protected $casts = [
        'base_price'   => 'decimal:2',
        'commission'   => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'total_price'  => 'decimal:2',
        'pickup_lat'   => 'decimal:7',
        'pickup_lng'   => 'decimal:7',
        'delivery_lat' => 'decimal:7',
        'delivery_lng' => 'decimal:7',
    ];

    // ── العلاقات ──

    public function task(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'company_id');
    }

    public function warehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company_Warehouse::class, 'warehouse_id');
    }

    public function endClient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company_End_Client::class, 'end_client_id');
    }

    public function vehicleSize(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
    }
}
