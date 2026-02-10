<?php

namespace App\Models;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasRoles;
    use HasFactory;
    use SoftDeletes;
    use HasPushSubscriptions;
    use HasApiTokens;

    protected $guard_name = 'customer';
    protected $table = 'customers';
    protected $fillable = [
      'name',
      'email',
      'password',
      'phone',
      'phone_code',
      'image',
      'status',
      'company_name',
      'company_address',
      'additional_data',
      'form_template_id',
      'role_id',
      'team_id',
      'is_customs_clearance_agent',
      'bank_name',
      'account_number',
      'iban_number',
      'confirmation_code',
      'confirmation_code_expires_at',
      'fcm_token',
      'device_id',
      'policy_file_name',
      'signature_image'
    ];

    protected $casts = [
      'additional_data' => 'array',
      'is_customs_clearance_agent' => 'boolean',
    ];

    protected $dates = ['deleted_at'];


    public function form_template()
    {
        return $this->belongsTo(Form_Template::class, 'form_template_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function tags()
    {
        return $this->hasMany(Tag_Customers::class, 'customer_id');
    }

    public function points()
    {
        return $this->hasMany(Point::class, 'customer_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'customer_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'payable');
    }

    public function transactionsClearance()
    {
        return $this->morphMany(Clearance_Transactions::class, 'payable');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'customer_id');
    }

    public function formTemplate()
    {
        return $this->belongsTo(Form_Template::class, 'form_template_id');
    }

    /**
     * العلاقة مع عمولات المستخدمين المرتبطين بهذا العميل
     */
    public function userCommissions()
    {
        return $this->hasMany(UserCommission::class, 'customer_id');
    }

    /**
     * الحصول على المستخدمين الذين لديهم عمولات مع هذا العميل
     */
    public function usersWithCommissions()
    {
        return $this->belongsToMany(User::class, 'user_commissions', 'customer_id', 'user_id')
            ->wherePivot('status', true)
            ->withPivot('commission_type', 'commission_value', 'status');
    }

    /**
     * الحصول على العمولات النشطة لهذا العميل
     */
    public function getActiveCommissions()
    {
        return $this->userCommissions()
            ->where('status', true)
            ->with('user')
            ->get();
    }

    /**
     * Get additional data that customer is allowed to see
     * Similar to driver's getDriverVisibleAdditionalDataAttribute
     */
    public function getCustomerVisibleAdditionalDataAttribute()
    {
        if (!is_array($this->additional_data)) {
            return [];
        }

        $formFields = $this->formTemplate?->fields ?? collect();

        return collect($this->additional_data)->filter(function ($item) use ($formFields) {
            return $formFields->contains(function ($field) use ($item) {
                return $field->label == $item['label'] &&
                  in_array($field->customer_can, ['read', 'write']);
            });
        })->values()->all();
    }

    // علاقات التخليص الجمركي

    /**
     * طلبات التخليص التي أنشأها هذا العميل
     */
    public function customsClearanceRequests()
    {
        return $this->hasMany(CustomsClearance::class, 'customer_id');
    }

    /**
     * طلبات التخليص المعينة لهذا العميل كمخلص
     */
    public function assignedCustomsClearances()
    {
        return $this->hasMany(CustomsClearance::class, 'clearance_customer_id');
    }

    /**
     * عروض التخليص المقدمة من هذا العميل
     */
    public function customsClearanceOffers()
    {
        return $this->hasMany(CustomsClearanceOffer::class, 'customer_id');
    }

    /**
     * تاريخ عمليات التخليص التي قام بها هذا العميل
     */
    public function customsClearanceHistories()
    {
        return $this->hasMany(CustomsClearanceHistory::class, 'customer_id');
    }

    /**
     * فلترة العملاء المخلصين الجمركيين
     */
    public function scopeCustomsClearanceAgents($query)
    {
        return $query->where('is_customs_clearance_agent', true);
    }

    /**
     * التحقق من كون العميل مخلص جمركي
     */
    public function isCustomsClearanceAgent()
    {
        return $this->is_customs_clearance_agent;
    }
}
