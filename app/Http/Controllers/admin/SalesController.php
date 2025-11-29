<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sales_invoice;
use App\Models\Sales_invoice_detail;
use App\Models\Product;
use App\Models\Product_Vehicles;
use App\Models\Customer;
use App\Models\Vehicle_Size;
use App\Models\Task;
use App\Models\Form_Template;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalesController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:view_sales', ['only' => ['index', 'getData']]);
    //     $this->middleware('permission:create_sales', ['only' => ['create', 'store']]);
    //     $this->middleware('permission:show_sales', ['only' => ['show']]);
    //     $this->middleware('permission:edit_sales', ['only' => ['updateStatus']]);
    // }

    public function index()
    {
        return view('admin.sales.index');
    }

    public function getData(Request $request)
    {
        $columns = [
            1 => 'id',
            2 => 'invoice_number',
            3 => 'customer_id',
            4 => 'final_total',
            5 => 'status',
            6 => 'created_by',
            7 => 'created_at'
        ];

        $totalData = Sales_invoice::count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        $search = $request->input('search.value');

        $query = Sales_invoice::with(['customer', 'creator']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $totalFiltered = $query->count();

        $invoices = $query
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        foreach ($invoices as $invoice) {
            $statusColors = [
                'pending' => 'warning',
                'paid' => 'success',
                'cancelled' => 'danger',
                'refunded' => 'info',
            ];

            $data[] = [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer ? $invoice->customer->name : 'N/A',
                'final_total' => number_format($invoice->final_total, 2),
                'status' => '<span class="badge badge-' . ($statusColors[$invoice->status] ?? 'secondary') . '">' . ucfirst($invoice->status) . '</span>',
                'creator_name' => $invoice->creator ? $invoice->creator->name : 'N/A',
                'created_at' => $invoice->created_at->format('Y-m-d H:i'),
                'action' => '<a href="' . route('sales.show', $invoice->id) . '" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>'
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }

    /**
     * Get all products for modal display
     */
    public function getProducts()
    {
        $products = Product::where('status', 1)
            ->select('id', 'name', 'image', 'minimum_order', 'price', 'unit')
            ->get();

        return response()->json(['status' => 1, 'data' => $products]);
    }

    /**
     * Get vehicles matching product and quantity
     */
    public function getMatchingVehicles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity;

        // Get vehicles that match this product and can handle the quantity
        $matchingVehicles = Product_Vehicles::where('product_id', $product->id)
            ->where('maximum_order', '>=', $quantity)
            ->with('vehicle')
            ->get()
            ->map(function($pv) {
                $vehicleSize = $pv->vehicle; // This is actually Vehicle_Size
                if (!$vehicleSize) {
                    return null;
                }

                return [
                    'id' => $pv->vehicle_size_id,
                    'name' => $vehicleSize->name ?? 'Vehicle ' . $pv->vehicle_size_id,
                    'max_capacity' => $pv->maximum_order
                ];
            })
            ->filter() // Remove null values
            ->values(); // Re-index array

        return response()->json(['status' => 1, 'data' => $matchingVehicles]);
    }

    /**
     * Calculate price for product and quantity
     */
    public function calculatePrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity;
        $customerId = $request->customer_id;

        // Check for customer-specific pricing
        $unitPrice = $product->price;
        if ($customerId) {
            $customPricing = $product->pricing()->where('customer_id', $customerId)->first();
            if ($customPricing) {
                $unitPrice = $customPricing->price;
            }
        }

        $total = $unitPrice * $quantity;

        return response()->json([
            'status' => 1,
            'data' => [
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'total' => $total,
                'formatted_total' => number_format($total, 2)
            ]
        ]);
    }

    /**
     * Store new sales order with delivery task
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
            'vehicle_size_id' => 'required|exists:vehicle_sizes,id',
            'delivery_pricing_type' => 'required|in:manual,auto,ad',
            'delivery_lat' => 'required|numeric',
            'delivery_lng' => 'required|numeric',
            'delivery_address' => 'required|string',
            'manual_delivery_price' => 'required_if:delivery_pricing_type,manual|nullable|numeric|min:0',
            'ad_min_price' => 'required_if:delivery_pricing_type,ad|nullable|numeric|min:0',
            'ad_max_price' => 'required_if:delivery_pricing_type,ad|nullable|numeric|min:0',
            'ad_notes' => 'nullable|string',
            'conditions' => 'nullable|string',
            'template_id' => 'required|exists:form_templates,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $product = Product::findOrFail($request->product_id);
            $customer = Customer::findOrFail($request->customer_id);

            // Calculate product price
            $unitPrice = $product->price;
            $customPricing = $product->pricing()->where('customer_id', $customer->id)->first();
            if ($customPricing) {
                $unitPrice = $customPricing->price;
            }

            $quantity = $request->quantity;
            $lineTotal = $unitPrice * $quantity;
            $totalAmount = $lineTotal;
            $taxAmount = 0;

            // Delivery fee based on pricing type
            $deliveryFee = 0;
            if ($request->delivery_pricing_type == 'manual') {
                $deliveryFee = $request->manual_delivery_price;
            }
            // For 'auto' and 'ad', delivery fee will be determined by task pricing

            $finalTotal = $totalAmount + $taxAmount + $deliveryFee;

            // Create Invoice
            $invoice = Sales_invoice::create([
                'invoice_number' => 'INV-' . date('Ymd') . '-' . str_pad(Sales_invoice::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'delivery_fee' => $deliveryFee,
                'final_total' => $finalTotal,
                'notes' => $request->conditions,
                'created_by' => Auth::id(),
            ]);

            // Create Detail Item
            Sales_invoice_detail::create([
                'sales_invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            // Create Task for delivery
            $taskData = [
                'sales_invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'user_id' => Auth::id(),
                'form_template_id' => $request->template_id,
                'vehicle_size_id' => $request->vehicle_size_id,
                'status' => $request->delivery_pricing_type == 'ad' ? 'advertised' : 'in_progress',
                'conditions' => $request->conditions,
            ];

            // Set pricing
            if ($request->delivery_pricing_type == 'manual') {
                $taskData['total_price'] = $request->manual_delivery_price;
                $taskData['pricing_type'] = 'manual';
            } elseif ($request->delivery_pricing_type == 'ad') {
                $taskData['total_price'] = 0;
                $taskData['pricing_type'] = 'manual';
            }

            $task = Task::create($taskData);

            // Create task points (pickup from product, delivery from request)
            $task->points()->createMany([
                [
                    'type' => 'pickup',
                    'latitude' => $product->latitude,
                    'longitude' => $product->longitude,
                    'address' => $product->address,
                    'order' => 1,
                ],
                [
                    'type' => 'delivery',
                    'latitude' => $request->delivery_lat,
                    'longitude' => $request->delivery_lng,
                    'address' => $request->delivery_address,
                    'order' => 2,
                ]
            ]);

            // If advertisement, create task ad
            if ($request->delivery_pricing_type == 'ad') {
                $task->taskAd()->create([
                    'lowest_price' => $request->ad_min_price,
                    'highest_price' => $request->ad_max_price,
                    'description' => $request->ad_notes,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => __('Order created successfully'),
                'invoice_id' => $invoice->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $invoice = Sales_invoice::with(['details', 'customer', 'tasks'])->findOrFail($id);
        return view('admin.sales.show', compact('invoice'));
    }

    public function updateStatus(Request $request, $id)
    {
        $invoice = Sales_invoice::findOrFail($id);

        if ($request->status == 'paid') {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $request->payment_method ?? $invoice->payment_method
            ]);
        } else {
            $invoice->update(['status' => $request->status]);
        }

        return redirect()->back()->with('success', __('Status updated successfully'));
    }

    public function createTask($id)
    {
        $invoice = Sales_invoice::with(['details', 'customer'])->findOrFail($id);

        if ($invoice->status !== 'paid') {
            return redirect()->back()->with('error', __('Invoice must be paid before creating a task'));
        }

        if ($invoice->tasks()->count() > 0) {
            return redirect()->back()->with('error', __('Task already exists for this invoice'));
        }

        // We assume single product for now based on requirement
        $detail = $invoice->details->first();
        $product = $detail->product;

        // Redirect to task creation with pre-filled data
        // We will use a special route or pass data via session/query
        return redirect()->route('tasks.create_from_invoice', ['invoice_id' => $invoice->id]);
    }
}
