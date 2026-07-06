<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Task_Ad extends Model
{
    use LogsActivity;

  protected $table = 'tasks_ads';
  protected $fillable = [
    'description',
    'status',
    'highest_price',
    'lowest_price',
    'included',
    'closed_at',
    'service_commission',
    'service_commission_type',
    'vat_commission',
    'task_id',
  ];

    protected $appends = [
        'final_lowest_price',
        'final_highest_price',
    ];
  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }

  public function offers()
  {
    return $this->hasMany(Task_Offire::class, 'task_ad_id');
  }

     /* ===============================
       Accessors
    =============================== */

    public function getFinalLowestPriceAttribute()
    {
        return $this->calculatePrice($this->attributes['lowest_price']);
    }

    public function getFinalHighestPriceAttribute()
    {
        return $this->calculatePrice($this->attributes['highest_price']);
    }

    /* ===============================
       Core calculation
    =============================== */

    private function calculatePrice($price)
    {
        if ($this->included) {
            return (float) $price;
        }

        $commission = is_numeric($this->service_commission) ? $this->service_commission : 0;
        $vat        = is_numeric($this->vat_commission) ? $this->vat_commission : 0;

        // عمولة الخدمة
        if ($this->service_commission_type === 1) {
            $price += $commission;
        } else {
            $price += $price * ($commission / 100);
        }

        // الضريبة
        $price += $price * ($vat / 100);

        return round($price, 2);
    }
}
