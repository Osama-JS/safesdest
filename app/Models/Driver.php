<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Driver extends Authenticatable
{
  use HasRoles;
  use SoftDeletes;
  protected $guard_name = 'driver';

  protected $table = 'drivers';
  protected $fillable = [
    'name',
    'phone',
    'phone_code',
    'email',
    'image',
    'username',
    'password',
    'status',
    'address',
    'online',
    'free',
    'longitude',
    'altitude',
    'last_seen_at',
    'commission_type',
    'commission_value',
    'location_update_interval',
    'additional_data',
    'form_template_id',
    'team_id',
    'vehicle_size_id',
    'role_id'
  ];
  protected $casts = [
    'additional_data' => 'array',
  ];

  protected $dates = ['deleted_at'];

  public function team()
  {
    return $this->belongsTo(Teams::class, 'team_id');
  }
  public function tags()
  {
    return $this->hasMany(Tag_Drivers::class, 'driver_id');
  }

  public function vehicle_size()
  {
    return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
  }

  public function tasks()
  {
    return $this->belongsTo(Task::class, 'driver_id');
  }

  public function possible_tasks()
  {
    return $this->hasMany(Task::class, 'pending_driver_id');
  }



  public function transactions()
  {
    return $this->morphMany(Transaction::class, 'payable');
  }

  // App\Models\Driver.php

  public function calculateCommission(float $totalPrice): float
  {
    $commissionType = $this->commission_type;
    $commissionValue = $this->commission_value;

    // إذا لم يوجد عمولة للسائق نبحث عن الفريق
    if (!$commissionType && $this->team_id && $this->team) {
      $commissionType = $this->team->commission_type;
      $commissionValue = $this->team->commission_value;
    }

    // إذا لم يوجد عمولة لا في السائق ولا في الفريق نرجع لإعدادات النظام
    if (!$commissionType) {
      $commissionType = \App\Models\Settings::where('key', 'commission_type')->value('value');

      if ($commissionType === 'rate') {
        $commissionValue = \App\Models\Settings::where('key', 'commission_rate')->value('value');
      } elseif ($commissionType === 'fixed') {
        $commissionValue = \App\Models\Settings::where('key', 'commission_fixed')->value('value');
      }
    }

    // حساب العمولة
    if ($commissionType && $commissionValue !== null) {
      if ($commissionType === 'rate') {
        return ($commissionValue / 100) * $totalPrice;
      } elseif ($commissionType === 'fixed') {
        return $commissionValue;
      }
    }

    return 0;
  }
}
