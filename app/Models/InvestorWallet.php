<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestorWallet extends Model
{
    use SoftDeletes;

    protected $table = 'investor_wallets';

    protected $fillable = [
        'user_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected $dates = ['deleted_at'];

    /**
     * العلاقة مع المستثمر (User)
     */
    public function investor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * العلاقة مع حركات المحفظة
     */
    public function transactions()
    {
        return $this->hasMany(InvestorWalletTransaction::class, 'investor_wallet_id');
    }

    /**
     * حساب الرصيد الحالي (credit - debit)
     */
    public function getBalanceAttribute(): float
    {
        return (float) $this->transactions()
            ->selectRaw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance")
            ->value('balance') ?? 0;
    }

    /**
     * إجمالي الإيداعات (رأس المال)
     */
    public function getCreditAttribute(): float
    {
        return (float) $this->transactions()
            ->where('transaction_type', 'credit')
            ->where('source_type', 'capital')
            ->sum('amount');
    }

    /**
     * إجمالي استعادة الاستثمار (المبالغ المستردة)
     */
    public function getReturnedCapitalAttribute(): float
    {
        return (float) $this->transactions()
            ->where('transaction_type', 'credit')
            ->where('source_type', 'refund')
            ->sum('amount');
    }

    /**
     * إجمالي المسحوبات (المدفوع للمهام)
     */
    public function getDebitAttribute(): float
    {
        return (float) $this->transactions()->where('transaction_type', 'debit')->sum('amount');
    }

    /**
     * Scope للمحافظ النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
