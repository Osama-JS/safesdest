<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Company_Route_Pricing extends Model
{
    use LogsActivity;

    protected $table = 'company_route_pricing';
    protected $fillable = [
        'company_id', 'warehouse_id', 'destination_province_id',
        'default_price', 'is_active'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Company_Warehouse::class, 'warehouse_id');
    }

    public function destinationProvince()
    {
        return $this->belongsTo(Company_Province::class, 'destination_province_id');
    }

    public function vehiclePrices()
    {
        return $this->hasMany(Company_Route_Pricing_Vehicle::class, 'route_pricing_id');
    }
}
