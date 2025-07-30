<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Http\Controllers\pages\UserTeams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
  use HasApiTokens;

  use HasFactory;
  use HasProfilePhoto;
  use Notifiable;
  use TwoFactorAuthenticatable;
  use HasRoles;
  use SoftDeletes;


  protected $fillable = [
    'name',
    'email',
    'password',
    'phone',
    'phone_code',
    'status',  // ['active', 'inactive', 'deleted', 'pending']
    'reset_password',
    'last_login',
    'additional_data',
    'form_template_id',
    'role_id',
    'is_customs_clearance_agent'
  ];

  protected $casts = [
    'additional_data' => 'array',
    'is_customs_clearance_agent' => 'boolean',
  ];

  protected $dates = ['deleted_at'];

  protected $hidden = [
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
  ];


  protected $appends = [
    'profile_photo_url',
  ];


  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  public function role()
  {
    return $this->belongsTo(Role::class, 'role_id');
  }

  public function teams()
  {
    return $this->belongsToMany(Teams::class, 'user_has_teams', 'user_id', 'team_id');
  }

  protected $guard_name = 'web';

  public function customers()
  {
    return $this->belongsToMany(Customer::class);
  }

  public function tasks()
  {
    return $this->hasMany(Task::class, 'user_id');
  }

  public function checkCustomer($id)
  {
    if ($this->can('mange_customers')) {
      return true;
    }
    return $this->customers()->where('customer_id', $id)->exists();
  }

  public function checkTeam($id)
  {
    if ($this->can('mange_teams')) {
      return true;
    }
    return $this->teams()->where('team_id', $id)->exists();
  }

  public function checkTeams()
  {
    if ($this->can('mange_teams')) {
      return true;
    }
    return false;
  }


  public function checkDriver($id)
  {
    if ($this->can('mange_drivers')) {
      return true;
    }
    $driver = Driver::find($id);
    if ($driver) {
      return $this->teams()->where('team_id', $driver->team_id)->exists();
    }
    return false;
  }

  public function checkTask($id)
  {
    // إذا كان المستخدم لديه صلاحية عامة لإدارة المهام
    if ($this->can('manage_tasks')) {
      return true;
    }

    // إذا كانت المهمة مرتبطة مباشرة بالمستخدم
    if ($this->tasks()->where('id', $id)->exists()) {
      return true;
    }

    // إذا كانت المهمة مرتبطة بفريق والمستخدم عضو في هذا الفريق
    $teamIds = $this->teams()->pluck('teams.id'); // الحصول على معرفات الفرق التي ينتمي لها المستخدم

    return Task::where('id', $id)
      ->whereIn('team_id', $teamIds)
      ->exists();
  }


  public function transactions()
  {
    return $this->morphMany(Transaction::class, 'payable');
  }

  public function transactionsClearance()
  {
    return $this->morphMany(Clearance_Transactions::class, 'payable');
  }

  // علاقات التخليص الجمركي

  /**
   * طلبات التخليص التي أنشأها هذا المستخدم
   */
  public function customsClearanceRequests()
  {
    return $this->hasMany(CustomsClearance::class, 'user_id');
  }

  /**
   * طلبات التخليص المعينة لهذا المستخدم كمخلص
   */
  public function assignedCustomsClearances()
  {
    return $this->hasMany(CustomsClearance::class, 'clearance_user_id');
  }

  /**
   * عروض التخليص المقدمة من هذا المستخدم
   */
  public function customsClearanceOffers()
  {
    return $this->hasMany(CustomsClearanceOffer::class, 'user_id');
  }

  /**
   * تاريخ عمليات التخليص التي قام بها هذا المستخدم
   */
  public function customsClearanceHistories()
  {
    return $this->hasMany(CustomsClearanceHistory::class, 'user_id');
  }

  /**
   * فلترة المستخدمين المخلصين الجمركيين
   */
  public function scopeCustomsClearanceAgents($query)
  {
    return $query->where('is_customs_clearance_agent', true);
  }

  /**
   * التحقق من كون المستخدم مخلص جمركي
   */
  public function isCustomsClearanceAgent()
  {
    return $this->is_customs_clearance_agent;
  }
}
