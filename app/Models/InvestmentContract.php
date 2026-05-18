<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class InvestmentContract extends Model
{
    use SoftDeletes;

    protected $table = 'investment_contracts';

    protected $fillable = [
        'user_id',
        'contract_type',
        'commission_type',
        'commission_value',
        'start_date',
        'end_date',
        'status',
        'min_commission_threshold',
        'filter_customer_ids',
        'notes',
        'created_by',
        'broker_id',
        'broker_commission_source',
        'broker_commission_type',
        'broker_commission_value',
    ];

    protected $casts = [
        'commission_value'         => 'decimal:2',
        'min_commission_threshold' => 'decimal:2',
        'filter_customer_ids'      => 'array',
        'start_date'               => 'date',
        'end_date'                 => 'date',
        'broker_commission_value'  => 'decimal:2',
    ];

    protected $dates = ['deleted_at'];

    /**
     * العلاقة مع المستثمر
     */
    public function investor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * العلاقة مع الوسيط (Broker)
     */
    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id');
    }

    /**
     * العلاقة مع Admin الذي أنشأ العقد
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope للعقود النشطة ضمن تاريخها الصالح
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    /**
     * Scope للاستثمار بالمهام
     */
    public function scopeTaskInvestment($query)
    {
        return $query->where('contract_type', 'task_investment');
    }

    /**
     * Scope للاستثمار العام
     */
    public function scopeGeneralInvestment($query)
    {
        return $query->where('contract_type', 'general_investment');
    }

    /**
     * هل العقد نشط حالياً؟
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->start_date->gt(now())) return false;
        if ($this->end_date && $this->end_date->lt(now()->startOfDay())) return false;
        return true;
    }

    /**
     * حساب عمولة المستثمر على مبلغ معين من عمولة المنصة
     * مع ضمان أنها لا تتجاوز عمولة المنصة
     *
     * @param float $platformCommission عمولة المنصة
     * @return float عمولة المستثمر
     */
    public function calculateCommission(float $platformCommission): float
    {
        if ($this->commission_type === 'percentage') {
            $commission = ($platformCommission * $this->commission_value) / 100;
        } else {
            $commission = (float) $this->commission_value;
        }

        // ضمان أن عمولة المستثمر لا تتجاوز عمولة المنصة
        return min($commission, $platformCommission);
    }

    /**
     * التحقق من أن المهمة ضمن نطاق تاريخ العقد
     *
     * @param Carbon|string $taskCreatedAt تاريخ إنشاء المهمة
     */
    public function isTaskWithinContractPeriod($taskCreatedAt): bool
    {
        $date = $taskCreatedAt instanceof Carbon
            ? $taskCreatedAt
            : Carbon::parse($taskCreatedAt);

        if ($date->lt($this->start_date->startOfDay())) return false;
        if ($this->end_date && $date->gt($this->end_date->endOfDay())) return false;

        return true;
    }
}
