<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company_Route_Pricing_Vehicle extends Model
{
    protected $table = 'company_route_pricing_vehicles';
    protected $fillable = ['route_pricing_id', 'vehicle_size_id', 'price'];

    public function routePricing()
    {
        return $this->belongsTo(Company_Route_Pricing::class, 'route_pricing_id');
    }

    public function vehicleSize()
    {
        return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
    }
}
