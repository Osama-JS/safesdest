<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing_Template extends Model
{
  protected $table = 'pricing_templates';
  protected $fillable = [
    'name',
    'decimal_places',
    'base_fare',
    'base_waiting_time',
    'waiting_fare',

    'base_distance',
    'distance_fare',

    'discount_percentage',
    'vat_commission',
    'service_tax_commission',
    'all_customer',
    'form_template_id'
  ];

  public function form_template()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }
  public function pricing_methods()
  {
    return $this->hasMany(Pricing::class, 'pricing_template_id');
  }
  public function customers()
  {
    return $this->belongsToMany(Customer::class, 'pricing_customer', 'pricing_template_id', 'customer_id');
  }
  public function tags()
  {
    return $this->belongsToMany(Tag::class, 'tags_pricing', 'pricing_template_id', 'tag_id');
  }

  public function sizes()
  {
    return $this->belongsToMany(Vehicle_Size::class, 'pricing_vehicle', 'pricing_template_id', 'vehicle_size_id');
  }


  public function fields()
  {
    return $this->hasMany(Pricing_Field::class, 'pricing_id');
  }

  public function geoFences()
  {
    return $this->hasMany(Pricing_GeoFence::class, 'pricing_template_id');
  }



  public function scopeAvailableForCustomer($query, $templateId, $customerId = null, $vehicleSizes = [])
  {
    // أولاً: فلترة حسب form_template_id
    $query->where('form_template_id', $templateId);

    // ثانياً: فلترة حسب vehicle sizes إن وُجدت
    if (!empty($vehicleSizes)) {
      $query->whereIn('id', function ($sub) use ($vehicleSizes) {
        $sub->select('pricing_template_id')
          ->from('pricing_vehicle')
          ->whereIn('vehicle_size_id', $vehicleSizes);
      });
    }

    // ثالثاً: فلترة حسب customer/tag إن وُجد customerId
    if ($customerId) {
      // استخراج ارتباطات customer
      $customerTemplateIds = Pricing_Customer::where('customer_id', $customerId)
        ->pluck('pricing_template_id');

      // استخراج tag المرتبطة بالعميل
      $tagIds = Tag_Customers::where('customer_id', $customerId)
        ->pluck('tag_id');

      $tagTemplateIds = Tag_Pricing::whereIn('tag_id', $tagIds)
        ->pluck('pricing_template_id');

      $matchedIds = $customerTemplateIds->merge($tagTemplateIds)->unique();

      if ($matchedIds->count() > 0) {
        // ✅ يوجد ارتباطات → نُرجع فقط المرتبط
        $query->whereIn('id', $matchedIds);
      } else {
        // ❌ لا يوجد ارتباطات → نُرجع فقط غير المرتبط مع أي customer/tag
        $query->whereNotIn('id', function ($sub) {
          $sub->select('pricing_template_id')->from('pricing_customer');
        })->whereNotIn('id', function ($sub) {
          $sub->select('pricing_template_id')->from('tags_pricing');
        });
      }
    }

    return $query;
  }
}
