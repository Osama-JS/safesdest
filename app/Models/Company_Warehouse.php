<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company_Warehouse extends Model
{
    protected $table = 'company_warehouses';
    protected $fillable = [
        'company_id', 'province_id', 'name', 'city', 'address',
        'latitude', 'longitude', 'contact_name', 'contact_phone',
        'is_active', 'notes'
    ];

    public function company()
    {
        return $this->belongsTo(Customer::class, 'company_id');
    }

    public function province()
    {
        return $this->belongsTo(Company_Province::class, 'province_id');
    }

    public function routePricings()
    {
        return $this->hasMany(Company_Route_Pricing::class, 'warehouse_id');
    }
}
