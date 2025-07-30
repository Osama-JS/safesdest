<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customs_Clearance_Offer extends Model
{
  protected $table = 'customs_clearance_offers';
  protected $fillable = [
    'price',
    'description',
    'accepted',
    'additional_data',
    'customs_clearance_id',
    'clearance_agent_id',
    'form_template_id',
  ];
  public function customsClearance()
  {
    return $this->belongsTo(Customs_Clearance::class, 'customs_clearance_id');
  }
  public function broker()
  {
    return $this->belongsTo(Customer::class, 'clearance_agent_id');
  }
  public function formTemplate()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }
}
