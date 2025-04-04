<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_Teams extends Model
{
  protected $table = 'user_has_teams';
  protected $fillable = [
    'user_id',
    'team_id'
  ];

  public $timestamps = false;
  public function team()
  {
    return $this->belongsTo(Team::class, 'team_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
