<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company_Client_Pricing_Vehicle extends Model
{
    protected $table = 'company_client_pricing_vehicles';
    protected $fillable = [
        'company_id', 'warehouse_id', 'end_client_id', 'vehicle_size_id', 'price'
    ];

    public function company()
    {
        return $this->belongsTo(Customer::class, 'company_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Company_Warehouse::class, 'warehouse_id');
    }

    public function endClient()
    {
        return $this->belongsTo(Company_End_Client::class, 'end_client_id');
    }

    public function vehicleSize()
    {
        return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
    }
}
