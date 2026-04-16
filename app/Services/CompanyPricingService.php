<?php

namespace App\Services;

use App\Models\Company_Pricing_Config;
use App\Models\Company_Warehouse;
use App\Models\Company_End_Client;
use App\Models\Company_Route_Pricing;
use App\Models\Company_Route_Pricing_Vehicle;
use App\Models\Company_Client_Pricing_Vehicle;
use Exception;

class CompanyPricingService
{
    /**
     * Resolve the base price based on the 5-layer priority logic.
     */
    public function resolveBasePrice($companyId, $warehouseId, $endClientId, $vehicleSizeId): array
    {
        // 1. Highest Priority: Client specific price for a specific vehicle size
        $clientVehiclePrice = Company_Client_Pricing_Vehicle::where([
            'company_id'      => $companyId,
            'warehouse_id'    => $warehouseId,
            'end_client_id'   => $endClientId,
            'vehicle_size_id' => $vehicleSizeId,
        ])->first();

        if ($clientVehiclePrice) {
            return [
                'base_price'   => $clientVehiclePrice->price,
                'pricing_rule' => 'client_vehicle',
            ];
        }

        // 2. Route Level: Route (Warehouse -> Province) specific price for a specific vehicle size
        $endClient = Company_End_Client::findOrFail($endClientId);
        $routePricing = Company_Route_Pricing::where([
            'warehouse_id'           => $warehouseId,
            'destination_province_id' => $endClient->province_id,
        ])->first();

        if ($routePricing) {
            $routeVehiclePrice = Company_Route_Pricing_Vehicle::where([
                'route_pricing_id' => $routePricing->id,
                'vehicle_size_id'  => $vehicleSizeId,
            ])->first();

            if ($routeVehiclePrice) {
                return [
                    'base_price'   => $routeVehiclePrice->price,
                    'pricing_rule' => 'route_vehicle',
                ];
            }

            // 3. Route Level: Default price for the route (no vehicle filter)
            if ($routePricing->default_price !== null) {
                return [
                    'base_price'   => $routePricing->default_price,
                    'pricing_rule' => 'route_default',
                ];
            }
        }

        // 4. No price found
        throw new Exception("لم يتم العثور على تسعير مطابق لهذا المسار أو نوع المركبة. يرجى مراجعة إعدادات التسعير.");
    }

    /**
     * Calculate final price including commission and VAT.
     * Accepts either a scalar base price or an array from resolveBasePrice().
     */
    public function calculateFinalPrice($companyId, $basePrice): array
    {
        // Support array input from resolveBasePrice()
        $pricingRule = null;
        if (is_array($basePrice)) {
            $pricingRule = $basePrice['pricing_rule'] ?? null;
            $basePrice   = $basePrice['base_price'];
        }

        $config = Company_Pricing_Config::where('company_id', $companyId)->first();

        if (!$config) {
            $commission    = 0;
            $vatPercentage = 15.00;
        } else {
            $vatPercentage = $config->vat_percentage;
            if ($config->commission_type === 'percentage') {
                $commission = ($basePrice * $config->commission_value) / 100;
            } else {
                $commission = $config->commission_value;
            }
        }

        $priceWithCommission = $basePrice + $commission;
        $vatAmount           = ($priceWithCommission * $vatPercentage) / 100;
        $totalPrice          = $priceWithCommission + $vatAmount;

        return [
            'base_price'     => round($basePrice, 2),
            'commission'     => round($commission, 2),
            'vat_amount'     => round($vatAmount, 2),
            'total_price'    => round($totalPrice, 2),
            'vat_percentage' => $vatPercentage,
            'pricing_rule'   => $pricingRule,
        ];
    }
}
