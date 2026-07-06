<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Clearance_Pricing_Template extends Model
{
    use LogsActivity;

  protected $table = "clearance_pricing_template";
  protected $fillable = [
    'name',
    'decimal_places',
    'vat_commission',
    'service_commission',
    'service_commission_status',
    'service_commission_type',
    'all_customer',
    'form_template_id'
  ];

  public function formTemplate()
  {
    return $this->belongsTo(Form_Template::class, 'form_template_id');
  }

  public function customers()
  {
    return $this->belongsToMany(Customer::class, 'clearance_pricing_customer', 'clearance_pricing_template_id', 'customer_id');
  }
  public function tags()
  {
    return $this->belongsToMany(Tag::class, 'tags_clearance_pricing', 'clearance_pricing_template_id', 'tag_id');
  }

  public function scopeAvailableForCustomer($query, $templateId, $customerId = null)
  {
    // أولاً: فلترة حسب form_template_id
    $query->where('form_template_id', $templateId);



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
