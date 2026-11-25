<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
  use LogsActivity;

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
    'amnn_deal_number',
    'amnn_deal_id',
    'amnn_payment_url',
    'amnn_deal_status',
    'is_escrow',
    'additional_data',
    'distribution_attempts',
    'last_attempt_at',
    'delivery_note',
    'delivery_notes',
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
    'investor_id',
    'investor_payment_status',
    'broker_id',
    'broker_commission_type',
    'broker_commission_value',
    'gotech_task_id',
    'gotech_task_number',
    'gotech_status',
    'gotech_funding_amount',
    'gotech_funded_at',
    'gotech_metadata',
  ];

  protected $casts = [
    'additional_data' => 'array',
    'pricing_history' => 'array',
    'pricing_details' => 'array',
    'gotech_metadata' => 'array',
    'gotech_funded_at' => 'datetime',
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

  public function broker()
  {
    return $this->belongsTo(User::class, 'broker_id');
  }

  /**
   * العلاقة الجديدة لدعم الوسطاء المتعددين
   */
  public function brokers()
  {
      return $this->belongsToMany(User::class, 'task_brokers', 'task_id', 'broker_id')
                  ->withPivot('commission_type', 'commission_value')
                  ->withTimestamps();
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
   * العلاقة مع المستثمر الذي دفع قيمة المهمة
   */
  public function investor()
  {
    return $this->belongsTo(User::class, 'investor_id');
  }

  /**
   * Scope: المهام المتاحة للدفع من محفظة مستثمر
   * (غير مقفلة، غير مدفوعة من مستثمر، وغير مطروحة أو ممولة في Go-Tech)
   */
  public function scopeAvailableForInvestorPayment($query)
  {
    return $query
      ->where('investor_payment_status', 'none')
      ->whereNull('investor_id')
      ->where('closed', false)
      ->whereNotIn('status', ['pending', 'in_progress', 'advertised', 'canceld', 'canceled', 'cancelled', 'refund', 'refound', 'refunded'])
      ->where('payment_status', 'waiting')
      ->where(function ($q) {
          $q->whereNull('gotech_status')
            ->orWhereNotIn('gotech_status', ['available', 'pending_funding', 'funded']);
      })
      ->whereHas('customer', function ($q) {
          $q->where('allow_investment', true);
      });
  }

  /**
   * Scope: المهام المؤهلة للطرح والتمويل في Go-Tech
   * (لم تدفع، غير ممولة داخلياً، غير مطروحة أو ممولة في Go-Tech، وتابعة لعميل مفعل لديه الاستثمار)
   */
  public function scopeEligibleForGoTechFunding($query)
  {
    return $query
      ->where('closed', false)
      ->whereNotIn('status', ['canceled', 'canceld', 'cancelled', 'refund', 'refound', 'refunded'])
      ->whereNotIn('payment_status', ['paid', 'completed'])
      ->whereNull('investor_id')
      ->where(function ($q) {
          $q->whereNull('investor_payment_status')
            ->orWhere('investor_payment_status', 'none');
      })
      ->where(function ($q) {
          $q->whereNull('gotech_status')
            ->orWhereNotIn('gotech_status', ['available', 'pending_funding', 'funded']);
      })
      ->whereHas('customer', function ($q) {
          $q->where('allow_investment', true);
      });
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

  /**
   * Check if task is paid via Mtahd Escrow
   */
  public function isMtahdEscrow(): bool
  {
    return $this->is_escrow || !empty($this->amnn_deal_number) || $this->payment_method === 'mtahd';
  }

  /**
   * Get Arabic label for Mtahd deal status
   */
  public function getMtahdStatusLabelAttribute(): string
  {
    return match ($this->amnn_deal_status) {
      'draft'           => 'مسودة صفقة',
      'pending_payment' => 'بانتظار سداد العميل في متعهد',
      'paid'            => 'الضمان محجوز في متعهد (مدفوعة)',
      'released'        => 'تم تحرير وصرف الضمان للمنصة',
      'cancelled'       => 'صفقة ملغاة ومستردة',
      default           => $this->amnn_deal_status ?? 'غير محدد',
    };
  }

  /**
   * حساب تفاصيل عمولة المستثمر للمهمة بعد خصم عمولات المستخدمين ووسطاء المهمة ووسطاء العقد
   */
  public function calculateInvestorCommission(InvestmentContract $contract): array
  {
      // 1. حساب إجمالي عمولة المنصة الأساسية
      $platformGross = 0.0;
      if ($this->ad) {
          if ($this->ad->service_commission_type == 1) {
              $platformGross = (float) $this->ad->service_commission;
          } else {
              $platformGross = ($this->total_price * (float) $this->ad->service_commission) / 100;
          }
      } else {
          $platformGross = (float) $this->commission;
      }

      if ($platformGross <= 0) {
          return [
              'platform_gross'                  => 0.0,
              'user_commissions_total'          => 0.0,
              'user_commissions_details'        => [],
              'task_brokers_total'              => 0.0,
              'task_brokers_details'            => [],
              'contract_task_brokers_total'     => 0.0,
              'contract_investor_brokers_total' => 0.0,
              'broker_payouts'                  => [],
              'total_deductions_from_task'      => 0.0,
              'net_task_commission'             => 0.0,
              'gross_investor_commission'       => 0.0,
              'net_investor_commission'         => 0.0,
              'has_deductions'                  => false,
          ];
      }

      // 2. حساب عمولات المستخدمين المرتبطين بعميل المهمة
      $userCommissionsTotal = 0.0;
      $userCommissionsDetails = [];
      if ($this->customer_id) {
          $customer = $this->customer;
          $userCommissions = ($customer && $customer->relationLoaded('userCommissions'))
              ? $customer->userCommissions->where('status', true)
              : \App\Models\UserCommission::where('customer_id', $this->customer_id)->where('status', true)->with('user')->get();

          foreach ($userCommissions as $uc) {
              $user = $uc->user;
              if ($user && $user->commission_start_date && $this->created_at) {
                  if ($this->created_at->startOfDay() < \Carbon\Carbon::parse($user->commission_start_date)->startOfDay()) {
                      continue;
                  }
              }
              $amt = (float) $uc->calculateCommission($platformGross);
              if ($amt > 0) {
                  $userCommissionsTotal += $amt;
                  $userCommissionsDetails[] = [
                      'user_name' => $user?->name ?? 'مستخدم',
                      'amount'    => round($amt, 2),
                      'type'      => $uc->commission_type,
                      'value'     => (float) $uc->commission_value,
                  ];
              }
          }
      }

      // 3. حساب عمولات وسطاء المهمة (المباشرين، أو عبر السائق، أو وسيط فردي سابق)
      $taskBrokersTotal = 0.0;
      $taskBrokersDetails = [];

      $taskBrokersList = $this->relationLoaded('brokers') ? $this->brokers : $this->brokers()->get();
      if ($taskBrokersList->isNotEmpty()) {
          foreach ($taskBrokersList as $b) {
              if (!empty($b->pivot->commission_start_date) && $this->created_at && $this->created_at < $b->pivot->commission_start_date) {
                  continue;
              }
              $cType = $b->pivot->commission_type ?? 'percentage';
              $cValue = (float) ($b->pivot->commission_value ?? 0);
              $share = $cType === 'percentage' ? (($platformGross * $cValue) / 100) : $cValue;
              if ($share > 0) {
                  $taskBrokersTotal += $share;
                  $taskBrokersDetails[] = [
                      'broker_name' => $b->name,
                      'source'      => 'مباشر على المهمة',
                      'amount'      => round($share, 2),
                      'type'        => $cType,
                      'value'       => $cValue,
                  ];
              }
          }
      } elseif ($this->driver && ($driverBrokers = ($this->driver->relationLoaded('brokers') ? $this->driver->brokers : $this->driver->brokers()->get()))->isNotEmpty()) {
          foreach ($driverBrokers as $b) {
              if (!empty($b->pivot->commission_start_date) && $this->created_at && $this->created_at < $b->pivot->commission_start_date) {
                  continue;
              }
              $cType = $b->pivot->commission_type ?? 'percentage';
              $cValue = (float) ($b->pivot->commission_value ?? 0);
              $share = $cType === 'percentage' ? (($platformGross * $cValue) / 100) : $cValue;
              if ($share > 0) {
                  $taskBrokersTotal += $share;
                  $taskBrokersDetails[] = [
                      'broker_name' => $b->name,
                      'source'      => 'وسيط السائق',
                      'amount'      => round($share, 2),
                      'type'        => $cType,
                      'value'       => $cValue,
                  ];
              }
          }
      } elseif (!empty($this->broker_id) && $this->broker) {
          $b = $this->broker;
          $cType = $this->broker_commission_type ?? 'percentage';
          $cValue = (float) ($this->broker_commission_value ?? 0);
          $share = $cType === 'percentage' ? (($platformGross * $cValue) / 100) : $cValue;
          if ($share > 0) {
              $taskBrokersTotal += $share;
              $taskBrokersDetails[] = [
                  'broker_name' => $b->name,
                  'source'      => 'وسيط المهمة',
                  'amount'      => round($share, 2),
                  'type'        => $cType,
                  'value'       => $cValue,
              ];
          }
      }

      // 4. وسطاء عقد الاستثمار
      $contractBrokersList = collect();
      if ($contract->relationLoaded('brokers') ? $contract->brokers->isNotEmpty() : $contract->brokers()->exists()) {
          $contractBrokersList = $contract->brokers()->get();
      } elseif ($contract->broker_id && $contract->broker_commission_value > 0) {
          $contractBrokersList = collect([
              (object)[
                  'id'    => $contract->broker_id,
                  'name'  => optional($contract->broker)->name,
                  'pivot' => (object)[
                      'broker_commission_source' => $contract->broker_commission_source,
                      'broker_commission_type'   => $contract->broker_commission_type,
                      'broker_commission_value'  => $contract->broker_commission_value,
                  ]
              ]
          ]);
      }

      $contractTaskBrokersTotal = 0.0;
      $brokerPayouts = [];

      foreach ($contractBrokersList as $cb) {
          $pivot = $cb->pivot;
          $commSource = $pivot->broker_commission_source ?? 'investor_commission';
          $commType   = $pivot->broker_commission_type ?? 'percentage';
          $commVal    = (float) ($pivot->broker_commission_value ?? 0);

          if ($commVal <= 0) continue;

          if ($commSource === 'task_commission') {
              $bShare = ($commType === 'percentage') ? (($platformGross * $commVal) / 100) : $commVal;
              $contractTaskBrokersTotal += $bShare;
              $brokerPayouts[] = [
                  'broker_id'   => $cb->id,
                  'broker_name' => $cb->name,
                  'amount'      => round($bShare, 2),
                  'source'      => 'task_commission',
                  'type'        => $commType,
                  'value'       => $commVal,
              ];
          }
      }

      // 5. خصم عمولات المستخدمين والوسطاء ووسطاء المستثمر (من عمولة المهمة) من عمولة المنصة الأساسية أولاً
      $totalDeductionsFromTask = $userCommissionsTotal + $taskBrokersTotal + $contractTaskBrokersTotal;
      $netTaskCommission = max(0.0, $platformGross - $totalDeductionsFromTask);

      // التحقق من الحد الأدنى للعقد بناءً على صافي عمولة المهمة
      $contractInvestorBrokersTotal = 0.0;
      if ($contract->min_commission_threshold > 0 && $netTaskCommission < (float) $contract->min_commission_threshold) {
          $grossInvestorCommission = 0.0;
          $netInvestorCommission = 0.0;
      } else {
          // احتساب عمولة المستثمر بناءً على صافي عمولة المهمة بعد الخصومات
          $grossInvestorCommission = (float) $contract->calculateCommission($netTaskCommission);

          // خصم عمولات وسطاء المستثمر التي مصدرها عمولة المستثمر (investor_commission)
          foreach ($contractBrokersList as $cb) {
              $pivot = $cb->pivot;
              $commSource = $pivot->broker_commission_source ?? 'investor_commission';
              $commType   = $pivot->broker_commission_type ?? 'percentage';
              $commVal    = (float) ($pivot->broker_commission_value ?? 0);

              if ($commVal <= 0) continue;

              if ($commSource === 'investor_commission') {
                  $bShare = ($commType === 'percentage') ? (($grossInvestorCommission * $commVal) / 100) : $commVal;
                  $contractInvestorBrokersTotal += $bShare;
                  $brokerPayouts[] = [
                      'broker_id'   => $cb->id,
                      'broker_name' => $cb->name,
                      'amount'      => round($bShare, 2),
                      'source'      => 'investor_commission',
                      'type'        => $commType,
                      'value'       => $commVal,
                  ];
              }
          }

          $netInvestorCommission = max(0.0, $grossInvestorCommission - $contractInvestorBrokersTotal);
      }

      $hasDeductions = ($totalDeductionsFromTask > 0) || ($contractInvestorBrokersTotal > 0);

      return [
          'platform_gross'                  => round($platformGross, 2),
          'user_commissions_total'          => round($userCommissionsTotal, 2),
          'user_commissions_details'        => $userCommissionsDetails,
          'task_brokers_total'              => round($taskBrokersTotal, 2),
          'task_brokers_details'            => $taskBrokersDetails,
          'contract_task_brokers_total'     => round($contractTaskBrokersTotal, 2),
          'contract_investor_brokers_total' => round($contractInvestorBrokersTotal, 2),
          'broker_payouts'                  => $brokerPayouts,
          'total_deductions_from_task'      => round($totalDeductionsFromTask, 2),
          'net_task_commission'             => round($netTaskCommission, 2),
          'gross_investor_commission'       => round($grossInvestorCommission, 2),
          'net_investor_commission'         => round($netInvestorCommission, 2),
          'has_deductions'                  => $hasDeductions,
      ];
  }
}
