<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Customs_Clearance_History extends Model
{
    use LogsActivity;

  protected $table = 'customs_clearance_history';
  protected $fillable = [
    'action_type',
    'description',
    'file_path',
    'file_type',
    'ip',
    'customs_clearance_id',
    'clearance_agent_id',
    'user_id',
  ];

  public function customsClearance()
  {
    return $this->belongsTo(Customs_Clearance::class, 'customs_clearance_id');
  }

  public function clearanceAgent()
  {
    return $this->belongsTo(Customer::class, 'clearance_agent_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
  public function getFilePathAttribute($value)
  {
    return asset('storage/' . $value);
  }
}
