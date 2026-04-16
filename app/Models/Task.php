<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'tasks';
    protected $fillable = [
      'status',
      'pricing_type',
      'total_price',
      'commission_type',
      'commission',
      'payment_method',
      'payment_status',
      'payment_paid',
      'payment_pending_amount',
      'payment_id',
      'additional_data',
      'distribution_attempts',
      'last_attempt_at',
      'delivery_note',
      'delivery_number',
      'pending_driver_id',
      'pricing_history',
      'pricing_details',
      'conditions',
      'closed',
      'closed_at',
      'completed_at',
      'order_id',
      'customer_id',
      'driver_id',
      'team_id',
      'user_id',
      'form_template_id',
      'pricing_id',
      'vehicle_size_id',
      'created_at',
      'customer_cancel',
      'customer_cancel_reason',
      'driver_cancel',
      'driver_cancel_reason',
      'is_broadcast',
      'signature_request_id',
      'signature_status',
      'customer_task_number',
      'company_warehouse_id',
      'company_end_client_id',
    ];

    protected $casts = [
      'additional_data' => 'array',
      'pricing_history' => 'array',
      'pricing_details' => 'array',
      'last_attempt_at' => 'datetime',
      'completed_at' => 'datetime',


    ];

    protected $appends = ['owner'];

    public function getOwnerAttribute()
    {
        return $this->customer_id ? 'customer' : 'admin';
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function team()
    {
        return $this->belongsTo(Teams::class, 'team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function formTemplate()
    {
        return $this->belongsTo(Form_Template::class, 'form_template_id');
    }
    public function pricingTemplate()
    {
        return $this->belongsTo(Pricing_Template::class, 'pricing_id');
    }

    public function payments()
    {
        return $this->hasMany(Payments::class, 'task_id');
    }

    public function point()
    {
        return $this->hasOne(Task_Points::class, 'task_id');
    }

    public function points()
    {
        return $this->hasMany(Task_Points::class, 'task_id');
    }

    public function taskPoints()
    {
        return $this->hasMany(Task_Points::class, 'task_id');
    }

    public function history()
    {
        return $this->hasMany(Task_History::class, 'task_id');
    }

    public function pickup()
    {
        return $this->hasOne(Task_Points::class, 'task_id')->where('type', 'pickup');
    }

    public function delivery()
    {
        return $this->hasOne(Task_Points::class, 'task_id')->where('type', 'delivery');
    }

    public function ad()
    {
        return $this->hasOne(Task_Ad::class, 'task_id');
    }

    public function vehicle_size()
    {
        return $this->belongsTo(Vehicle_Size::class, 'vehicle_size_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Sales_invoice::class, 'sales_invoice_id');
    }

    public function userWalletTransactions()
    {
        return $this->hasMany(UserWalletTransaction::class);
    }
    /**
     * Get additional data that customer is allowed to see
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

    public function getDriverVisibleAdditionalDataAttribute()
    {
        if (!is_array($this->additional_data)) {
            return [];
        }

        $formFields = $this->formTemplate?->fields ?? collect();

        return collect($this->additional_data)->filter(function ($item) use ($formFields) {
            return $formFields->contains(function ($field) use ($item) {
                return $field->label === $item['label'] && ($field->driver_can === 'read' || $field->driver_can === 'write');
            });
        })->values()->all(); // إعادة ترقيم المفاتيح
    }

    public function getPdfVisibleAdditionalDataAttribute()
    {
        if (!is_array($this->additional_data)) {
            return [];
        }

        $formFields = $this->formTemplate?->fields ?? collect();

         return collect($this->additional_data)->filter(function ($item) use ($formFields) {
            return $formFields->contains(function ($field) use ($item) {
                return $field->label === $item['label'] && ($field->driver_can === 'read' || $field->driver_can === 'write');
            });
        })->values()->all();
    }

    public function attempts()
    {
        return $this->hasMany(TaskDriverAttempt::class, 'task_id');
    }

    public function claimRequests()
    {
        return $this->hasMany(TaskClaimRequest::class, 'task_id');
    }

    public function companyWarehouse()
    {
        return $this->belongsTo(Company_Warehouse::class, 'company_warehouse_id');
    }

    public function companyEndClient()
    {
        return $this->belongsTo(Company_End_Client::class, 'company_end_client_id');
    }

    /**
     * B2B details linking table (identity + pricing snapshot).
     */
    public function b2bDetail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TaskB2bDetail::class, 'task_id');
    }

    /**
     * Is this task a B2B company task?
     */
    public function getIsB2bAttribute(): bool
    {
        return $this->company_warehouse_id !== null;
    }
}
