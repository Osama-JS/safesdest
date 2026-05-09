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
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasApiTokens;

    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use SoftDeletes;
    use HasPushSubscriptions;


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
      'is_customs_clearance_agent',
      'investor',
      'bank_name',
      'account_number',
      'iban_number',
      'signature_image'
    ];

    protected $casts = [
      'additional_data' => 'array',
      'is_customs_clearance_agent' => 'boolean',
      'investor' => 'boolean',
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


    /**
     * العلاقة مع عمولات المستخدم
     */
    public function commissions()
    {
        return $this->hasMany(UserCommission::class, 'user_id');
    }

    /**
     * العلاقة مع محفظة المستخدم
     */
    public function userWallet()
    {
        return $this->hasOne(UserWallet::class, 'user_id');
    }

    /**
     * العلاقة مع معاملات محفظة المستخدم
     */
    public function userWalletTransactions()
    {
        return $this->hasMany(UserWalletTransaction::class, 'user_id');
    }

    /**
     * الحصول على العمولات النشطة للمستخدم مع عميل معين
     */
    public function getActiveCommissionsForCustomer($customerId)
    {
        return $this->commissions()
            ->where('customer_id', $customerId)
            ->where('status', true)
            ->get();
    }

    /**
     * التحقق من وجود عمولات للمستخدم مع عميل معين
     */
    public function hasCommissionsForCustomer($customerId)
    {
        return $this->commissions()
            ->where('customer_id', $customerId)
            ->where('status', true)
            ->exists();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    /**
     * العلاقة مع محفظة الاستثمار
     */
    public function investorWallet()
    {
        return $this->hasOne(InvestorWallet::class, 'user_id');
    }

    /**
     * العلاقة مع عقود الاستثمار
     */
    public function investmentContracts()
    {
        return $this->hasMany(InvestmentContract::class, 'user_id');
    }

    /**
     * العقد النشط للمستثمر
     */
    public function activeInvestmentContract()
    {
        return $this->hasOne(InvestmentContract::class, 'user_id')
            ->where('status', 'active')
            ->where('start_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    /**
     * Scope للمستثمرين فقط
     */
    public function scopeInvestors($query)
    {
        return $query->where('investor', true);
    }

    public function customsClearance()
    {
        return $this->hasMany(Customs_Clearance::class, 'user_id');
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

    public function checkClearance($id)
    {
        // إذا كان المستخدم لديه صلاحية عامة لإدارة المهام
        if ($this->can('manage_customs_clearances')) {
            return true;
        }

        // إذا كانت المهمة مرتبطة مباشرة بالمستخدم
        if ($this->customsClearance()->where('id', $id)->exists()) {
            return true;
        }

        $customerIds = $this->customers()->pluck('customers.id'); // الحصول على معرفات الفرق التي ينتمي لها المستخدم


        return Customs_Clearance::where('id', $id)
          ->whereIn('customer_id', $customerIds)
          ->exists();
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

    /**
     * Get the notes for the user.
     */
    public function notes()
    {
        return $this->hasMany(Note::class);
    }
}
