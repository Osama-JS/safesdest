<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Form_Template extends Model
{
    use LogsActivity;

  protected $table = 'form_templates';
  protected $fillable = ['name', 'description'];

  public function fields()
  {
    return $this->hasMany(Form_Field::class, 'form_template_id')->orderBy('order', 'ASC');
  }

  public function pricing_templates()
  {
    return $this->hasMany(Pricing_Template::class, 'form_template_id');
  }

  public function tasks()
  {
    return $this->hasMany(Task::class, 'form_template_id');
  }

  public function customers()
  {
    return $this->hasMany(Customer::class, 'form_template_id');
  }

  public function drivers()
  {
    return $this->hasMany(Driver::class, 'form_template_id');
  }

  public function users()
  {
    return $this->hasMany(User::class, 'form_template_id');
  }


  public function customsClearances()
  {
    return $this->hasMany(Customs_Clearance::class, 'form_template_id');
  }


  public function clearancePricingTemplates()
  {
    return $this->hasMany(Clearance_Pricing_Template::class, 'form_template_id');
  }

  /**
   * Get usage statistics for this template
   *
   * @return array
   */
  public function getUsageStatsAttribute()
  {
    return [
      'tasks' => $this->tasks()->count(),
      'customers' => $this->customers()->count(),
      'drivers' => $this->drivers()->count(),
      'users' => $this->users()->count(),
      'customsClearances' => $this->customsClearances()->count(),
    ];
  }

  /**
   * Get formatted usage summary for display
   *
   * @return string
   */
  public function getUsageSummaryAttribute()
  {
    $stats = $this->usage_stats;
    $summary = [];

    if ($stats['tasks'] > 0) {
      $summary[] = __('Tasks') . ': ' . $stats['tasks'];
    }
    if ($stats['customers'] > 0) {
      $summary[] = __('Customers') . ': ' . $stats['customers'];
    }
    if ($stats['drivers'] > 0) {
      $summary[] = __('Drivers') . ': ' . $stats['drivers'];
    }
    if ($stats['users'] > 0) {
      $summary[] = __('Users') . ': ' . $stats['users'];
    }
    if ($stats['customsClearances'] > 0) {
      $summary[] = __('Customs Clearances') . ': ' . $stats['customsClearances'];
    }

    return empty($summary) ? __('No usage') : implode('<br>', $summary);
  }

  /**
   * Get total usage count
   *
   * @return int
   */
  public function getTotalUsageAttribute()
  {
    $stats = $this->usage_stats;
    return $stats['tasks'] + $stats['customers'] + $stats['drivers'] + $stats['users'] + $stats['customsClearances'];
  }
}
