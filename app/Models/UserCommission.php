<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCommission extends Model
{
    use LogsActivity;

  use SoftDeletes;

  protected $table = 'user_commissions';
  protected $fillable = [
    'user_id',
    'customer_id',
    'commission_type',
    'commission_value',
    'status'
  ];

  protected $casts = [
    'commission_value' => 'decimal:2',
    'status' => 'boolean',
  ];

  protected $dates = ['deleted_at'];

  /**
   * العلاقة مع المستخدم
   */
  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * العلاقة مع العميل
   */
  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }

  /**
   * حساب العمولة بناءً على مبلغ المهمة
   */
  public function calculateCommission($taskCommission)
  {
    if ($this->commission_type === 'percentage') {
      return ($taskCommission * $this->commission_value) / 100;
    }
    
    return $this->commission_value;
  }

  /**
   * التحقق من أن العمولة نشطة
   */
  public function isActive()
  {
    return $this->status === true;
  }

  /**
   * Scope للعمولات النشطة
   */
  public function scopeActive($query)
  {
    return $query->where('status', true);
  }

  /**
   * Scope للعمولات حسب العميل
   */
  public function scopeForCustomer($query, $customerId)
  {
    return $query->where('customer_id', $customerId);
  }

  /**
   * Scope للعمولات حسب المستخدم
   */
  public function scopeForUser($query, $userId)
  {
    return $query->where('user_id', $userId);
  }
}
