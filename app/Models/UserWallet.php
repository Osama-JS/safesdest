<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWallet extends Model
{
    use LogsActivity;

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
   * حساب الرصيد المتاح الفعلي للسحب
   *
   * للمستثمر العام: الرصيد الدفتري الكامل (credit - debit)
   * للمستثمر بالمهام: عمولات المهام المسواة + الإيداعات اليدوية - السحوبات
   * شرط التسوية: وجود حركة credit في investor_wallet_transactions بنفس task_id
   */
  public function getWithdrawableBalanceAttribute()
  {
    $user = $this->user;
    if (!$user) {
      return 0.00;
    }

    $investorWallet = $user->investorWallet;

    // تطبيق المعادلة المحسوبة لجميع المستثمرين (عام أو بالمهام)
    if ($investorWallet) {
      $investorWalletId = $investorWallet->id;

      // إجمالي الإيداعات القابلة للسحب:
      //   أ) إيداعات بدون task_id (مكافآت يدوية وتسويات إدارية) → قابلة فوراً
      //   ب) إيداعات مرتبطة بمهام → مشروطة بوجود حركة credit في محفظة الاستثمار
      //      بنفس task_id (أي أن رأس المال أُعيد لمحفظة الاستثمار)
      $settledCredits = $this->transactions()
        ->where('transaction_type', 'credit')
        ->where(function ($q) use ($investorWalletId) {
          // الإيداعات اليدوية (بدون مهمة)
          $q->whereNull('task_id')
            // أو عمولات مهام تم استرداد رأس مالها
            ->orWhere(function ($q2) use ($investorWalletId) {
              $q2->whereNotNull('task_id')
                ->whereExists(function ($sub) use ($investorWalletId) {
                  $sub->from('investor_wallet_transactions')
                    ->where('investor_wallet_id', $investorWalletId)
                    ->whereColumn('task_id', 'user_wallet_transactions.task_id')
                    ->where('transaction_type', 'credit'); // رد رأس المال
                });
            });
        })
        ->sum('amount');

      // إجمالي المبالغ المسحوبة (debit)
      $totalWithdrawn = $this->debit;

      return (float) max(0.00, $settledCredits - $totalWithdrawn);
    }

    // المستخدمين غير المستثمرين (سائق، عميل): الرصيد الدفتري الكامل
    return (float) max(0.00, $this->balance);
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
