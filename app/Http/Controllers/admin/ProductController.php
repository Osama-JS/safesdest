<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FunctionsController;
use App\Models\Product_Pricing;
use App\Models\Product_Vehicles;
use App\Models\Vehicle;
use App\Models\Vehicle_Size;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_products', ['only' => ['index', 'getData', 'show']]);
        $this->middleware('permission:store_products', ['only' => ['store']]);
        $this->middleware('permission:delete_products', ['only' => ['destroy']]);
        $this->middleware('permission:status_products', ['only' => ['chang_status']]);
        $this->middleware('permission:view_product_details', ['only' => ['show']]);
        $this->middleware('permission:vehicles_products', ['only' => ['storeVehicles', 'editVehicles', 'destroyVehicles']]);
        $this->middleware('permission:pricing_products', ['only' => ['storePricing', 'editPricing', 'destroyPricing']]);
    }
    public function index()
    {
        return view('admin.products.index');
    }

    public function getData(Request $request)
    {
        $columns = [
          1 => 'id',
          2 => 'image',
          3 => 'name',
          4 => 'code',
          5 => 'price',
          6 => 'min',
          7 => 'status',
          8 => 'created_at'
        ];

        $totalData = Product::count();
        $totalFiltered = $totalData;

        $limit  = $request->input('length');
        $start  = $request->input('start');
        $order  = $columns[$request->input('order.0.column')] ?? 'id';
        $dir    = $request->input('order.0.dir') ?? 'desc';

        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $query = Product::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        if (!empty($statusFilter)) {
            if ($statusFilter == 1) {
                $query->where('status', 1);
            } else {
                $query->where('status', 0);
            }
        }

        $totalFiltered = $query->count();


        $products = $query
          ->offset($start)
          ->limit($limit)
          ->orderBy($order, $dir)
          ->get();

        $data = [];
        $fakeId = $start;

        foreach ($products as $product) {
            $data[] = [
              'id'         => $product->id,
              'fake_id'    => ++$fakeId,
              'name'       => $product->name,
              'code'      => $product->code,
              'image'     => $product->image ? url($product->image) : '',
              'price'      => $product->price,
              'min'      => $product->minimum_order . ' ' . $product->unit,
              'increase'   => $product->increase . ' ' . $product->unit,
              'status'       => $product->status,
              'created_at' => $product->created_at->format('Y-m-d H:i'),
            ];
        }


        return response()->json([
          'draw'            => intval($request->input('draw')),
          'recordsTotal'    => $totalData,
          'recordsFiltered' => $totalFiltered,
          'code'            => 200,
          'data'            => $data,
          'summary' => [
            'total' => Product::count(),
            'total_active' => Product::where('status', 1)->count(),
            'total_blocked' => Product::where('status', 0)->count(),
          ]
        ]);
    }

    public function show($id, $name)
    {
        $data = Product::findOrFail($id);
        return view('admin.products.show', compact('data'));
    }

    public function chang_status(Request $req)
    {
        $find = Product::findOrFail($req->id);
        if (!$find) {
            return response()->json(['status' => 2, 'error' => __('Product not found')]);
        }
        $status = $find->status == 1 ? 0 : 1;
        $done = $find->update([
          'status' => $status,
        ]);
        if (!$done) {
            return response()->json(['status' => 2, 'error' => __('Error to change Product status')]);
        }
        return response()->json(['status' => 1, 'success' => __('Status changed successfully')]);
    }


    public function edit($id)
    {
        $data = Product::findOrFail($id);
        $data->img = $data->image ? url($data->image) : null;
        return response()->json($data);
    }

    public function store(Request $req)
    {
        $validator = Validator::make($req->all(), [
          'id' => 'nullable|exists:products,id',
          'name' => 'required|unique:products,name,' .  ($req->id ?? 0),
          'code' => 'required|unique:products,code,' .  ($req->id ?? 0),
          'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
          'min' => 'required|numeric|min:1',
          'increase' => 'required|numeric|min:1',
          'price' => 'required|numeric|min:0',
          'unit' => 'required|string|in:kg,ton',
          'contact_name' => 'nullable|string|max:255',
          'contact_phone' => 'nullable|string|max:255',
          'contact_email' => 'nullable|email|max:255',
          'latitude' => 'required|numeric',
          'longitude' => 'required|numeric',
          'description' => 'nullable|string',
          'notes' => 'nullable|string',
          'address' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }
        DB::beginTransaction();
        try {
            $data = [
              'name' => $req->name,
              'code' => $req->code,
              'unit' => $req->unit,
              'minimum_order' => $req->min,
              'increase' => $req->increase,
              'price' => $req->price,
              'contact_name' => $req->contact_name,
              'contact_phone' => $req->contact_phone,
              'contact_email' => $req->contact_email,
              'latitude' => $req->latitude,
              'longitude' => $req->longitude,
              'description' => $req->description,
              'notes' => $req->notes,
              'address' => $req->address,
            ];

            $oldImage = null;
            if ($req->filled('id')) {
                $find = Product::findOrFail($req->id);
                if (!$find) {
                    return response()->json(['status' => 2, 'error' => __('Can not find the selected Product')]);
                }
                $oldImage = $find->image;

                if ($req->hasFile('image')) {
                    $data['image'] = (new FunctionsController())->convert($req->image, 'products');
                }

                $done = $find->update($data);

            } else {
                if ($req->hasFile('image')) {
                    $data['image'] = (new FunctionsController())->convert($req->image, 'products');
                }

                $done = Product::create($data);

            }

            if (!$done) {
                DB::rollBack();
                return response()->json(['status' => 2, 'error' => __('Error: can not save the Product')]);
            }

            if ($oldImage && $req->hasFile('image')) {
                unlink($oldImage);
            }

            DB::commit();
            return response()->json(['status' => 1, 'success' => __('Product saved successfully')]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }



    public function destroy(Request $req)
    {
        DB::beginTransaction();
        try {
            $find = Product::with(['pricing', 'vehicles', 'sales'])->findOrFail($req->id);

            if (!$find) {
                return response()->json(['status' => 2, 'error' => __('Can not find the selected Product')]);
            }
            // التحقق من النشاطات المرتبطة
            $hasRelations =
              $find->pricing()->exists() ||
              $find->vehicles()->exists() ||
              $find->sales()->exists();

            if (!$hasRelations) {
                $find->forceDelete();
            } else {
                return response()->json(['status' => 2, 'error' => __('Can not Delete the selected Product')]);
            }

            DB::commit();
            return response()->json(['status' => 1, 'success' => __('Product deleted successfully')]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }



    // Vehicle Management Methods
    public function getVehiclesData(Request $request)
    {
        $productId = $request->input('product_id');

        $query = Product_Vehicles::with(['vehicle'])
            ->where('product_id', $productId);

        $totalData = $query->count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = 'id';
        $dir = 'desc';

        $vehicles = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        foreach ($vehicles as $key => $vehicle) {
            $data[] = [
                'id' => $vehicle->id,
                'fake_id' => $start + $key + 1,
                'vehicle_size' => $vehicle->vehicle->name . ' - ' . $vehicle->vehicle->type->name . ' - ' . $vehicle->vehicle->type->vehicle->name ?? 'N/A',
                'maximum_order' => $vehicle->maximum_order . ' ' . $vehicle->product->unit,
                'notes' => $vehicle->notes,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function getVehicles(Request $request)
    {
        $vehicles = Vehicle_Size::with('type.vehicle')->get();
        $vehicles = $vehicles->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->type->vehicle->name . ' - ' . $item->type->name . ' - '.$item->name,

            ];
        });
        return response()->json(['status' => 1, 'data' => $vehicles]);
    }
    public function storeVehicle(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'product_id' => 'required|exists:products,id',
            'vehicle_size_id' => 'required|exists:vehicle_sizes,id',
            'maximum_order' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 2, 'error' => $validator->errors()->first()]);
        }

        try {
            DB::beginTransaction();

            $data = [
                'product_id' => $req->product_id,
                'vehicle_size_id' => $req->vehicle_size_id,
                'maximum_order' => $req->maximum_order,
                'notes' => $req->notes,
            ];

            if ($req->filled('vehicle_id')) {
                $find = Product_Vehicles::findOrFail($req->vehicle_id);
                $done = $find->update($data);
                $message = __('Vehicle updated successfully');
            } else {
                // Check if vehicle already exists for this product
                $exists = Product_Vehicles::where('product_id', $req->product_id)
                    ->where('vehicle_size_id', $req->vehicle_size_id)
                    ->exists();

                if ($exists) {
                    return response()->json(['status' => 2, 'error' => __('This vehicle size is already added for this product')]);
                }

                $done = Product_Vehicles::create($data);
                $message = __('Vehicle added successfully');
            }

            if (!$done) {
                DB::rollBack();
                return response()->json(['status' => 2, 'error' => __('Error: can not save the Vehicle')]);
            }

            DB::commit();
            return response()->json(['status' => 1, 'success' => $message]);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    public function editVehicle($id)
    {
        try {
            $vehicle = Product_Vehicles::with(['vehicle'])->findOrFail($id);

            return response()->json([
                'status' => 1,
                'data' => [
                    'id' => $vehicle->id,
                    'product_id' => $vehicle->product_id,
                    'vehicle_size_id' => $vehicle->vehicle_size_id,
                    'vehicle_size' => $vehicle->vehicle->name ?? 'N/A',
                    'maximum_order' => $vehicle->maximum_order,
                    'notes' => $vehicle->notes,
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json(['status' => 2, 'error' => __('Vehicle not found')]);
        }
    }

    public function destroyVehicle($id)
    {
        try {
            DB::beginTransaction();

            $vehicle = Product_Vehicles::findOrFail($id);
            $vehicle->delete();

            DB::commit();
            return response()->json(['status' => 1, 'success' => __('Vehicle deleted successfully')]);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    // Pricing Management Methods
    public function getPricingData(Request $request)
    {
        $productId = $request->input('product_id');

        $query = Product_Pricing::with(['customer'])
            ->where('product_id', $productId);

        $totalData = $query->count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = 'id';
        $dir = 'desc';

        $pricing = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        foreach ($pricing as $key => $price) {
            $data[] = [
                'id' => $price->id,
                'fake_id' => $start + $key + 1,
                'customer' => $price->customer->name ?? 'N/A',
                'price' => $price->price,
                'notes' => $price->notes,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function storePricing(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'required|exists:customers,id',
            'price' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 2, 'error' => $validator->errors()->first()]);
        }

        try {
            DB::beginTransaction();

            $data = [
                'product_id' => $req->product_id,
                'customer_id' => $req->customer_id,
                'price' => $req->price,
                'notes' => $req->notes,
            ];

            if ($req->filled('pricing_id')) {
                $find = Product_Pricing::findOrFail($req->pricing_id);
                $done = $find->update($data);
                $message = __('Customer pricing updated successfully');
            } else {
                // Check if pricing already exists for this customer and product
                $exists = Product_Pricing::where('product_id', $req->product_id)
                    ->where('customer_id', $req->customer_id)
                    ->exists();

                if ($exists) {
                    return response()->json(['status' => 2, 'error' => __('Pricing for this customer already exists for this product')]);
                }

                $done = Product_Pricing::create($data);
                $message = __('Customer pricing added successfully');
            }

            if (!$done) {
                DB::rollBack();
                return response()->json(['status' => 2, 'error' => __('Error: can not save the Pricing')]);
            }

            DB::commit();
            return response()->json(['status' => 1, 'success' => $message]);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    public function editPricing($id)
    {
        try {
            $pricing = Product_Pricing::with(['customer'])->findOrFail($id);

            return response()->json([
                'status' => 1,
                'data' => [
                    'id' => $pricing->id,
                    'product_id' => $pricing->product_id,
                    'customer_id' => $pricing->customer_id,
                    'customer' => $pricing->customer->name ?? 'N/A',
                    'price' => $pricing->price,
                    'notes' => $pricing->notes,
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json(['status' => 2, 'error' => __('Pricing not found')]);
        }
    }

    public function destroyPricing($id)
    {
        try {
            DB::beginTransaction();

            $pricing = Product_Pricing::findOrFail($id);
            $pricing->delete();

            DB::commit();
            return response()->json(['status' => 1, 'success' => __('Customer pricing deleted successfully')]);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

}
