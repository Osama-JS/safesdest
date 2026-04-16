<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Company_Warehouse;
use App\Models\Company_End_Client;
use App\Models\Company_Province;
use App\Models\Company_Route_Pricing;
use App\Models\Company_Client_Pricing_Vehicle;
use App\Models\Vehicle_Size;
use App\Imports\CompanyEndClientsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class CompanyManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_customers');
    }

    // --- Companies Management ---

    public function companiesIndex()
    {
        $stats = [
            'total_companies'   => Customer::where('is_company', 1)->count(),
            'total_warehouses'  => Company_Warehouse::where('is_active', 1)->count(),
            'total_end_clients' => Company_End_Client::where('is_active', 1)->count(),
        ];
        return view('admin.b2b.companies.index', compact('stats'));
    }

    public function getCompaniesData(Request $request)
    {
        // Similar to CustomersController@getData but specifically for is_company = 1
        $query = Customer::where('is_company', 1);

        if ($request->search && isset($request->search['value']) && $request->search['value'] != '') {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $totalData = $query->count();
        $items = $query->offset($request->start)->limit($request->length)->orderBy('id', 'desc')->get();

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'phone' => $item->phone_code . $item->phone,
                'warehouses_count' => $item->warehouses()->count(),
                'clients_count' => $item->endClients()->count(),
                'status' => $item->status,
            ];
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalData,
            'data' => $data,
        ]);
    }

    public function provincesIndex()
    {
        $stats = [
            'total_provinces'   => Company_Province::count(),
            'active_provinces'  => Company_Province::where('is_active', 1)->count(),
            'inactive_provinces'=> Company_Province::where('is_active', 0)->count(),
        ];
        return view('admin.b2b.provinces.index', compact('stats'));
    }

    public function getProvincesData(Request $request)
    {
        $query = Company_Province::query();

        if ($request->search && isset($request->search['value']) && $request->search['value'] != '') {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%")
                  ->orWhere('region', 'LIKE', "%{$search}%");
            });
        }

        $totalData = $query->count();
        $items = $query->offset($request->start)->limit($request->length)->orderBy('name_ar', 'asc')->get();

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'id' => $item->id,
                'name_ar' => $item->name_ar,
                'name_en' => $item->name_en,
                'region' => $item->region ?? 'N/A',
                'status' => $item->is_active ? 'active' : 'inactive',
            ];
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalData,
            'data' => $data,
        ]);
    }

    public function storeProvince(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_ar' => 'required|string|max:100|unique:company_provinces,name_ar,' . ($request->id ?? 'NULL'),
            'name_en' => 'required|string|max:100|unique:company_provinces,name_en,' . ($request->id ?? 'NULL'),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        $province = Company_Province::updateOrCreate(
            ['id' => $request->id],
            $request->only(['name_ar', 'name_en', 'region', 'is_active'])
        );

        return response()->json(['status' => 1, 'success' => __('Province saved successfully')]);
    }

    public function getProvince($id)
    {
        return response()->json(Company_Province::findOrFail($id));
    }

    public function deleteProvince($id)
    {
        $province = Company_Province::findOrFail($id);
        $province->delete();
        return response()->json(['status' => 1, 'success' => __('Province deleted successfully')]);
    }

    // --- Warehouses Management ---

    public function warehousesIndex(Request $request)
    {
        $companies = Customer::where('is_company', 1)->get();
        $provinces = Company_Province::where('is_active', 1)->get();
        
        $stats = [
            'total_warehouses'    => 0,
            'active_warehouses'   => 0,
            'inactive_warehouses' => 0,
        ];

        if ($request->has('company_id') && $request->company_id) {
            $stats = [
                'total_warehouses'    => Company_Warehouse::where('company_id', $request->company_id)->count(),
                'active_warehouses'   => Company_Warehouse::where('company_id', $request->company_id)->where('is_active', 1)->count(),
                'inactive_warehouses' => Company_Warehouse::where('company_id', $request->company_id)->where('is_active', 0)->count(),
            ];
        }

        return view('admin.b2b.warehouses.index', compact('companies', 'provinces', 'stats'));
    }

    public function getWarehousesData(Request $request)
    {
        if (!$request->company_id) {
            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $query = Company_Warehouse::with(['company', 'province'])
            ->where('company_id', $request->company_id);

        // --- Filtering ---
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status == 'active' ? 1 : 0);
        }

        // --- Searching ---
        $search = $request->q ?? ($request->search['value'] ?? null);
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%")
                  ->orWhereHas('province', function($pq) use ($search) {
                      $pq->where('name_ar', 'LIKE', "%{$search}%")
                        ->orWhere('name_en', 'LIKE', "%{$search}%");
                  });
            });
        }

        $totalData = Company_Warehouse::where('company_id', $request->company_id)->count();
        $filteredData = $query->count();
        
        $items = $query->offset($request->start)
            ->limit($request->length)
            ->orderBy('id', 'desc')
            ->get();

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'id' => $item->id,
                'company_name' => $item->company->name ?? 'N/A',
                'name' => $item->name,
                'province' => $item->province->name_ar ?? 'N/A',
                'address' => $item->address,
                'status' => $item->is_active ? 'active' : 'inactive',
            ];
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $filteredData,
            'data' => $data,
        ]);
    }

    public function storeWarehouse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'province_id' => 'required|exists:company_provinces,id',
            'address' => 'required|string',
            'contact_name' => 'required|string|max:191',
            'contact_phone' => 'required|string|max:30',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'pricing' => 'nullable|array', // Layer 4: Route pricing
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {
            $warehouse = Company_Warehouse::updateOrCreate(
                ['id' => $request->id],
                $request->only(['company_id', 'name', 'province_id', 'address', 'latitude', 'longitude', 'contact_name', 'contact_phone'])
            );

            // Handle Specific Route Pricing (Layer 4)
            if ($request->pricing) {
                foreach ($request->pricing as $destProvinceId => $price) {
                    if ($price > 0) {
                        Company_Route_Pricing::updateOrCreate(
                            [
                                'company_id' => $request->company_id,
                                'warehouse_id' => $warehouse->id,
                                'destination_province_id' => $destProvinceId
                            ],
                            ['default_price' => $price, 'is_active' => 1]
                        );
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 1, 'success' => __('Warehouse saved successfully')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    public function getWarehouse($id)
    {
        $warehouse = Company_Warehouse::with('routePricings')->findOrFail($id);
        return response()->json($warehouse);
    }

    public function deleteWarehouse($id)
    {
        $warehouse = Company_Warehouse::findOrFail($id);
        $warehouse->delete();
        return response()->json(['status' => 1, 'success' => __('Warehouse deleted')]);
    }

    // --- End Clients Management ---

    public function endClientsIndex(Request $request)
    {
        $companies = Customer::where('is_company', 1)->get();
        $provinces = Company_Province::where('is_active', 1)->get();
        $vehicleSizes = Vehicle_Size::all();

        $stats = null;
        if ($request->has('company_id') && $request->company_id) {
            $stats = [
                'total_clients'    => Company_End_Client::where('company_id', $request->company_id)->count(),
                'active_clients'   => Company_End_Client::where('company_id', $request->company_id)->where('is_active', 1)->count(),
                'inactive_clients' => Company_End_Client::where('company_id', $request->company_id)->where('is_active', 0)->count(),
            ];
        }

        return view('admin.b2b.end-clients.index', compact('companies', 'provinces', 'vehicleSizes', 'stats'));
    }

    public function getEndClientsData(Request $request)
    {
        if (!$request->company_id) {
            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $query = Company_End_Client::with(['company', 'province'])
            ->where('company_id', $request->company_id);

        // --- Filtering ---
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status == 'active' ? 1 : 0);
        }

        // --- Searching ---
        $search = $request->q ?? ($request->search['value'] ?? null);
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('client_code', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhereHas('province', function($pq) use ($search) {
                      $pq->where('name_ar', 'LIKE', "%{$search}%")
                        ->orWhere('name_en', 'LIKE', "%{$search}%");
                  });
            });
        }

        $totalData = Company_End_Client::where('company_id', $request->company_id)->count();
        $filteredData = $query->count();
        
        $items = $query->offset($request->start)
            ->limit($request->length)
            ->orderBy('id', 'desc')
            ->get();

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'id' => $item->id,
                'company_name' => $item->company->name ?? 'N/A',
                'name' => $item->name,
                'client_code' => $item->client_code,
                'province' => $item->province->name_ar ?? 'N/A',
                'phone' => $item->phone,
                'status' => $item->is_active ? 'active' : 'inactive',
            ];
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $filteredData,
            'data' => $data,
        ]);
    }

    public function storeEndClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'client_code' => 'nullable|string|max:100',
            'province_id' => 'required|exists:company_provinces,id',
            'phone' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'pricing' => 'nullable|array', // Layer 5: Client-Warehouse-Vehicle pricing
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {
            $client = Company_End_Client::updateOrCreate(
                ['id' => $request->id],
                $request->only(['company_id', 'client_code', 'name', 'province_id', 'phone', 'phone_2', 'address', 'latitude', 'longitude', 'notes'])
            );

            // Handle Specific Client Pricing (Layer 1 - Specific per Warehouse/Vehicle)
            if ($request->pricing) {
                // Format: pricing[warehouse_id][vehicle_id] = price
                foreach ($request->pricing as $warehouseId => $vehicles) {
                    foreach ($vehicles as $vehicleId => $price) {
                        if ($price > 0) {
                            Company_Client_Pricing_Vehicle::updateOrCreate(
                                [
                                    'company_id' => $request->company_id,
                                    'warehouse_id' => $warehouseId,
                                    'end_client_id' => $client->id,
                                    'vehicle_size_id' => $vehicleId
                                ],
                                ['price' => $price]
                            );
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 1, 'success' => __('End Client saved successfully')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    public function getEndClient($id)
    {
        $client = Company_End_Client::findOrFail($id);
        // Load associated pricing
        $pricing = Company_Client_Pricing_Vehicle::where('end_client_id', $id)->get();
        return response()->json([
            'client' => $client,
            'pricing' => $pricing
        ]);
    }

    public function deleteEndClient($id)
    {
        $client = Company_End_Client::findOrFail($id);
        $client->delete();
        return response()->json(['status' => 1, 'success' => __('End Client deleted')]);
    }

    /**
     * Get warehouses for a specific company (AJAX helper)
     */
    public function getWarehouses($companyId)
    {
        $warehouses = Company_Warehouse::where('company_id', $companyId)
            ->where('is_active', 1)
            ->get();
        return response()->json($warehouses);
    }

    /**
     * Get end clients for a specific company (AJAX helper)
     */
    public function getEndClients($companyId)
    {
        $clients = Company_End_Client::where('company_id', $companyId)
            ->where('is_active', 1)
            ->get();
        return response()->json($clients);
    }

    /**
     * Import End Clients from Excel file
     */
    public function importEndClients(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:customers,id',
            'file'       => 'required|file|mimes:xlsx,xls,csv|max:51200', // 50MB
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        try {
            $countBefore = \App\Models\Company_End_Client::where('company_id', $request->company_id)->count();

            $import = new CompanyEndClientsImport($request->company_id);
            Excel::import($import, $request->file('file'));

            $countAfter = \App\Models\Company_End_Client::where('company_id', $request->company_id)->count();
            $added = $countAfter - $countBefore;

            return response()->json([
                'status'  => 1,
                'success' => __('Import completed successfully. :added new clients added (total: :total).', [
                    'added' => $added,
                    'total' => $countAfter,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }
}
