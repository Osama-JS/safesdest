<?php

namespace App\Services;

use App\Models\Tag_Pricing;
use App\Models\Tag_Customers;
use App\Models\Pricing_Vehicle;
use App\Models\Pricing_Customer;
use App\Models\Pricing_Template;
use Illuminate\Support\Collection;

class PricingTemplateResolver
{
  public function resolveForSizes(int $templateId, Collection $sizeIds, ?int $customerId = null): array
  {
    $results = [];

    foreach ($sizeIds as $sizeId) {
      $query = Pricing_Template::query()
        ->where('form_template_id', $templateId)
        ->whereIn('id', function ($sub) use ($sizeId) {
          $sub->select('pricing_template_id')
            ->from('pricing_vehicle')
            ->where('vehicle_size_id', $sizeId);
        });

      if ($customerId) {
        // الأولوية: مرتبط مع customer أو tag
        $query->where(function ($q) use ($customerId) {
          $q->whereIn('id', function ($sub) use ($customerId) {
            $sub->select('pricing_template_id')
              ->from('pricing_customer')
              ->where('customer_id', $customerId);
          })->orWhereIn('id', function ($sub) use ($customerId) {
            $sub->select('pricing_template_id')
              ->from('tags_pricing')
              ->whereIn('tag_id', function ($tags) use ($customerId) {
                $tags->select('tag_id')
                  ->from('tags_customers')
                  ->where('customer_id', $customerId);
              });
          });
        });

        // إذا لم يُوجد أي قوالب مرتبطة بـ customer أو tag ننتقل إلى fallback
        if ($query->doesntExist()) {
          $query = Pricing_Template::query()
            ->where('form_template_id', $templateId)
            ->whereIn('id', function ($sub) use ($sizeId) {
              $sub->select('pricing_template_id')
                ->from('pricing_vehicle')
                ->where('vehicle_size_id', $sizeId);
            })
            ->whereNotIn('id', function ($sub) {
              $sub->select('pricing_template_id')
                ->from('pricing_customer');
            })
            ->whereNotIn('id', function ($sub) {
              $sub->select('pricing_template_id')
                ->from('tags_pricing');
            });
        }
      }

      $results[$sizeId] = $query->pluck('id');
    }

    // استدعاء الخدمه
    // $sizes = collect($req->input('vehicles'))->pluck('vehicle_size')->unique();

    // $pricingTemplatesPerSize = app(PricingTemplateResolver::class)
    //     ->resolveForSizes($req->template, $sizes, $req->customer ?? null);
    return $results;
  }
}
