<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Task;
use App\Models\Form_Template;
use App\Models\Form_Field;
use App\Models\Pricing_Template;
use App\Models\Vehicle_Size;
use App\Models\Settings;
use App\Models\Vehicle;
use App\Models\Task_History;
use App\Models\Driver;
use App\Http\Controllers\Controller;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CustomerTaskController extends Controller
{

    public function getInitData()
    {
        try {
            // جلب جميع المركبات
           $vehicles = Vehicle::with([
                'types' => function ($query) {
                    $query->select('id', 'vehicle_id', 'name', 'en_name')
                        ->with(['sizes' => function ($q) {
                            $q->select('id', 'vehicle_type_id', 'name');
                        }]);
                }
            ])->get(['id', 'name', 'en_name']);


            // دالة مساعدة لجلب بيانات القالب بناءً على مفتاح الإعداد
            $getTemplateFields = function ($settingKey) {
                $setting = Settings::where('key', $settingKey)->first();

                if (!$setting) {
                    return null;
                }

                $template = Form_Template::find($setting->value);

                if (!$template) {
                    return null;
                }

                $fields = Form_Field::where('form_template_id', $template->id)
                    ->where('customer_can', 'write')
                    ->orderBy('order')
                    ->get([
                        'id',
                        'name',
                        'label',
                        'type',
                        'value',
                        'required',
                        'order',
                    ]);

                return [
                    'template' => $template,
                    'fields' => $fields,
                ];
            };

            // جلب القوالب الثلاثة
            $taskTemplate = $getTemplateFields('task_template');
            $taskFromTemplate = $getTemplateFields('task_from_port_template');
            $taskToTemplate = $getTemplateFields('task_to_port_template');

            // إرجاع البيانات بصيغة JSON
            return response()->json([
                'status' => 200,
                'data' => [
                    'vehicles' => $vehicles,
                    'task_template' => $taskTemplate,
                    'task_from_template' => $taskFromTemplate,
                    'task_to_template' => $taskToTemplate,
                ],
            ]);

        } catch (\Exception $e) {
            // التعامل مع الأخطاء بشكل منظم
            return response()->json([
                'status' => 500,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => $e->getMessage(),
            ]);
        }
    }

     public function getTasksMap(Request $request)
    {
        $customer = $request->user();
        $query = Task::where('customer_id', $customer->id)->where('closed', false)->where('status', '!=', 'canceled')->where('status', '!=', 'refund');

        $tasks = $query->get();
        
        $tasksData = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'status' => $task->status,
                'pickup' => [
                    
                ],
                'price' => $task->price,
                'currency' => 'SAR',
                'driver' => $task->driver ? [
                    'id' => $task->driver->id,
                    'name' => $task->driver->name,
                    'phone' => $task->driver->phone,
                    'image' => $task->driver->image ? asset('storage/' . $task->driver->image) : null,
                    'rating' => $task->driver->rating,
                ] : null,
                'vehicle_size' => $task->vehicle_size ? [
                    'id' => $task->vehicle_size->id,
                    'name' => $task->vehicle_size->name,
                    'description' => $task->vehicle_size->description,
                ] : null,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ];
        });



    }

    /**
     * Get customer tasks list
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $query = Task::where('customer_id', $customer->id);

            // Apply filters
            if ($request->filled('status')) {
                $statuses = is_array($request->status) ? $request->status : [$request->status];
                $query->whereIn('status', $statuses);
            }

            if ($request->filled('task_type')) {
                $query->where('task_type', $request->task_type);
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
                    $q->where('from_location', 'like', "%{$search}%")
                      ->orWhere('to_location', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $tasks = $query->with(['driver', 'vehicle_size', 'pricing_template'])
                          ->paginate($perPage);

            $tasksData = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'task_type' => $task->task_type,
                    'status' => $task->status,
                    'from_location' => $task->from_location,
                    'to_location' => $task->to_location,
                    'from_lat' => $task->from_lat,
                    'from_lng' => $task->from_lng,
                    'to_lat' => $task->to_lat,
                    'to_lng' => $task->to_lng,
                    'pickup_time' => $task->pickup_time,
                    'delivery_time' => $task->delivery_time,
                    'price' => $task->price,
                    'currency' => 'SAR',
                    'driver' => $task->driver ? [
                        'id' => $task->driver->id,
                        'name' => $task->driver->name,
                        'phone' => $task->driver->phone,
                        'image' => $task->driver->image ? asset('storage/' . $task->driver->image) : null,
                        'rating' => $task->driver->rating,
                    ] : null,
                    'vehicle_size' => $task->vehicle_size ? [
                        'id' => $task->vehicle_size->id,
                        'name' => $task->vehicle_size->name,
                        'description' => $task->vehicle_size->description,
                    ] : null,
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $tasksData,
                    'pagination' => [
                        'current_page' => $tasks->currentPage(),
                        'last_page' => $tasks->lastPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                        'from' => $tasks->firstItem(),
                        'to' => $tasks->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new task
     */
    public function store(Request $request)
    {
        try {
            $customer = $request->user();

            // Base validation rules
            $baseRules = [
                'task_type' => 'required|in:normal,task_from,task_to',
                'from_location' => 'required|string|max:255',
                'to_location' => 'required|string|max:255',
                'from_lat' => 'required|numeric|between:-90,90',
                'from_lng' => 'required|numeric|between:-180,180',
                'to_lat' => 'required|numeric|between:-90,90',
                'to_lng' => 'required|numeric|between:-180,180',
                'pickup_time' => 'required|date|after:now',
                'delivery_time' => 'nullable|date|after:pickup_time',
                'vehicle_size_id' => 'required|exists:vehicle_sizes,id',
                'pricing_template_id' => 'required|exists:pricing_templates,id',
                'assign_type' => 'required|in:direct,advertised',
                'driver_id' => 'required_if:assign_type,direct|exists:drivers,id',
                'description' => 'nullable|string|max:1000',
            ];

            // Get form template for additional fields validation
            $additionalRules = [];
            $template = null;

            // Determine which template to use based on task type
            $templateField = match($request->task_type) {
                'normal' => 'task_template',
                'task_from' => 'task_from_port_template',
                'task_to' => 'task_to_port_template',
                default => 'task_template'
            };

            if ($request->filled('form_template_id')) {
                $template = Form_Template::with('fields')->find($request->form_template_id);
            } else {
                // Get default template based on task type
                $template = Form_Template::where('type', $templateField)->first();
            }

            if ($template) {
                foreach ($template->fields as $field) {
                    if ($field->customer_can === 'write') {
                        $fieldKey = 'additional_fields.' . $field->name;
                        $rules = [];

                        if ($field->required) {
                            $rules[] = 'required';
                        }

                        // Add type-specific validation
                        switch ($field->type) {
                            case 'email':
                                $rules[] = 'email';
                                break;
                            case 'number':
                                $rules[] = 'numeric';
                                break;
                            case 'date':
                                $rules[] = 'date';
                                break;
                            case 'file':
                            case 'image':
                                $rules[] = 'file';
                                if ($field->type === 'image') {
                                    $rules[] = 'image|mimes:jpeg,png,jpg,webp,gif|max:2048';
                                } else {
                                    $rules[] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv|max:5120';
                                }
                                break;
                        }

                        if (!empty($rules)) {
                            $additionalRules[$fieldKey] = $rules;
                        }
                    }
                }
            }

            // Merge validation rules
            $allRules = array_merge($baseRules, $additionalRules);

            $validator = Validator::make($request->all(), $allRules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Prepare task data
            $taskData = [
                'customer_id' => $customer->id,
                'task_type' => $request->task_type,
                'from_location' => $request->from_location,
                'to_location' => $request->to_location,
                'from_lat' => $request->from_lat,
                'from_lng' => $request->from_lng,
                'to_lat' => $request->to_lat,
                'to_lng' => $request->to_lng,
                'pickup_time' => $request->pickup_time,
                'delivery_time' => $request->delivery_time,
                'vehicle_size_id' => $request->vehicle_size_id,
                'pricing_template_id' => $request->pricing_template_id,
                'description' => $request->description,
                'form_template_id' => $template ? $template->id : null,
            ];

            // Handle assignment type
            if ($request->assign_type === 'direct') {
                $taskData['driver_id'] = $request->driver_id;
                $taskData['status'] = 'assign';
            } else {
                $taskData['status'] = 'advertised';
            }

            // Handle additional fields
            $structuredFields = [];
            if ($template) {
                foreach ($template->fields as $field) {
                    if ($field->customer_can === 'write') {
                        $fieldName = $field->name;
                        $fieldKey = 'additional_fields.' . $fieldName;

                        if ($request->has($fieldKey)) {
                            $value = $request->input($fieldKey);

                            // Handle file uploads
                            if (in_array($field->type, ['file', 'image']) && $request->hasFile($fieldKey)) {
                                $file = $request->file($fieldKey);
                                $path = FileHelper::uploadFile($file, 'tasks/additional_fields');
                                $value = $path;
                            }

                            $structuredFields[$fieldName] = [
                                'label' => $field->label,
                                'value' => $value,
                                'type' => $field->type,
                            ];
                        }
                    }
                }
                $taskData['additional_data'] = $structuredFields;
            }

            // Calculate pricing (implement your pricing logic)
            $taskData['price'] = $this->calculateTaskPrice($request);

            // Create task
            $task = Task::create($taskData);

            // Create task history entry
            Task_History::create([
                'task_id' => $task->id,
                'status' => $task->status,
                'description' => 'Task created',
                'created_by_type' => 'customer',
                'created_by_id' => $customer->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => [
                    'task' => [
                        'id' => $task->id,
                        'task_type' => $task->task_type,
                        'status' => $task->status,
                        'from_location' => $task->from_location,
                        'to_location' => $task->to_location,
                        'pickup_time' => $task->pickup_time,
                        'delivery_time' => $task->delivery_time,
                        'price' => $task->price,
                        'created_at' => $task->created_at,
                    ]
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task details
     */
    public function show(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $task = Task::where('id', $id)
                       ->where('customer_id', $customer->id)
                       ->with(['driver', 'vehicle_size', 'pricing_template', 'form_template.fields'])
                       ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $taskData = [
                'id' => $task->id,
                'task_type' => $task->task_type,
                'status' => $task->status,
                'from_location' => $task->from_location,
                'to_location' => $task->to_location,
                'from_lat' => $task->from_lat,
                'from_lng' => $task->from_lng,
                'to_lat' => $task->to_lat,
                'to_lng' => $task->to_lng,
                'pickup_time' => $task->pickup_time,
                'delivery_time' => $task->delivery_time,
                'price' => $task->price,
                'description' => $task->description,
                'driver' => $task->driver ? [
                    'id' => $task->driver->id,
                    'name' => $task->driver->name,
                    'phone' => $task->driver->phone,
                    'image' => $task->driver->image ? asset('storage/' . $task->driver->image) : null,
                    'rating' => $task->driver->rating,
                    'vehicle_info' => $task->driver->vehicle_info,
                ] : null,
                'vehicle_size' => $task->vehicle_size ? [
                    'id' => $task->vehicle_size->id,
                    'name' => $task->vehicle_size->name,
                    'description' => $task->vehicle_size->description,
                    'max_weight' => $task->vehicle_size->max_weight,
                    'max_volume' => $task->vehicle_size->max_volume,
                ] : null,
                'pricing_template' => $task->pricing_template ? [
                    'id' => $task->pricing_template->id,
                    'name' => $task->pricing_template->name,
                    'base_price' => $task->pricing_template->base_price,
                ] : null,
                'additional_data' => $task->additional_data,
                'form_template' => $task->form_template ? [
                    'id' => $task->form_template->id,
                    'name' => $task->form_template->name,
                    'fields' => $task->form_template->fields->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'name' => $field->name,
                            'label' => $field->label,
                            'type' => $field->type,
                            'required' => $field->required,
                            'customer_can' => $field->customer_can,
                            'driver_can' => $field->driver_can,
                            'order' => $field->order,
                        ];
                    })
                ] : null,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $taskData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get task details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate task pricing
     */
    private function calculateTaskPrice(Request $request)
    {
        // Implement your pricing calculation logic here
        // This is a simplified version

        $pricingTemplate = Pricing_Template::find($request->pricing_template_id);
        $vehicleSize = Vehicle_Size::find($request->vehicle_size_id);

        $basePrice = $pricingTemplate ? $pricingTemplate->base_price : 100;
        $sizeMultiplier = $vehicleSize ? $vehicleSize->price_multiplier ?? 1 : 1;

        // Calculate distance (simplified)
        $distance = $this->calculateDistance(
            $request->from_lat,
            $request->from_lng,
            $request->to_lat,
            $request->to_lng
        );

        $price = $basePrice * $sizeMultiplier * ($distance / 100); // per 100km

        return max($price, $basePrice); // Minimum base price
    }

    /**
     * Calculate distance between two points
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Update task (limited fields)
     */
    public function update(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $task = Task::where('id', $id)
                       ->where('customer_id', $customer->id)
                       ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Only allow updates for certain statuses
            if (!in_array($task->status, ['advertised', 'in_progress'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task cannot be updated in current status'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'pickup_time' => 'sometimes|date|after:now',
                'delivery_time' => 'sometimes|date|after:pickup_time',
                'description' => 'sometimes|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only(['pickup_time', 'delivery_time', 'description']);
            $task->update($updateData);

            // Create history entry
            Task_History::create([
                'task_id' => $task->id,
                'status' => $task->status,
                'description' => 'Task updated by customer',
                'created_by_type' => 'customer',
                'created_by_id' => $customer->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => [
                    'task' => [
                        'id' => $task->id,
                        'pickup_time' => $task->pickup_time,
                        'delivery_time' => $task->delivery_time,
                        'description' => $task->description,
                        'updated_at' => $task->updated_at,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel task
     */
    public function cancel(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $task = Task::where('id', $id)
                       ->where('customer_id', $customer->id)
                       ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Check if task can be canceled
            if (in_array($task->status, ['completed', 'canceled', 'refund'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task cannot be canceled in current status'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Update task status
            $task->update([
                'status' => 'canceled',
                'cancellation_reason' => $request->reason,
                'canceled_at' => now(),
            ]);

            // Create history entry
            Task_History::create([
                'task_id' => $task->id,
                'status' => 'canceled',
                'description' => 'Task canceled by customer: ' . $request->reason,
                'created_by_type' => 'customer',
                'created_by_id' => $customer->id,
            ]);

            // Handle refund logic if needed
            // $this->processTaskRefund($task);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task canceled successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track task location and status
     */
    public function track(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $task = Task::where('id', $id)
                       ->where('customer_id', $customer->id)
                       ->with(['driver'])
                       ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $trackingData = [
                'task_id' => $task->id,
                'status' => $task->status,
                'current_location' => null,
                'estimated_arrival' => null,
                'driver' => null,
                'route_info' => [
                    'from' => [
                        'location' => $task->from_location,
                        'lat' => $task->from_lat,
                        'lng' => $task->from_lng,
                    ],
                    'to' => [
                        'location' => $task->to_location,
                        'lat' => $task->to_lat,
                        'lng' => $task->to_lng,
                    ]
                ],
                'timeline' => $this->getTaskTimeline($task),
            ];

            // Add driver info and location if assigned
            if ($task->driver) {
                $trackingData['driver'] = [
                    'id' => $task->driver->id,
                    'name' => $task->driver->name,
                    'phone' => $task->driver->phone,
                    'image' => $task->driver->image ? asset('storage/' . $task->driver->image) : null,
                    'rating' => $task->driver->rating,
                ];

                // Get driver's current location (if available)
                if ($task->driver->current_lat && $task->driver->current_lng) {
                    $trackingData['current_location'] = [
                        'lat' => $task->driver->current_lat,
                        'lng' => $task->driver->current_lng,
                        'updated_at' => $task->driver->location_updated_at,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $trackingData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get tracking info',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task history
     */
    public function getHistory(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $task = Task::where('id', $id)
                       ->where('customer_id', $customer->id)
                       ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $history = Task_History::where('task_id', $task->id)
                                  ->orderBy('created_at', 'desc')
                                  ->get()
                                  ->map(function ($entry) {
                                      return [
                                          'id' => $entry->id,
                                          'status' => $entry->status,
                                          'description' => $entry->description,
                                          'created_by_type' => $entry->created_by_type,
                                          'created_by_id' => $entry->created_by_id,
                                          'created_at' => $entry->created_at,
                                      ];
                                  });

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get task history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rate task and driver
     */
    public function rate(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $task = Task::where('id', $id)
                       ->where('customer_id', $customer->id)
                       ->first();

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            if ($task->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only rate completed tasks'
                ], 400);
            }

            if ($task->customer_rating) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task already rated'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Update task rating
            $task->update([
                'customer_rating' => $request->rating,
                'customer_comment' => $request->comment,
                'rated_at' => now(),
            ]);

            // Update driver rating if task has driver
            if ($task->driver_id) {
                $this->updateDriverRating($task->driver_id, $request->rating);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task rated successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to rate task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task timeline for tracking
     */
    private function getTaskTimeline($task)
    {
        $timeline = [];

        $statuses = [
            'advertised' => 'Task Posted',
            'in_progress' => 'In Progress',
            'assign' => 'Driver Assigned',
            'started' => 'Task Started',
            'in pickup point' => 'At Pickup Point',
            'loading' => 'Loading',
            'in the way' => 'On The Way',
            'in delivery point' => 'At Delivery Point',
            'unloading' => 'Unloading',
            'completed' => 'Completed',
            'canceled' => 'Canceled',
        ];

        $history = Task_History::where('task_id', $task->id)
                              ->orderBy('created_at', 'asc')
                              ->get();

        foreach ($history as $entry) {
            $timeline[] = [
                'status' => $entry->status,
                'title' => $statuses[$entry->status] ?? ucfirst(str_replace('_', ' ', $entry->status)),
                'description' => $entry->description,
                'timestamp' => $entry->created_at,
                'is_current' => $entry->status === $task->status,
            ];
        }

        return $timeline;
    }

    /**
     * Update driver rating
     */
    private function updateDriverRating($driverId, $newRating)
    {
        $driver = Driver::find($driverId);
        if (!$driver) {
            return;
        }

        $totalRatings = Task::where('driver_id', $driverId)
                           ->whereNotNull('customer_rating')
                           ->count();

        $averageRating = Task::where('driver_id', $driverId)
                            ->whereNotNull('customer_rating')
                            ->avg('customer_rating');

        $driver->update([
            'rating' => round($averageRating, 2),
            'total_ratings' => $totalRatings,
        ]);
    }

    /**
     * Validate task data before creation
     */
    public function validateTask(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'task_type' => 'required|in:normal,task_from,task_to',
                'from_lat' => 'required|numeric|between:-90,90',
                'from_lng' => 'required|numeric|between:-180,180',
                'to_lat' => 'required|numeric|between:-90,90',
                'to_lng' => 'required|numeric|between:-180,180',
                'vehicle_size_id' => 'required|exists:vehicle_sizes,id',
                'pricing_template_id' => 'required|exists:pricing_templates,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculate distance and estimated price
            $distance = $this->calculateDistance(
                $request->from_lat,
                $request->from_lng,
                $request->to_lat,
                $request->to_lng
            );

            $estimatedPrice = $this->calculateTaskPrice($request);

            return response()->json([
                'success' => true,
                'data' => [
                    'distance_km' => round($distance, 2),
                    'estimated_price' => $estimatedPrice,
                    'currency' => 'SAR',
                    'validation_passed' => true,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate pricing for task
     */
    public function calculatePricing(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'from_lat' => 'required|numeric|between:-90,90',
                'from_lng' => 'required|numeric|between:-180,180',
                'to_lat' => 'required|numeric|between:-90,90',
                'to_lng' => 'required|numeric|between:-180,180',
                'vehicle_size_id' => 'required|exists:vehicle_sizes,id',
                'pricing_template_id' => 'required|exists:pricing_templates,id',
                'pickup_time' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $distance = $this->calculateDistance(
                $request->from_lat,
                $request->from_lng,
                $request->to_lat,
                $request->to_lng
            );

            $pricingTemplate = Pricing_Template::find($request->pricing_template_id);
            $vehicleSize = Vehicle_Size::find($request->vehicle_size_id);

            $basePrice = $pricingTemplate ? $pricingTemplate->base_price : 100;
            $sizeMultiplier = $vehicleSize ? ($vehicleSize->price_multiplier ?? 1) : 1;
            $distancePrice = $distance * ($pricingTemplate->price_per_km ?? 2);

            // Time-based pricing (rush hours, weekends, etc.)
            $timeMultiplier = 1;
            if ($request->pickup_time) {
                $pickupTime = Carbon::parse($request->pickup_time);
                $hour = $pickupTime->hour;

                // Rush hour pricing (7-9 AM, 5-7 PM)
                if (($hour >= 7 && $hour <= 9) || ($hour >= 17 && $hour <= 19)) {
                    $timeMultiplier = 1.2;
                }

                // Weekend pricing
                if ($pickupTime->isWeekend()) {
                    $timeMultiplier *= 1.1;
                }
            }

            $totalPrice = ($basePrice + $distancePrice) * $sizeMultiplier * $timeMultiplier;
            $finalPrice = max($totalPrice, $basePrice); // Minimum base price

            return response()->json([
                'success' => true,
                'data' => [
                    'distance_km' => round($distance, 2),
                    'base_price' => $basePrice,
                    'distance_price' => round($distancePrice, 2),
                    'size_multiplier' => $sizeMultiplier,
                    'time_multiplier' => $timeMultiplier,
                    'total_price' => round($finalPrice, 2),
                    'currency' => 'SAR',
                    'breakdown' => [
                        'base_price' => $basePrice,
                        'distance_cost' => round($distancePrice, 2),
                        'size_adjustment' => round(($sizeMultiplier - 1) * 100, 0) . '%',
                        'time_adjustment' => round(($timeMultiplier - 1) * 100, 0) . '%',
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate pricing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get map data for customer tasks
     */
    public function getMapData(Request $request)
    {
        try {
            $customer = $request->user();

            // جلب المهام النشطة (غير مكتملة وغير مفوترة)
            $tasks = Task::where('customer_id', $customer->id)
                ->whereNotIn('status', ['completed', 'invoiced', 'cancelled'])
                ->with([
                    'driver:id,name,longitude,altitude,last_seen_at,online,free,status',
                    'taskPoints:id,task_id,type,latitude,longitude,address,contact_name,contact_phone,notes,scheduled_at'
                ])
                ->get();

            $mapData = [];

            foreach ($tasks as $task) {
                $taskData = [
                    'id' => $task->id,
                    'title' => $task->title ?? "مهمة #{$task->id}",
                    'status' => $task->status,
                    'driver_id' => $task->driver_id,
                    'driver_name' => $task->driver ? $task->driver->name : null,
                    'driver_latitude' => $task->driver ? $task->driver->altitude : null, // altitude stores latitude
                    'driver_longitude' => $task->driver ? $task->driver->longitude : null,
                    'driver_last_seen' => $task->driver ? $task->driver->last_seen_at : null,
                    'points' => $task->taskPoints->map(function ($point) {
                        return [
                            'id' => $point->id,
                            'task_id' => $point->task_id,
                            'type' => $point->type,
                            'latitude' => $point->latitude,
                            'longitude' => $point->longitude,
                            'address' => $point->address,
                            'contact_name' => $point->contact_name,
                            'contact_phone' => $point->contact_phone,
                            'notes' => $point->notes,
                            'scheduled_at' => $point->scheduled_at,
                        ];
                    }),
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                    'customer_notes' => $task->notes,
                    'estimated_price' => $task->price,
                ];

                $mapData[] = $taskData;
            }

            return response()->json([
                'success' => true,
                'data' => $mapData,
                'message' => 'Map data retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve map data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get driver locations for customer tasks
     */
    public function getDriverLocations(Request $request)
    {
        try {
            $customer = $request->user();

            // جلب السائقين المعينين للمهام النشطة للعميل
            $drivers = Driver::whereHas('tasks', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)
                      ->whereNotIn('status', ['completed', 'invoiced', 'cancelled']);
            })
            ->where('online', true)
            ->whereNotNull('longitude')
            ->whereNotNull('altitude')
            ->select('id', 'name', 'longitude', 'altitude', 'last_seen_at', 'online', 'free', 'status')
            ->get();

            $driverLocations = $drivers->map(function ($driver) {
                return [
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->name,
                    'latitude' => $driver->altitude, // altitude field stores latitude
                    'longitude' => $driver->longitude,
                    'last_seen' => $driver->last_seen_at,
                    'is_online' => $driver->online,
                    'is_free' => $driver->free,
                    'status' => $driver->status,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $driverLocations,
                'message' => 'Driver locations retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve driver locations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
