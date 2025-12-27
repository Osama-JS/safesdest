<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Customs_Clearance;
use App\Models\Customs_Clearance_History;
use App\Models\Customs_Clearance_Offer;
use App\Http\Controllers\Controller;
use App\Helpers\FileHelper;
use App\Models\Form_Field;
use App\Models\Form_Template;
use App\Models\Settings;
use App\Models\Clearance_Pricing_Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class CustomerCustomsClearanceController extends Controller
{
    /**
     * Get the active customs clearance template
     */
    public function getTemplate()
    {
        try {
            $templateId = Settings::getValue('customs_clearance_template');

            if (!$templateId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customs clearance template not configured'
                ], 404);
            }

            $template = Form_Template::with(['fields' => function($query) {
                $query->orderBy('order', 'ASC');
            }])->find($templateId);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'fields' => $template->fields
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer customs clearances list
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $query = Customs_Clearance::where('customer_id', $customer->id)
                                      ->with(['clearanceAgent', 'pricing']);

            // Apply filters
            if ($request->filled('status')) {
                $statuses = is_array($request->status) ? $request->status : [$request->status];
                $query->whereIn('status', $statuses);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search in notes or additional_data
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('notes', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $clearances = $query->paginate($perPage);

            $clearancesData = $clearances->getCollection()->map(function ($clearance) {
                $pricing = $clearance->pricing;
                $totalPrice = $clearance->total_price;

                // Simple pricing calculation if not included and no agent yet
                if ($totalPrice > 0 && !$clearance->included && $clearance->commission == 0 && $clearance->clearance_agent_id == null && $pricing) {
                    $vat = floatval($pricing->vat_commission);
                    $commission = floatval($pricing->service_commission);
                    $commissionType = $pricing->service_commission_type;

                    $commissionAmount = 0;
                    if ($commission > 0) {
                        $commissionAmount = ($commissionType === 'percentage') ? ($commission / 100) * $totalPrice : $commission;
                    }

                    $priceWithCommission = $totalPrice + $commissionAmount;
                    $vatAmount = ($vat > 0) ? ($vat / 100) * $priceWithCommission : 0;
                    $totalPrice += $commissionAmount + $vatAmount;
                }

                return [
                    'id' => $clearance->id,
                    'status' => $clearance->status,
                    'status_label' => __($clearance->status),
                    'total_price' => $totalPrice,
                    'is_public' => (bool)$clearance->public,
                    'closed' => (bool)$clearance->closed,
                    'payment_status' => $clearance->payment_status,
                    'notes' => $clearance->notes,
                    'agent' => $clearance->clearanceAgent ? [
                        'id' => $clearance->clearanceAgent->id,
                        'name' => $clearance->clearanceAgent->name,
                        'phone' => $clearance->clearanceAgent->phone,
                    ] : null,
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
     * Create/Store new customs clearance request
     */
    public function store(Request $request)
    {
        try {
            $customer = Auth::user();
            $templateId = Settings::getValue('customs_clearance_template');

            if (!$templateId) {
                return response()->json(['success' => false, 'message' => 'Template not configured'], 422);
            }

            $fields = Form_Field::where('form_template_id', $templateId)->get();
            $rules = [
                'notes' => 'nullable|string',
                'is_public' => 'nullable|boolean',
                'included' => 'nullable|boolean',
                'price' => 'nullable|numeric|min:0',
            ];
            Log::alert('start recording');
            foreach ($fields as $field) {
                $fieldKey = 'additional_fields.' . $field->name;

                if ($field->required && !in_array($field->type, ['file_expiration_date', 'file_with_text'])) {
                    $rules[$fieldKey] = 'required';
                }

                switch ($field->type) {
                    case 'file':
                    case 'image':
                        $rules[$fieldKey] = ($field->required ? 'required|' : 'nullable|') . 'file|max:10240';
                        break;
                    case 'file_expiration_date':
                        $rules[$fieldKey . '_file'] = ($field->required ? 'required|' : 'nullable|') . 'file|max:10240';
                        $rules[$fieldKey . '_expiration'] = ($field->required ? 'required|' : 'nullable|') . 'date|after_or_equal:today';
                        break;
                    case 'file_with_text':
                        $rules[$fieldKey . '_file'] = ($field->required ? 'required|' : 'nullable|') . 'file|max:10240';
                        $rules[$fieldKey . '_text'] = ($field->required ? 'required|' : 'nullable|') . 'string|max:255';
                        break;
                }
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                Log::alert('validation failed');

                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            Log::alert('validation passed');
            DB::beginTransaction();

            $structuredFields = [];
            foreach ($fields as $field) {
                $fieldName = $field->name;
                $fieldType = $field->type;

                if ($fieldType === 'file_expiration_date') {
                    if ($request->hasFile("additional_fields.{$fieldName}_file")) {
                        $path = FileHelper::uploadFile($request->file("additional_fields.{$fieldName}_file"), 'customs_clearances/files');
                        $structuredFields[$fieldName] = [
                            'label' => $field->label,
                            'value' => $path,
                            'expiration' => $request->input("additional_fields.{$fieldName}_expiration"),
                            'type' => $fieldType,
                        ];
                    }
                } else if ($fieldType === 'file_with_text') {
                    if ($request->hasFile("additional_fields.{$fieldName}_file")) {
                        $path = FileHelper::uploadFile($request->file("additional_fields.{$fieldName}_file"), 'customs_clearances/files');
                        $structuredFields[$fieldName] = [
                            'label' => $field->label,
                            'value' => $path,
                            'text' => $request->input("additional_fields.{$fieldName}_text"),
                            'type' => $fieldType,
                        ];
                    }
                } else if (in_array($fieldType, ['file', 'image'])) {
                    if ($request->hasFile("additional_fields.$fieldName")) {
                        $path = FileHelper::uploadFile($request->file("additional_fields.$fieldName"), 'customs_clearances/files');
                        $structuredFields[$fieldName] = [
                            'label' => $field->label,
                            'value' => $path,
                            'type' => $fieldType,
                        ];
                    }
                } else {
                    if ($request->has("additional_fields.$fieldName")) {
                        $structuredFields[$fieldName] = [
                            'label' => $field->label,
                            'value' => $request->input("additional_fields.$fieldName"),
                            'type' => $fieldType,
                        ];
                    }
                }
            }

            $pricing = Clearance_Pricing_Template::availableForCustomer($templateId, $customer->id)->first();
            if (!$pricing) {
                return response()->json(['success' => false, 'message' => 'No pricing template found for this request'], 422);
            }

            Log::alert('pricing found');
            $clearance = Customs_Clearance::create([
                'customer_id' => $customer->id,
                'form_template_id' => $templateId,
                'pricing_id' => $pricing->id,
                'total_price' => $request->price ?? 0,
                'included' => $request->included ?? 0,
                'public' => $request->is_public ?? 0,
                'notes' => $request->notes,
                'status' => 'in_progress',
                'additional_data' => $structuredFields,
            ]);

            Customs_Clearance_History::create([
                'customs_clearance_id' => $clearance->id,
                'action_type' => 'created',
                'description' => 'Customs clearance request created via Mobile App',
                'ip' => $request->ip()
            ]);
            Log::alert('clearance created');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customs clearance request created successfully',
                'data' => $clearance
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::alert('error creating clearance');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
                                         ->with(['clearanceAgent', 'pricing', 'history'])
                                         ->first();

            if (!$clearance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customs clearance not found'
                ], 404);
            }

            // Map additional data values with full URLs for files
            $additionalData = collect($clearance->additional_data)->map(function($field) {
                if (isset($field['type']) && in_array($field['type'], ['file', 'image', 'file_expiration_date', 'file_with_text'])) {
                    $field['url'] = asset('storage/' . $field['value']);
                }
                return $field;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $clearance->id,
                    'status' => $clearance->status,
                    'status_label' => __($clearance->status),
                    'total_price' => $clearance->total_price,
                    'included' => (bool)$clearance->included,
                    'is_public' => (bool)$clearance->public,
                    'notes' => $clearance->notes,
                    'additional_data' => $additionalData,
                    'agent' => $clearance->clearanceAgent ? [
                        'id' => $clearance->clearanceAgent->id,
                        'name' => $clearance->clearanceAgent->name,
                        'phone' => $clearance->clearanceAgent->phone,
                        'email' => $clearance->clearanceAgent->email,
                    ] : null,
                    'history' => $clearance->history,
                    'created_at' => $clearance->created_at,
                    'updated_at' => $clearance->updated_at,
                ]
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
     * Upload additional documents (kept for compatibility or future use, adjusted for corrected structure)
     */
    public function uploadDocuments(Request $request, $id)
    {
        // This method might need rethink if we want to add to additional_data,
        // but for now let's just return success if we don't strictly need it or adjust it.
        return response()->json(['success' => false, 'message' => 'Not implemented in dynamic mode'], 501);
    }

    /**
     * Get clearance status used by UI timeline
     */
    public function getStatus(Request $request, $id)
    {
        try {
            $customer = Auth::user();
            $clearance = Customs_Clearance::where('id', $id)
                                         ->where('customer_id', $customer->id)
                                         ->with('history')
                                         ->first();

            if (!$clearance) {
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $clearance->id,
                    'status' => $clearance->status,
                    'status_label' => __($clearance->status),
                    'history' => $clearance->history,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing customs clearance request
     */
    public function update(Request $request, $id)
    {
        try {
            $customer = Auth::user();
            $clearance = Customs_Clearance::where('id', $id)
                                         ->where('customer_id', $customer->id)
                                         ->first();

            if (!$clearance) {
                return response()->json(['success' => false, 'message' => 'Customs clearance not found'], 404);
            }

            if ($clearance->status !== 'in_progress') {
                return response()->json(['success' => false, 'message' => 'Only requests in "in_progress" status can be edited'], 422);
            }

            $templateId = $clearance->form_template_id;
            $fields = Form_Field::where('form_template_id', $templateId)->get();

            $rules = [
                'notes' => 'nullable|string',
                'is_public' => 'nullable|boolean',
                'included' => 'nullable|boolean',
                'price' => 'nullable|numeric|min:0',
            ];

            foreach ($fields as $field) {
                $fieldKey = 'additional_fields.' . $field->name;
                // Files are not required in update if they already exist
                if ($field->required && !in_array($field->type, ['file', 'image', 'file_expiration_date', 'file_with_text'])) {
                    $rules[$fieldKey] = 'required';
                }
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();

            $structuredFields = $clearance->additional_data ?? [];
            foreach ($fields as $field) {
                $fieldName = $field->name;
                $fieldType = $field->type;

                if ($fieldType === 'file_expiration_date') {
                    if ($request->hasFile("additional_fields.{$fieldName}_file")) {
                        $path = FileHelper::uploadFile($request->file("additional_fields.{$fieldName}_file"), 'customs_clearances/files');
                        $structuredFields[$fieldName] = [
                            'label' => $field->label,
                            'value' => $path,
                            'expiration' => $request->input("additional_fields.{$fieldName}_expiration"),
                            'type' => $fieldType,
                        ];
                    } elseif ($request->has("additional_fields.{$fieldName}_expiration")) {
                         // Update expiration if file is not changed
                         if (isset($structuredFields[$fieldName])) {
                             $structuredFields[$fieldName]['expiration'] = $request->input("additional_fields.{$fieldName}_expiration");
                         }
                    }
                } else if ($fieldType === 'file_with_text') {
                    if ($request->hasFile("additional_fields.{$fieldName}_file")) {
                        $path = FileHelper::uploadFile($request->file("additional_fields.{$fieldName}_file"), 'customs_clearances/files');
                        $structuredFields[$fieldName] = [
                            'label' => $field->label,
                            'value' => $path,
                            'text' => $request->input("additional_fields.{$fieldName}_text"),
                            'type' => $fieldType,
                        ];
                    } elseif ($request->has("additional_fields.{$fieldName}_text")) {
                        if (isset($structuredFields[$fieldName])) {
                            $structuredFields[$fieldName]['text'] = $request->input("additional_fields.{$fieldName}_text");
                        }
                    }
                } else if (in_array($fieldType, ['file', 'image'])) {
                    if ($request->hasFile("additional_fields.$fieldName")) {
                        $path = FileHelper::uploadFile($request->file("additional_fields.$fieldName"), 'customs_clearances/files');
                        $structuredFields[$fieldName] = [
                            'label' => $field->label,
                            'value' => $path,
                            'type' => $fieldType,
                        ];
                    }
                } else {
                    if ($request->has("additional_fields.$fieldName")) {
                        $structuredFields[$fieldName] = [
                            'label' => $field->label,
                            'value' => $request->input("additional_fields.$fieldName"),
                            'type' => $fieldType,
                        ];
                    }
                }
            }

            $clearance->update([
                'total_price' => $request->price ?? $clearance->total_price,
                'included' => $request->included ?? $clearance->included,
                'public' => $request->is_public ?? $clearance->public,
                'notes' => $request->notes ?? $clearance->notes,
                'additional_data' => $structuredFields,
            ]);

            Customs_Clearance_History::create([
                'customs_clearance_id' => $clearance->id,
                'action_type' => 'updated',
                'description' => 'Customs clearance request updated via Mobile App',
                'ip' => $request->ip()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customs clearance request updated successfully',
                'data' => $clearance
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get offers for a specific customs clearance request
     */
    public function offers(Request $request, $id)
    {
        try {
            $customer = Auth::user();
            $clearance = Customs_Clearance::where('id', $id)
                                         ->where('customer_id', $customer->id)
                                         ->first();

            if (!$clearance) {
                return response()->json(['success' => false, 'message' => 'Customs clearance not found'], 404);
            }

            $offers = Customs_Clearance_Offer::where('customs_clearance_id', $id)
                                            ->with('broker')
                                            ->orderBy('created_at', 'desc')
                                            ->get();

            return response()->json([
                'success' => true,
                'data' => $offers->map(function($offer) {
                    return [
                        'id' => $offer->id,
                        'price' => $offer->price,
                        'description' => $offer->description,
                        'accepted' => (bool)$offer->accepted,
                        'created_at' => $offer->created_at,
                        'broker' => $offer->broker ? [
                            'id' => $offer->broker->id,
                            'name' => $offer->broker->name,
                            'phone' => $offer->broker->phone,
                            'avatar' => $offer->broker->avatar ? asset('storage/' . $offer->broker->avatar) : null,
                        ] : null,
                    ];
                })
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Accept a specific offer for a customs clearance request
     */
    public function acceptOffer(Request $request, $id)
    {
        try {
            $customer = Auth::user();
            $offer = Customs_Clearance_Offer::with('customsClearance')->find($id);

            if (!$offer || $offer->customsClearance->customer_id !== $customer->id) {
                return response()->json(['success' => false, 'message' => 'Offer not found'], 404);
            }

            $clearance = $offer->customsClearance;

            if ($clearance->clearance_agent_id) {
                return response()->json(['success' => false, 'message' => 'Request already assigned to an agent'], 422);
            }

            DB::beginTransaction();

            // Accept this offer
            $offer->update(['accepted' => 1]);

            // Decline all other offers for this request
            Customs_Clearance_Offer::where('customs_clearance_id', $clearance->id)
                                  ->where('id', '!=', $offer->id)
                                  ->update(['accepted' => 0]);

            // Update clearance request
            $clearance->update([
                'clearance_agent_id' => $offer->clearance_agent_id,
                'status' => 'assigned', // Or whatever the next status should be
                'total_price' => $offer->price,
            ]);

            Customs_Clearance_History::create([
                'customs_clearance_id' => $clearance->id,
                'action_type' => 'offer_accepted',
                'description' => 'Offer from agent accepted. Request assigned.',
                'ip' => $request->ip()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offer accepted successfully',
                'data' => $clearance
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
