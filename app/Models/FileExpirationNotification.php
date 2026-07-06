<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FileExpirationNotification extends Model
{
    use LogsActivity;

  use HasFactory;

  protected $table = 'file_expiration_notifications';


  protected $fillable = [
    'user_type',
    'user_id',
    'field_name',
    'field_label',
    'file_path',
    'expiration_date',
    'notification_sent_date',
    'days_before_expiration',
    'status',
    'recipients'
  ];


  protected $casts = [
    'expiration_date' => 'date',
    'notification_sent_date' => 'date',
    'recipients' => 'array',
    'days_before_expiration' => 'integer'
  ];

  /**
   * القيم الافتراضية للحقول
   */
  protected $attributes = [
    'status' => 'sent'
  ];

  /**
   * الحقول المخفية من التسلسل
   */
  protected $hidden = [
    'file_path' // لأسباب أمنية
  ];

  /**
   * العلاقة مع المستخدم حسب النوع
   *
   * @return \Illuminate\Database\Eloquent\Relations\MorphTo|null
   */
  public function user()
  {
    try {
      switch ($this->user_type) {
        case 'user':
          return $this->belongsTo(User::class, 'user_id');
        case 'customer':
          return $this->belongsTo(Customer::class, 'user_id');
        case 'driver':
          return $this->belongsTo(Driver::class, 'user_id');
        default:
          Log::warning('Unknown user_type in FileExpirationNotification', [
            'id' => $this->id,
            'user_type' => $this->user_type
          ]);
          return null;
      }
    } catch (\Exception $e) {
      Log::error('Error getting user relationship in FileExpirationNotification', [
        'id' => $this->id,
        'user_type' => $this->user_type,
        'error' => $e->getMessage()
      ]);
      return null;
    }
  }

  /**
   * فحص ما إذا كان يجب تعليق الحساب (مرت 3 أيام على التنبيه)
   *
   * @return bool
   */
  public function shouldSuspendAccount(): bool
  {
    if ($this->status !== 'sent') {
      return false;
    }

    return $this->notification_sent_date->diffInDays(now()) >= 3;
  }

  /**
   * فحص ما إذا كان الملف منتهي الصلاحية
   *
   * @return bool
   */
  public function isExpired(): bool
  {
    return $this->expiration_date->isPast();
  }

  /**
   * الحصول على عدد الأيام المتبقية للانتهاء
   *
   * @return int (سالب إذا انتهى)
   */
  public function getDaysRemainingAttribute(): int
  {
    return now()->startOfDay()->diffInDays($this->expiration_date, false);
  }

  /**
   * الحصول على نوع المستخدم بالعربية
   *
   * @return string
   */
  public function getUserTypeInArabicAttribute(): string
  {
    $types = [
      'user' => 'مستخدم النظام',
      'customer' => 'عميل',
      'driver' => 'سائق'
    ];

    return $types[$this->user_type] ?? $this->user_type;
  }

  /**
   * Scope للحصول على التنبيهات التي تحتاج تعليق الحساب
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeNeedsSuspension($query)
  {
    return $query->where('status', 'sent')
      ->where('notification_sent_date', '<=', now()->subDays(3)->toDateString());
  }

  /**
   * Scope للحصول على التنبيهات المرسلة اليوم
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeSentToday($query)
  {
    return $query->whereDate('notification_sent_date', today());
  }

  /**
   * Scope للحصول على التنبيهات حسب نوع المستخدم
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @param string $userType
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeForUserType($query, string $userType)
  {
    return $query->where('user_type', $userType);
  }

  /**
   * Scope للحصول على التنبيهات المرسلة في فترة معينة
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @param \Carbon\Carbon $from
   * @param \Carbon\Carbon $to
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeBetweenDates($query, Carbon $from, Carbon $to)
  {
    return $query->whereBetween('notification_sent_date', [
      $from->toDateString(),
      $to->toDateString()
    ]);
  }

  /**
   * إنشاء تنبيه جديد مع التحقق من التكرار
   *
   * @param array $data
   * @return static|null
   */
  public static function createSafely(array $data): ?self
  {
    try {
      // التحقق من عدم وجود تنبيه مماثل اليوم
      $existing = self::where([
        'user_type' => $data['user_type'],
        'user_id' => $data['user_id'],
        'field_name' => $data['field_name'],
        'notification_sent_date' => $data['notification_sent_date'] ?? today()
      ])->first();

      if ($existing) {
        Log::info('Duplicate notification prevented', [
          'user_type' => $data['user_type'],
          'user_id' => $data['user_id'],
          'field_name' => $data['field_name']
        ]);
        return null;
      }

      return self::create($data);
    } catch (\Exception $e) {
      Log::error('Error creating FileExpirationNotification', [
        'data' => $data,
        'error' => $e->getMessage()
      ]);
      return null;
    }
  }

  /**
   * الحصول على إحصائيات التنبيهات
   *
   * @param \Carbon\Carbon|null $date
   * @return array
   */
  public static function getStatistics(Carbon $date = null): array
  {
    $date = $date ?? today();

    $query = self::whereDate('notification_sent_date', $date);

    return [
      'total' => $query->count(),
      'by_user_type' => $query->selectRaw('user_type, COUNT(*) as count')
        ->groupBy('user_type')
        ->pluck('count', 'user_type')
        ->toArray(),
      'by_status' => $query->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray(),
      'expired_files' => $query->where('days_before_expiration', '<', 0)->count(),
      'expiring_soon' => $query->where('days_before_expiration', '>=', 0)
        ->where('days_before_expiration', '<=', 1)
        ->count()
    ];
  }
}
