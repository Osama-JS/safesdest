<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class UserWalletTransaction extends Model
{
    use LogsActivity;

  protected $table = 'user_wallet_transactions';
  protected $fillable = [
    'user_id',
    'amount',
    'transaction_type',
    'description',
    'image',
    'status',
    'user_wallet_id',
    'team_id',
    'maturity_time',
    'sequence',
    'task_id',
    'clearance_id',
  ];

  protected $casts = [
    'amount' => 'decimal:2',
    'status' => 'boolean',
    'maturity_time' => 'datetime',
  ];

  /**
   * تلقائياً إنشاء sequence عند إنشاء معاملة جديدة
   */
  protected static function booted()
  {
    static::creating(function ($transaction) {
      $last = self::where('user_wallet_id', $transaction->user_wallet_id)
        ->lockForUpdate()
        ->orderByDesc('sequence')
        ->first();

      $transaction->sequence = optional($last)->sequence + 1 ?? 1;
    });
  }

  /**
   * العلاقة مع محفظة المستخدم
   */
  public function userWallet()
  {
    return $this->belongsTo(UserWallet::class, 'user_wallet_id');
  }

  /**
   * العلاقة مع المهمة
   */
  public function task()
  {
    return $this->belongsTo(Task::class, 'task_id');
  }

  /**
   * العلاقة مع المستخدم
   */
  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * العلاقة مع الفريق
   */
  public function team()
  {
    return $this->belongsTo(Teams::class, 'team_id');
  }

  /**
   * العلاقة مع التخليص الجمركي
   */
  public function clearance()
  {
    return $this->belongsTo(Customs_Clearance::class, 'clearance_id');
  }

  /**
   * Scope للمعاملات النشطة
   */
  public function scopeActive($query)
  {
    return $query->where('status', true);
  }

  /**
   * Scope للمعاملات حسب النوع
   */
  public function scopeOfType($query, $type)
  {
    return $query->where('transaction_type', $type);
  }

  /**
   * Scope للمعاملات حسب المهمة
   */
  public function scopeForTask($query, $taskId)
  {
    return $query->where('task_id', $taskId);
  }

  /**
   * Scope للمعاملات حسب المستخدم
   */
  public function scopeForUser($query, $userId)
  {
    return $query->where('user_id', $userId);
  }
}
