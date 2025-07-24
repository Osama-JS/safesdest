<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customs_Clearance extends Model
{
  protected $table = 'customs_clearance';
  protected $fillable = [
    'status',
    'total_price',
    'commission_type',
    'commission',
    'payment_method',
    'payment_status',
    'payment_paid',
    'payment_pending_amount',
    'closed',
    'delivery_note',
    'additional_data',
    'pricing_history',
    'pricing_details',
    'form_template_id',
    'clearance_agent_id',
    'customer_id',
    'user_id',
    'completed_at',
    'closed_at',
  ];

  protected $appends = ['owner'];
  public function getOwnerAttribute()
  {
    return $this->customer_id ? $this->customer : $this->user;
  }

  public function formTemplate()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }

  public function clearanceAgent()
  {
    return $this->belongsTo(Customer::class, 'clearance_agent_id');
  }

  public function customer()
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function offers()
  {
    return $this->hasMany(Customs_Clearance_Offer::class, 'customs_clearance_id');
  }

  public function history()
  {
    return $this->hasMany(Customs_Clearance_History::class, 'customs_clearance_id');
  }
}
