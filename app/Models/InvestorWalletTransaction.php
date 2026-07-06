<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class InvestorWalletTransaction extends Model
{
    use LogsActivity;

    protected $table = 'investor_wallet_transactions';

    protected $fillable = [
        'investor_wallet_id',
        'task_id',
        'transaction_type',
        'amount',
        'description',
        'performed_by',
        'balance_after',
        'attachment',
        'source_type',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * العلاقة مع المحفظة
     */
    public function wallet()
    {
        return $this->belongsTo(InvestorWallet::class, 'investor_wallet_id');
    }

    /**
     * العلاقة مع المهمة
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * من أجرى العملية (Admin أو المستثمر)
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope لعمليات الإيداع
     */
    public function scopeCredits($query)
    {
        return $query->where('transaction_type', 'credit');
    }

    /**
     * Scope لعمليات الخصم (الدفع للمهام)
     */
    public function scopeDebits($query)
    {
        return $query->where('transaction_type', 'debit');
    }
}
