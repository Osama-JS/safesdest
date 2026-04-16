<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company_Pricing_Config extends Model
{
    protected $table = 'company_pricing_configs';
    protected $fillable = [
        'company_id', 'commission_type', 'commission_value', 'vat_percentage'
    ];

    public function company()
    {
        return $this->belongsTo(Customer::class, 'company_id');
    }
}
