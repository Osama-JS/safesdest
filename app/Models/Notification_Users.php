<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Notification_Users extends Model
{
    use LogsActivity;

  protected $table = 'notifications_users';
  protected $fillable = [
    'notification_id',
    'user_id',
    'status',
    'is_shown',
    'shown_at',
  ];

  protected $casts = [
    'status'   => 'boolean',
    'is_shown' => 'boolean',
    'shown_at' => 'datetime',
  ];

  public function notification()
  {
    return $this->belongsTo(Notification::class, 'notification_id');
  }
  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
