<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Customs_Clearance;
use App\Models\Customs_Clearance_History;
use App\Models\Customs_Clearance_Offer;
use App\Http\Controllers\Controller;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class CustomerCustomsClearanceController extends Controller
{
    /**
     * Get customer customs clearances list
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $query = Customs_Clearance::where('customer_id', $customer->id);

            // Apply filters
            if ($request->filled('status')) {
                $statuses = is_array($request->status) ? $request->status : [$request->status];
                $query->whereIn('status', $statuses);
            }

            if ($request->filled('clearance_type')) {
                $query->where('clearance_type', $request->clearance_type);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('goods_description', 'like', "%{$search}%")
                      ->orWhere('origin_country', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $clearances = $query->with(['agent'])->paginate($perPage);

            $clearancesData = $clearances->map(function ($clearance) {
                return [
                    'id' => $clearance->id,
                    'clearance_type' => $clearance->clearance_type,
                    'status' => $clearance->status,
                    'goods_description' => $clearance->goods_description,
                    'goods_value' => $clearance->goods_value,
                    'goods_weight' => $clearance->goods_weight,
                    'origin_country' => $clearance->origin_country,
                    'destination_port' => $clearance->destination_port,
                    'expected_arrival' => $clearance->expected_arrival,
                    'agent' => $clearance->agent ? [
                        'id' => $clearance->agent->id,
                        'name' => $clearance->agent->name,
                        'company_name' => $clearance->agent->company_name,
                        'phone' => $clearance->agent->phone,
                        'rating' => $clearance->agent->rating ?? 0,
                    ] : null,
                    'total_cost' => $clearance->total_cost,
                    'currency' => 'SAR',
                    'created_at' => $clearance->created_at,
                    'updated_at' => $clearance->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'clearances' => $clearancesData,
                    'pagination' => [
                        'current_page' => $clearances->currentPage(),
                        'last_page' => $clearances->lastPage(),
                        'per_page' => $clearances->perPage(),
                        'total' => $clearances->total(),
                        'from' => $clearances->firstItem(),
                        'to' => $clearances->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get customs clearances',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new customs clearance request
     */
    public function store(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'clearance_type' => 'required|in:import,export,transit',
                'goods_description' => 'required|string|max:500',
                'goods_value' => 'required|numeric|min:1',
                'goods_weight' => 'required|numeric|min:0.1',
                'origin_country' => 'required|string|max:100',
                'destination_port' => 'required|string|max:100',
                'expected_arrival' => 'required|date|after:today',
                'assign_type' => 'required|in:direct,advertised',
                'agent_id' => 'required_if:assign_type,direct|exists:customers,id',
                'notes' => 'nullable|string|max:1000',
                'documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate agent if direct assignment
            if ($request->assign_type === 'direct') {
                $agent = Customer::where('id', $request->agent_id)
                                ->where('is_customs_clearance_agent', true)
                                ->first();

                if (!$agent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid customs clearance agent'
                    ], 422);
                }
            }

            DB::beginTransaction();

            // Prepare clearance data
            $clearanceData = [
                'customer_id' => $customer->id,
                'clearance_type' => $request->clearance_type,
                'goods_description' => $request->goods_description,
                'goods_value' => $request->goods_value,
                'goods_weight' => $request->goods_weight,
                'origin_country' => $request->origin_country,
                'destination_port' => $request->destination_port,
                'expected_arrival' => $request->expected_arrival,
                'notes' => $request->notes,
            ];

            // Handle assignment type
            if ($request->assign_type === 'direct') {
                $clearanceData['agent_id'] = $request->agent_id;
                $clearanceData['status'] = 'assigned';
            } else {
                $clearanceData['status'] = 'advertised';
            }

            // Create clearance
            $clearance = Customs_Clearance::create($clearanceData);

            // Handle document uploads
            if ($request->hasFile('documents')) {
                $documents = [];
                foreach ($request->file('documents') as $index => $file) {
                    $path = FileHelper::uploadFile($file, 'customs_clearances/documents');
                    $documents[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                        'uploaded_at' => now(),
                    ];
                }
                $clearance->update(['documents' => $documents]);
            }

            // Create history entry
            Customs_Clearance_History::create([
                'clearance_id' => $clearance->id,
                'status' => $clearance->status,
                'description' => 'Customs clearance request created',
                'created_by_type' => 'customer',
                'created_by_id' => $customer->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customs clearance request created successfully',
                'data' => [
                    'clearance' => [
                        'id' => $clearance->id,
                        'clearance_type' => $clearance->clearance_type,
                        'status' => $clearance->status,
                        'goods_description' => $clearance->goods_description,
                        'goods_value' => $clearance->goods_value,
                        'expected_arrival' => $clearance->expected_arrival,
                        'created_at' => $clearance->created_at,
                    ]
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customs clearance request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customs clearance details
     */
    public function show(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $clearance = Customs_Clearance::where('id', $id)
                                         ->where('customer_id', $customer->id)
                                         ->with(['agent'])
                                         ->first();

            if (!$clearance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customs clearance not found'
                ], 404);
            }

            $clearanceData = [
                'id' => $clearance->id,
                'clearance_type' => $clearance->clearance_type,
                'status' => $clearance->status,
                'goods_description' => $clearance->goods_description,
                'goods_value' => $clearance->goods_value,
                'goods_weight' => $clearance->goods_weight,
                'origin_country' => $clearance->origin_country,
                'destination_port' => $clearance->destination_port,
                'expected_arrival' => $clearance->expected_arrival,
                'notes' => $clearance->notes,
                'agent' => $clearance->agent ? [
                    'id' => $clearance->agent->id,
                    'name' => $clearance->agent->name,
                    'company_name' => $clearance->agent->company_name,
                    'phone' => $clearance->agent->phone,
                    'email' => $clearance->agent->email,
                    'rating' => $clearance->agent->rating ?? 0,
                ] : null,
                'total_cost' => $clearance->total_cost,
                'documents' => $clearance->documents ? collect($clearance->documents)->map(function ($doc) {
                    return [
                        'name' => $doc['name'],
                        'url' => asset('storage/' . $doc['path']),
                        'size' => $doc['size'],
                        'type' => $doc['type'],
                        'uploaded_at' => $doc['uploaded_at'],
                    ];
                }) : [],
                'created_at' => $clearance->created_at,
                'updated_at' => $clearance->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $clearanceData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get customs clearance details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload additional documents
     */
    public function uploadDocuments(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $clearance = Customs_Clearance::where('id', $id)
                                         ->where('customer_id', $customer->id)
                                         ->first();

            if (!$clearance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customs clearance not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'documents.*' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
                'document_types.*' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!$request->hasFile('documents')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents provided'
                ], 422);
            }

            DB::beginTransaction();

            $existingDocuments = $clearance->documents ?? [];
            $newDocuments = [];

            foreach ($request->file('documents') as $index => $file) {
                $path = FileHelper::uploadFile($file, 'customs_clearances/documents');
                $documentType = $request->input("document_types.{$index}") ?? 'Additional Document';

                $newDocuments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'document_type' => $documentType,
                    'uploaded_at' => now(),
                ];
            }

            // Merge with existing documents
            $allDocuments = array_merge($existingDocuments, $newDocuments);
            $clearance->update(['documents' => $allDocuments]);

            // Create history entry
            Customs_Clearance_History::create([
                'clearance_id' => $clearance->id,
                'status' => $clearance->status,
                'description' => 'Additional documents uploaded (' . count($newDocuments) . ' files)',
                'created_by_type' => 'customer',
                'created_by_id' => $customer->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Documents uploaded successfully',
                'data' => [
                    'uploaded_count' => count($newDocuments),
                    'total_documents' => count($allDocuments),
                ]
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get clearance status and history
     */
    public function getStatus(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $clearance = Customs_Clearance::where('id', $id)
                                         ->where('customer_id', $customer->id)
                                         ->first();

            if (!$clearance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customs clearance not found'
                ], 404);
            }

            // Get history
            $history = Customs_Clearance_History::where('clearance_id', $clearance->id)
                                               ->orderBy('created_at', 'desc')
                                               ->get()
                                               ->map(function ($entry) {
                                                   return [
                                                       'id' => $entry->id,
                                                       'status' => $entry->status,
                                                       'description' => $entry->description,
                                                       'created_by_type' => $entry->created_by_type,
                                                       'created_at' => $entry->created_at,
                                                   ];
                                               });

            // Get timeline
            $timeline = $this->getClearanceTimeline($clearance);

            return response()->json([
                'success' => true,
                'data' => [
                    'clearance_id' => $clearance->id,
                    'current_status' => $clearance->status,
                    'progress_percentage' => $this->calculateProgress($clearance->status),
                    'estimated_completion' => $this->estimateCompletion($clearance),
                    'timeline' => $timeline,
                    'history' => $history,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get clearance status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available clearance ads (for agents)
     */
    public function getAds(Request $request)
    {
        try {
            $query = Customs_Clearance::where('status', 'advertised');

            // Apply filters
            if ($request->filled('clearance_type')) {
                $query->where('clearance_type', $request->clearance_type);
            }

            if ($request->filled('origin_country')) {
                $query->where('origin_country', $request->origin_country);
            }

            if ($request->filled('destination_port')) {
                $query->where('destination_port', $request->destination_port);
            }

            if ($request->filled('value_min')) {
                $query->where('goods_value', '>=', $request->value_min);
            }

            if ($request->filled('value_max')) {
                $query->where('goods_value', '<=', $request->value_max);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $ads = $query->with(['customer'])->paginate($perPage);

            $adsData = $ads->map(function ($clearance) {
                return [
                    'id' => $clearance->id,
                    'clearance_type' => $clearance->clearance_type,
                    'goods_description' => $clearance->goods_description,
                    'goods_value' => $clearance->goods_value,
                    'goods_weight' => $clearance->goods_weight,
                    'origin_country' => $clearance->origin_country,
                    'destination_port' => $clearance->destination_port,
                    'expected_arrival' => $clearance->expected_arrival,
                    'customer' => [
                        'name' => $clearance->customer->name,
                        'company_name' => $clearance->customer->company_name,
                        'rating' => $clearance->customer->rating ?? 0,
                    ],
                    'offers_count' => Customs_Clearance_Offer::where('clearance_id', $clearance->id)->count(),
                    'created_at' => $clearance->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'ads' => $adsData,
                    'pagination' => [
                        'current_page' => $ads->currentPage(),
                        'last_page' => $ads->lastPage(),
                        'per_page' => $ads->perPage(),
                        'total' => $ads->total(),
                        'from' => $ads->firstItem(),
                        'to' => $ads->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get clearance ads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get clearance timeline
     */
    private function getClearanceTimeline($clearance)
    {
        $statuses = [
            'advertised' => 'Request Posted',
            'assigned' => 'Agent Assigned',
            'in_progress' => 'Processing',
            'documents_review' => 'Documents Under Review',
            'customs_processing' => 'Customs Processing',
            'payment_required' => 'Payment Required',
            'completed' => 'Completed',
            'canceled' => 'Canceled',
        ];

        $timeline = [];
        $history = Customs_Clearance_History::where('clearance_id', $clearance->id)
                                           ->orderBy('created_at', 'asc')
                                           ->get();

        foreach ($history as $entry) {
            $timeline[] = [
                'status' => $entry->status,
                'title' => $statuses[$entry->status] ?? ucfirst(str_replace('_', ' ', $entry->status)),
                'description' => $entry->description,
                'timestamp' => $entry->created_at,
                'is_current' => $entry->status === $clearance->status,
            ];
        }

        return $timeline;
    }

    /**
     * Calculate progress percentage
     */
    private function calculateProgress($status)
    {
        $progressMap = [
            'advertised' => 10,
            'assigned' => 25,
            'in_progress' => 40,
            'documents_review' => 60,
            'customs_processing' => 80,
            'payment_required' => 90,
            'completed' => 100,
            'canceled' => 0,
        ];

        return $progressMap[$status] ?? 0;
    }

    /**
     * Estimate completion date
     */
    private function estimateCompletion($clearance)
    {
        $daysMap = [
            'advertised' => 7,
            'assigned' => 5,
            'in_progress' => 4,
            'documents_review' => 3,
            'customs_processing' => 2,
            'payment_required' => 1,
        ];

        $daysRemaining = $daysMap[$clearance->status] ?? 0;

        if ($daysRemaining > 0) {
            return Carbon::now()->addDays($daysRemaining)->format('Y-m-d');
        }

        return null;
    }
}
