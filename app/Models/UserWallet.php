<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWallet extends Model
{
  use SoftDeletes;

  protected $table = 'user_wallets';
  protected $fillable = [
    'debt_ceiling',
    'user_type',
    'user_id',
    'status',
    'preview'
  ];

  protected $casts = [
    'debt_ceiling' => 'decimal:2',
    'status' => 'boolean',
    'preview' => 'boolean',
  ];

  protected $dates = ['deleted_at'];

  /**
   * الحصول على المالك (المستخدم)
   */
  public function getOwnerAttribute()
  {
    return $this->user;
  }

  /**
   * حساب إجمالي الائتمان
   */
  public function getCreditAttribute()
  {
    $credit = $this->transactions()
      ->where('transaction_type', 'credit')
      ->sum('amount');

    return $credit;
  }

  /**
   * حساب إجمالي الخصم
   */
  public function getDebitAttribute()
  {
    $debit = $this->transactions()
      ->where('transaction_type', 'debit')
      ->sum('amount');

    return $debit;
  }

  /**
   * حساب الرصيد الحالي
   */
  public function getBalanceAttribute()
  {
    $credit = $this->transactions()
      ->where('transaction_type', 'credit')
      ->sum('amount');

    $debit = $this->transactions()
      ->where('transaction_type', 'debit')
      ->sum('amount');

    return $credit - $debit;
  }

  /**
   * الحصول على آخر معاملة
   */
  public function getLastTransactionAttribute()
  {
    $last = $this->transactions()
      ->latest('created_at')
      ->value('created_at');

    return $last ? $last->format('Y-m-d H:i') : null;
  }

  /**
   * العلاقة مع المستخدم
   */
  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * العلاقة مع المعاملات
   */
  public function transactions()
  {
    return $this->hasMany(UserWalletTransaction::class, 'user_wallet_id');
  }

  /**
   * Scope للمحافظ النشطة
   */
  public function scopeActive($query)
  {
    return $query->where('status', true);
  }

  /**
   * Scope للمحافظ حسب المستخدم
   */
  public function scopeForUser($query, $userId)
  {
    return $query->where('user_id', $userId);
  }
}
