<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Company_Warehouse;
use App\Models\Company_Province;
use App\Models\Company_Route_Pricing;
use App\Models\Company_Route_Pricing_Vehicle;
use App\Models\Company_Client_Pricing_Vehicle;
use App\Models\Company_Pricing_Config;
use App\Models\Company_End_Client;
use App\Services\CompanyPricingService;
use App\Models\Vehicle_Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompanyRoutePricingController extends Controller
{
    protected CompanyPricingService $pricingService;

    public function __construct(CompanyPricingService $pricingService)
    {
        $this->middleware('permission:view_customers');
        $this->pricingService = $pricingService;
    }

    // ─────────────────────────────────────────────
    // PRICING MATRIX (Route Pricing)
    // ─────────────────────────────────────────────

    /**
     * Show the pricing matrix for a company
     */
    public function index($companyId)
    {
        $company   = Customer::where('is_company', 1)->findOrFail($companyId);
        $warehouses = Company_Warehouse::where('company_id', $companyId)->where('is_active', 1)->get();
        $provinces  = Company_Province::where('is_active', 1)->orderBy('name_ar')->get();
        $vehicleSizes = Vehicle_Size::all();

        // Fetch existing route pricing matrix
        $matrix = Company_Route_Pricing::where('company_id', $companyId)
            ->with('vehiclePrices')
            ->get()
            ->keyBy(fn($r) => $r->warehouse_id . '_' . $r->destination_province_id);

        return view('admin.b2b.pricing.index', compact(
            'company', 'warehouses', 'provinces', 'vehicleSizes', 'matrix'
        ));
    }

    /**
     * Store or update a route pricing entry (with vehicle prices)
     */
    public function storeRoute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id'              => 'required|exists:customers,id',
            'warehouse_id'            => 'required|exists:company_warehouses,id',
            'destination_province_id' => 'required|exists:company_provinces,id',
            'default_price'           => 'nullable|numeric|min:0',
            'vehicle_prices'          => 'nullable|array',
            'vehicle_prices.*.vehicle_size_id' => 'required|exists:vehicle_sizes,id',
            'vehicle_prices.*.price'           => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {
            $route = Company_Route_Pricing::updateOrCreate(
                [
                    'company_id'              => $request->company_id,
                    'warehouse_id'            => $request->warehouse_id,
                    'destination_province_id' => $request->destination_province_id,
                ],
                [
                    'default_price' => $request->default_price,
                    'is_active'     => 1,
                ]
            );

            // Handle vehicle-specific prices
            if ($request->has('vehicle_prices')) {
                foreach ($request->vehicle_prices as $vp) {
                    if ($vp['price'] > 0) {
                        Company_Route_Pricing_Vehicle::updateOrCreate(
                            [
                                'route_pricing_id' => $route->id,
                                'vehicle_size_id'  => $vp['vehicle_size_id'],
                            ],
                            ['price' => $vp['price']]
                        );
                    }
                }
            }

            DB::commit();
            return response()->json([
                'status'  => 1,
                'success' => __('Route pricing saved successfully'),
                'id'      => $route->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Get all route pricing for a company as JSON (for DataTables)
     */
    public function getRoutes(Request $request, $companyId)
    {
        $routes = Company_Route_Pricing::with(['warehouse', 'destinationProvince', 'vehiclePrices.vehicleSize'])
            ->where('company_id', $companyId)
            ->get();

        return response()->json($routes);
    }

    /**
     * Get a single route pricing record with vehicle prices
     */
    public function getRoute($id)
    {
        $route = Company_Route_Pricing::with('vehiclePrices')->findOrFail($id);
        return response()->json($route);
    }

    /**
     * Delete a route pricing record (and its vehicle pivot records via cascade)
     */
    public function deleteRoute($id)
    {
        $route = Company_Route_Pricing::findOrFail($id);
        $route->delete();
        return response()->json(['status' => 1, 'success' => __('Route pricing deleted')]);
    }

    // ─────────────────────────────────────────────
    // COMPANY PRICING CONFIG (Commission / VAT)
    // ─────────────────────────────────────────────

    /**
     * Show the config form for a company
     */
    public function configIndex($companyId)
    {
        $company = Customer::where('is_company', 1)->findOrFail($companyId);
        $config  = Company_Pricing_Config::firstOrNew(
            ['company_id' => $companyId],
            ['commission_type' => 'percentage', 'commission_value' => 0, 'vat_percentage' => 15.00]
        );
        return view('admin.b2b.pricing.config', compact('company', 'config'));
    }

    /**
     * Save/Update the pricing config for a company
     */
    public function saveConfig(Request $request, $companyId)
    {
        $validator = Validator::make($request->all(), [
            'commission_type'  => 'required|in:fixed,percentage',
            'commission_value' => 'required|numeric|min:0',
            'vat_percentage'   => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        Company_Pricing_Config::updateOrCreate(
            ['company_id' => $companyId],
            [
                'commission_type'  => $request->commission_type,
                'commission_value' => $request->commission_value,
                'vat_percentage'   => $request->vat_percentage,
            ]
        );

        return response()->json(['status' => 1, 'success' => __('Company pricing config saved successfully')]);
    }

    // ─────────────────────────────────────────────
    // RESOLVE PRICE (AJAX from Task Creation)
    // ─────────────────────────────────────────────

    /**
     * Resolve price for a given warehouse + end client + vehicle combination
     */
    public function resolvePrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id'         => 'required|exists:customers,id',
            'warehouse_id'       => 'required|exists:company_warehouses,id',
            'end_client_id'      => 'required|exists:company_end_clients,id',
            'vehicle_size_id'    => 'required|exists:vehicle_sizes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        try {
            $basePrice   = $this->pricingService->resolveBasePrice(
                $request->company_id,
                $request->warehouse_id,
                $request->end_client_id,
                $request->vehicle_size_id
            );
            $finalPricing = $this->pricingService->calculateFinalPrice($request->company_id, $basePrice);

            return response()->json([
                'status' => 1,
                'data'   => $finalPricing,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Check if a customer is a B2B company (AJAX helper for task form)
     */
    public function isCompany($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['is_company' => false]);
        }
        return response()->json([
            'is_company' => (bool) $customer->is_company,
        ]);
    }
}
