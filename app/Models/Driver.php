<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    use SoftDeletes;
    use HasPushSubscriptions;

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
      'role_id',
      'whatsapp_country_code',
      'whatsapp_number',
      'phone_is_whatsapp',
      'device_id',
      'fcm_token',
      'app_version',
      'last_activity_at',
      'reset_token',
      'reset_token_expires_at',
      'bank_name',
      'account_number',
      'iban_number',
      'signature_image'
    ];
    protected $casts = [
      'additional_data' => 'array',
      'reset_token_expires_at' => 'datetime',
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

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function vehicle_size()
    {
        return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'driver_id');
    }

    public function possible_tasks()
    {
        return $this->hasMany(Task::class, 'pending_driver_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'driver_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'payable');
    }

    /**
     * Get the notifications for this driver
     */
    public function driverNotifications()
    {
        return $this->hasMany(Notification_Drivers::class, 'driver_id');
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCountAttribute()
    {
        return $this->driverNotifications()->unread()->count();
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

    public function formTemplate()
    {
        return $this->belongsTo(Form_Template::class, 'form_template_id');
    }

    public function getDriverVisibleAdditionalDataAttribute()
    {
        if (!is_array($this->additional_data)) {
            return [];
        }

        $formFields = $this->formTemplate?->fields ?? collect();

        return collect($this->additional_data)->filter(function ($item, $key) use ($formFields) {
            // تأكد أن $item مصفوفة ولها مفتاح label قبل الفحص
            if (!is_array($item) || !isset($item['label'])) {
                return false;
            }

            return $formFields->contains(function ($field) use ($item) {
                return $field->label == $item['label'] &&
                  in_array($field->customer_can, ['read', 'write']);
            });
        })->all();
    }

    /**
     * Get full WhatsApp number with country code
     */
    public function getFullWhatsappNumberAttribute()
    {
        if ($this->phone_is_whatsapp) {
            return $this->phone_code . $this->phone;
        }

        if ($this->whatsapp_country_code && $this->whatsapp_number) {
            return $this->whatsapp_country_code . $this->whatsapp_number;
        }

        return null;
    }

    /**
     * Get WhatsApp number for display
     */
    public function getWhatsappDisplayAttribute()
    {
        if ($this->phone_is_whatsapp) {
            return $this->phone_code . ' ' . $this->phone . ' (Same as phone)';
        }

        if ($this->whatsapp_country_code && $this->whatsapp_number) {
            return $this->whatsapp_country_code . ' ' . $this->whatsapp_number;
        }

        return 'Not provided';
    }

    /**
     * Set WhatsApp data based on phone_is_whatsapp flag
     */
    public function setWhatsappFromPhone()
    {
        if ($this->phone_is_whatsapp) {
            $this->whatsapp_country_code = $this->phone_code;
            $this->whatsapp_number = $this->phone;
        }
    }

    /**
     * Create a new API token for the driver with specific abilities
     */
    public function createDriverToken($deviceName, $deviceId = null, $fcmToken = null)
    {
        // Define driver-specific abilities
        $abilities = [
            'driver:read',
            'driver:update',
            'tasks:read',
            'tasks:manage',
            'location:update',
            'wallet:read',
            'notifications:read'
        ];

        // Create the token
        $token = $this->createToken($deviceName, $abilities);

        // Update driver with device info if provided
        if ($deviceId || $fcmToken) {
            $updateData = [];
            if ($deviceId) {
                $updateData['device_id'] = $deviceId;
            }
            if ($fcmToken) {
                $updateData['fcm_token'] = $fcmToken;
            }
            $this->update($updateData);
        }

        return $token;
    }

    /**
     * Revoke all tokens for this driver
     */
    public function revokeAllTokens()
    {
        return $this->tokens()->delete();
    }

    /**
     * Revoke tokens for a specific device
     */
    public function revokeDeviceTokens($deviceName)
    {
        return $this->tokens()->where('name', $deviceName)->delete();
    }

    /**
     * Check if driver has specific ability
     */
    public function hasDriverAbility($ability)
    {
        $token = $this->currentAccessToken();
        if (!$token) {
            return false;
        }

        return in_array($ability, $token->abilities);
    }
}
