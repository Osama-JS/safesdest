<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class Customs_Clearance extends Model
{
  protected $table = 'customs_clearance';
  protected $fillable = [
    'status',
    'total_price',
    'included',
    'commission_type',
    'commission',
    'payment_method',
    'payment_status',
    'payment_paid',
    'payment_pending_amount',
    'payment_id',
    'closed',
    'public',
    'notes',
    'delivery_note',
    'additional_data',
    'pricing_history',
    'pricing_details',
    'form_template_id',
    'pricing_id',
    'clearance_agent_id',
    'customer_id',
    'user_id',
    'completed_at',
    'closed_at',
  ];

  protected $appends = ['owner'];

  protected $casts = [
    'additional_data' => 'array',
    'pricing_history'  => 'array',
    'pricing_details'  => 'array',
  ];


  public function getOwnerAttribute()
  {
    return $this->customer_id ? $this->customer : $this->user;
  }

  public function formTemplate()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }
  public function pricing()
  {
    return $this->belongsTo(Clearance_Pricing_Template::class, 'pricing_id');
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

  public function payments()
  {
    return $this->hasMany(Clearance_Payments::class, 'customs_clearance_id');
  }

  public function transactions()
  {
    return $this->hasMany(Clearance_Transactions::class, 'reference_id');
  }

  public function walletTransactions()
  {
    return $this->hasMany(Wallet_Transaction::class, 'clearance_id');
  }

  public function deleteCompletely()
  {
    DB::transaction(function () {
      // حذف الصور المرتبطة بالـ transactions إن وُجدت
      foreach ($this->transactions as $transaction) {
        if ($transaction->receipt_image) {
          unlink($transaction->receipt_image);
        }
        $transaction->delete();
      }

      $this->offers()->delete();
      $this->history()->delete();
      $this->payments()->delete();
      $this->walletTransactions()->delete();

      $this->delete();
    });
  }
}
