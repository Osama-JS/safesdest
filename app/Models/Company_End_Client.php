<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Company_End_Client extends Model
{
    use LogsActivity;

    protected $table = 'company_end_clients';
    protected $fillable = [
        'company_id', 'province_id', 'client_code', 'name', 'phone', 'phone_2',
        'city', 'address', 'latitude', 'longitude', 'notes', 'is_active'
    ];

    public function company()
    {
        return $this->belongsTo(Customer::class, 'company_id');
    }

    public function province()
    {
        return $this->belongsTo(Company_Province::class, 'province_id');
    }
}
