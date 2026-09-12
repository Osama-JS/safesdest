<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestorCommissionWithdrawal extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'investor_commission_withdrawals';

    protected $fillable = [
        'user_id',
        'user_wallet_id',
        'amount',
        'status',
        'investor_notes',
        'bank_name',
        'account_number',
        'iban_number',
        'account_holder',
        'rejection_reason',
        'admin_notes',
        'receipt_image',
        'user_wallet_transaction_id',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount'       => 'float',
        'processed_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستثمر (المستخدم)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * العلاقة مع محفظة العمولات (UserWallet)
     */
    public function wallet()
    {
        return $this->belongsTo(UserWallet::class, 'user_wallet_id');
    }

    /**
     * العلاقة مع الحركة المالية في محفظة العمولات المسجلة عند الموافقة
     */
    public function transaction()
    {
        return $this->belongsTo(UserWalletTransaction::class, 'user_wallet_transaction_id');
    }

    /**
     * الموظف / المدير الذي راجع الطلب
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * رابط الإيصال العام المباشر
     */
    public function getReceiptUrlAttribute()
    {
        if (!$this->receipt_image) {
            return null;
        }
        if (str_starts_with($this->receipt_image, 'http')) {
            return $this->receipt_image;
        }
        if (str_starts_with($this->receipt_image, 'storage/')) {
            return asset($this->receipt_image);
        }
        return asset('storage/' . ltrim($this->receipt_image, '/'));
    }
}
