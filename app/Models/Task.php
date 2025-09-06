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
      'created_at'
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
}
