<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company_Province extends Model
{
    protected $table = 'company_provinces';
    protected $fillable = ['name_ar', 'name_en', 'region', 'is_active'];

    public function warehouses()
    {
        return $this->hasMany(Company_Warehouse::class, 'province_id');
    }

    public function endClients()
    {
        return $this->hasMany(Company_End_Client::class, 'province_id');
    }

    public function routePricings()
    {
        return $this->hasMany(Company_Route_Pricing::class, 'destination_province_id');
    }
}
