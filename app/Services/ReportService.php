<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Teams;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Generate customer tasks report
     */
    public function generateCustomerTasksReport(array $filters, bool $preview = false)
    {
        $user = Auth::user();

        // Build base query
        $query = Task::with([
            'customer:id,name,company_name',
            'driver:id,name,phone,team_id',
            'driver.team:id,name',
            'user:id,name',
            'pickup:id,task_id,address,contact_name,contact_phone',
            'delivery:id,task_id,address,contact_name,contact_phone',
            'vehicle_size',
        ]);

        // Apply user permissions filter
        $this->applyUserPermissions($query, $user, $filters);

        // Apply filters
        $this->applyFilters($query, $filters);


        // Apply date range
        $this->applyDateRange($query, $filters);

        // For preview, limit results
        if ($preview) {
            $query->limit(100);
        }


        // Execute query
        $tasks = $query->orderBy('created_at', 'desc')->get();

        // Build extra columns map for dynamic driver fields
        $extraColumnsMap = [];
        if (!empty($filters['columns'])) {
            $extraFieldIds = [];
            foreach ($filters['columns'] as $col) {
                if (str_starts_with($col, 'driver_extra:')) {
                    $extraFieldIds[] = str_replace('driver_extra:', '', $col);
                }
            }
            if (!empty($extraFieldIds)) {
                $fields = \App\Models\Form_Field::whereIn('id', $extraFieldIds)->get(['id', 'label']);
                foreach ($fields as $field) {
                    $extraColumnsMap['driver_extra:' . $field->id] = $field->label;
                }
            }
        }

        // Process tasks data
        $processedTasks = $this->processTasksData($tasks, $filters, $extraColumnsMap);

        // Generate summary
        $summary = $this->generateSummary($tasks, $filters);

        return [
            'tasks' => $processedTasks,
            'summary' => $summary,
            'filters_applied' => $this->getAppliedFilters($filters),
            'extra_columns_map' => $extraColumnsMap,
            'generated_at' => now(),
            'generated_by' => $user->name
        ];
    }

    /**
     * Apply user permissions to query
     */
    private function applyUserPermissions($query, $user, $filters)
    {
        // Filter customers based on user permissions
        if (!$user->can('mange_customers')) {
            $allowedCustomerIds = $user->customers->pluck('id')->toArray();
            if (!empty($filters['customer_ids'])) {
                $requestedCustomerIds = array_intersect($filters['customer_ids'], $allowedCustomerIds);
                $query->whereIn('customer_id', $requestedCustomerIds);
            } else {
                $query->whereIn('customer_id', $allowedCustomerIds);
            }
        } else {
            if (!empty($filters['customer_ids'])) {
                $query->whereIn('customer_id', $filters['customer_ids']);
            }
        }

        // Filter tasks based on user permissions
        if (!$user->can('manage_tasks')) {
            $teamIds = $user->teams->pluck('id')->toArray();
            $query->where(function ($q) use ($user, $teamIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('team_id', $teamIds);
            });
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, $filters)
    {
        // Task status filter
        if (!empty($filters['task_statuses'])) {
            $query->whereIn('status', $filters['task_statuses']);
        }

        // Payment status filter
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Payment method filter
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Driver filter
        if (!empty($filters['driver_ids'])) {
            $query->whereIn('driver_id', $filters['driver_ids']);
        }

        // Team filter
        if (!empty($filters['team_ids'])) {
            $query->whereIn('team_id', $filters['team_ids']);
        }

        // Task creator filter
        if (!empty($filters['created_by'])) {
            if ($filters['created_by'] === 'customer') {
                $query->whereNotNull('customer_id')->whereNull('user_id');
            } elseif ($filters['created_by'] === 'admin') {
                $query->whereNotNull('user_id');
            }
        }

        // Closed status filter
        if (isset($filters['closed_status'])) {
            $query->where('closed', $filters['closed_status']);
        }
    }

    /**
     * Apply date range filters
     */
    private function applyDateRange($query, $filters)
    {
        $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
        $dateTo = Carbon::parse($filters['date_to'])->endOfDay();

        // Default to created_at if no specific date type is specified
        $dateType = $filters['date_type'] ?? 'created_at';

        switch ($dateType) {
            case 'completed_at':
                $query->whereBetween('completed_at', [$dateFrom, $dateTo]);
                break;
            case 'closed_at':
                $query->whereBetween('closed_at', [$dateFrom, $dateTo]);
                break;
            default:
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
                break;
        }
    }

    /**
     * Process tasks data for report
     */
    private function processTasksData($tasks, $filters, $extraColumnsMap = [])
    {
        // dd($tasks);

        $t = $tasks->map(function ($task) use ($extraColumnsMap) {
            // Handle refunded/cancelled tasks - set price to 0
            $effectivePrice = $this->getEffectivePrice($task);
            $effectivePaymentMethod = $this->getEffectivePaymentMethod($task);
            $processedTask = [
                'id' => $task->id,
                'total_price' => $effectivePrice,
                'original_price' => $task->total_price, // Keep original for reference
                'customer_name' => $task->customer->name ?? 'غير محدد',
                'customer_company' => $task->customer->company_name ?? '',
                'driver_name' => $task->driver->name ?? 'غير محدد',
                'driver_phone' => $task->driver->phone ?? '',
                'team_name' => $task->driver->team->name ?? 'غير محدد',
                'pickup_address' => $task->pickup->address ?? 'غير محدد',
                'pickup_contact_name' => $task->pickup->contact_name ?? '',
                'pickup_contact_phone' => $task->pickup->contact_phone ?? '',
                'delivery_address' => $task->delivery->address ?? 'غير محدد',
                'delivery_contact_name' => $task->delivery->contact_name ?? '',
                'delivery_contact_phone' => $task->delivery->contact_phone ?? '',
                'vehicle_name' => $this->getVehicleName($task),
                'status' => $task->status,
                'status_ar' => $this->getStatusInArabic($task->status),
                'payment_status' => $task->payment_status,
                'payment_status_ar' => $this->getPaymentStatusInArabic($task->payment_status),
                'payment_method' => $task->payment_method,
                'payment_method_ar' => $effectivePaymentMethod,
                'created_by' => $task->user ? 'إداري' : 'عميل',
                'created_by_name' => $task->user->name ?? $task->customer->name ?? 'غير محدد',
                'created_at' => $task->created_at,
                'completed_at' => $task->completed_at,
                'closed_at' => $task->closed_at,
                'created_at_formatted' => $task->created_at ? $task->created_at->format('Y-m-d H:i') : '',
                'completed_at_formatted' => $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : '',
                'closed_at_formatted' => $task->closed_at ? $task->closed_at->format('Y-m-d H:i') : '',
                'delivery_number' => $task->delivery_number ?? 'غير محدد',
            ];

            // Attach dynamic driver extra fields
            if (!empty($extraColumnsMap) && $task->driver && !empty($task->driver->additional_data)) {
                $additionalData = is_string($task->driver->additional_data) ? json_decode($task->driver->additional_data, true) : $task->driver->additional_data;
                if (is_array($additionalData)) {
                    foreach ($extraColumnsMap as $colKey => $label) {
                        $processedTask[$colKey] = 'غير محدد';
                        foreach ($additionalData as $item) {
                            if (is_array($item) && isset($item['label']) && $item['label'] === $label) {
                                $processedTask[$colKey] = $item['value'] ?? 'غير محدد';
                                break;
                            }
                        }
                    }
                }
            } else {
                foreach ($extraColumnsMap as $colKey => $label) {
                    $processedTask[$colKey] = 'غير محدد';
                }
            }

            return $processedTask;
        })->toArray();
        return  $t;
    }

    /**
     * Get vehicle name
     */
    private function getVehicleName($task)
    {
        if ($task->vehicle_size_id) {
            return $task->vehicle_size->type->vehicle->name . ' - ' . $task->vehicle_size->type->name . ' - ' . $task->vehicle_size->name;
        }
        return 'غير محدد';
    }

    /**
     * Get effective price for task (0 for refunded/cancelled)
     */
    private function getEffectivePrice($task)
    {
        // Set price to 0 for refunded or cancelled tasks
        if (in_array($task->status, ['refund', 'canceled', 'cancelled'])) {
            return 0;
        }

        return $task->total_price;
    }

    /**
     * Get effective payment method display
     */
    private function getEffectivePaymentMethod($task)
    {
        // Show empty for incomplete payment status (pending, unpaid, partially_paid)
        if (in_array($task->payment_status, ['pending', 'unpaid', 'partially_paid'])) {
            return '';
        }

        return $this->getPaymentMethodInArabic($task->payment_method);
    }

    /**
     * Generate report summary
     */
    private function generateSummary($tasks, $filters)
    {
        $totalTasks = $tasks->count();

        // Calculate effective amounts (excluding refunded/cancelled tasks)
        $effectiveAmounts = $tasks->map(function ($task) {
            return $this->getEffectivePrice($task);
        });

        $totalAmount = $effectiveAmounts->sum();

        // Payment status breakdown with effective amounts
        $paidTasks = $tasks->where('payment_status', 'completed');
        $partiallyPaidTasks = $tasks->where('payment_status', 'partially_paid');
        $unpaidTasks = $tasks->where('payment_status', 'waiting');
        $pendingTasks = $tasks->where('payment_status', 'pending');

        $paidAmount = $paidTasks->sum(function ($task) {
            return $this->getEffectivePrice($task);
        });

        $partiallyPaidAmount = $partiallyPaidTasks->sum(function ($task) {
            return $this->getEffectivePrice($task);
        });

        $unpaidAmount = $unpaidTasks->sum(function ($task) {
            return $this->getEffectivePrice($task);
        });

        $pendingAmount = $pendingTasks->sum(function ($task) {
            return $this->getEffectivePrice($task);
        });

        // Remaining = unpaid + partially paid + pending
        $remainingAmount = $unpaidAmount + $partiallyPaidAmount + $pendingAmount;

        // Payment method breakdown (only include fully paid tasks)
        $paymentMethodBreakdown = $tasks->filter(function ($task) {
            // Only include tasks that are fully paid and not refunded/cancelled
            if (in_array($task->status, ['refund', 'canceled', 'cancelled'])) {
                return false;
            }
            // Only include fully paid tasks
            if ($task->payment_status !== 'completed') {
                return false;
            }
            return true;
        })->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum(function ($task) {
                    return $this->getEffectivePrice($task);
                })
            ];
        });

        $statusCounts = $tasks->groupBy('status')->map->count();
        $paymentStatusCounts = $tasks->groupBy('payment_status')->map->count();

        return [
            'total_tasks' => $totalTasks,
            'total_amount' => $totalAmount,
            'average_amount' => $totalTasks > 0 ? $totalAmount / $totalTasks : 0,
            'paid_amount' => $paidAmount,
            'partially_paid_amount' => $pendingAmount,
            'unpaid_amount' => $unpaidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_method_breakdown' => $paymentMethodBreakdown,
            'status_counts' => $statusCounts,
            'payment_status_counts' => $paymentStatusCounts,
            'date_range' => [
                'from' => $filters['date_from'],
                'to' => $filters['date_to']
            ]
        ];
    }

    /**
     * Get applied filters description
     */
    private function getAppliedFilters($filters)
    {
        $applied = [];

        // Customer filter
        if (!empty($filters['customer_ids'])) {
            $customerNames = Customer::whereIn('id', $filters['customer_ids'])->pluck('name')->toArray();
            $applied['customers'] = implode(', ', $customerNames);
        }

        // Driver filter

        if (!empty($filters['driver_ids'])) {
            $customerNames = Driver::whereIn('id', $filters['driver_ids'])->pluck('name')->toArray();
            $applied['drivers'] = implode(', ', $customerNames);
        }

        if (!empty($filters['team_ids'])) {
            $customerNames = Teams::whereIn('id', $filters['team_ids'])->pluck('name')->toArray();
            $applied['teams'] = implode(', ', $customerNames);
        }

        // Date range
        $applied['date_range'] = $filters['date_from'] . ' إلى ' . $filters['date_to'];

        // Task statuses
        if (!empty($filters['task_statuses'])) {
            $applied['task_statuses'] = implode(', ', $filters['task_statuses']);
        }

        // Payment status
        if (!empty($filters['payment_status'])) {
            $applied['payment_status'] = $this->getPaymentStatusInArabic($filters['payment_status']);
        }

        // Payment method
        if (!empty($filters['payment_method'])) {
            $applied['payment_method'] = $this->getPaymentMethodInArabic($filters['payment_method']);
        }

        return $applied;
    }

    /**
     * Get status in Arabic
     */
    private function getStatusInArabic($status)
    {
        $statuses = [
            'in_progress' => 'قيد التنفيذ',
            'advertised' => 'معلن عنها',
            'assign' => 'معيّنة',
            'accepted' => 'مقبولة',
            'started' => 'بدأت',
            'in pickup point' => 'في نقطة الاستلام',
            'loading' => 'جاري التحميل',
            'in the way' => 'في الطريق',
            'in delivery point' => 'في نقطة التسليم',
            'unloading' => 'جاري التفريغ',
            'invoiced' => 'مفوترة',
            'completed' => 'مكتملة',
            'canceled' => 'ملغية',
            'refund' => 'مرتجعة'

        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Get payment status in Arabic
     */
    private function getPaymentStatusInArabic($status)
    {
        $statuses = [
            'waiting' => 'في الانتظار',
            'completed' => 'مكتمل',
            'pending' => 'معلق'
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Get payment method in Arabic
     */
    private function getPaymentMethodInArabic($method)
    {
        $methods = [
            'cash' => 'نقدي',
            'credit' => 'ائتمان',
            'banking' => 'تحويل بنكي',
            'wallet' => 'محفظة إلكترونية'
        ];

        return $methods[$method] ?? $method;
    }

    /**
     * Generate driver tasks report
     */
    public function generateDriverTasksReport(array $filters, bool $preview = false)
    {
        $user = Auth::user();

        // Build base query
        $query = Task::with([
            'customer:id,name,phone,company_name',
            'driver:id,name,phone,team_id',
            'driver.team:id,name'
        ]);

        // Filter by drivers (required)
        if (!empty($filters['driver_ids'])) {
            $query->whereIn('driver_id', $filters['driver_ids']);
        }

        // Filter by date range (required)
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('created_at', [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59'
            ]);
        }

        // Additional filters
        if (!empty($filters['customer_ids'])) {
            $query->whereIn('customer_id', $filters['customer_ids']);
        }

        if (!empty($filters['team_ids'])) {
            $query->whereHas('driver', function ($q) use ($filters) {
                $q->whereIn('team_id', $filters['team_ids']);
            });
        }

        if (!empty($filters['task_statuses'])) {
            $query->whereIn('status', $filters['task_statuses']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['created_by'])) {
            if ($filters['created_by'] === 'customer') {
                $query->whereNotNull('customer_id');
            } elseif ($filters['created_by'] === 'admin') {
                $query->whereNull('customer_id');
            }
        }

        // Get tasks
        $tasks = $query->orderBy('created_at', 'desc')->get();

        // Process tasks data
        $processedTasks = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'total_price' => ($task->total_price - $task->commission) ?? 0,
                'pickup' => [
                    'address' => $task->pickup->address ?? '',
                    'contact_name' => $task->pickup->contact_name ?? '',
                    'contact_phone' => $task->pickup->contact_phone ?? ''
                ],
                'delivery' => [
                    'address' => $task->delivery->address ?? '',
                    'contact_name' => $task->delivery->contact_name ?? '',
                    'contact_phone' => $task->delivery->contact_phone ?? ''
                ],
                'customer' => [
                    'name' => $task->customer->name ?? '',
                    'phone' => ($task->customer->phone_code . $task->customer->phone) ?? '',
                    'company_name' => $task->customer->company_name ?? ''
                ],
                'driver' => [
                    'name' => $task->driver->name ?? '',
                    'phone' => ($task->driver->phone_code . $task->driver->phone) ?? ''
                ],
                'vehicle' =>  $task->vehicle_size->type->vehicle->name . ' - ' . $task->vehicle_size->type->name . ' - ' . $task->vehicle_size->name,
                'team_name' => $task->driver->team->name ?? '',
                'created_by' => $task->user ? 'admin' : 'customer',
                'created_by_name' => $task->user->name ?? $task->customer->name ?? 'غير محدد',
                'status' => $task->status ?? '',
                'status_ar' => $this->getStatusInArabic($task->status),
                'payment_status' => $task->payment_status ?? '',
                'payment_status_ar' => $this->getPaymentStatusInArabic($task->payment_status),
                'payment_method' => $task->payment_method ?? '',
                'payment_method_ar' => $this->getPaymentMethodInArabic($task->payment_method),
                'created_at' => $task->created_at ? $task->created_at->format('Y-m-d H:i') : '',
                'completed_at' => $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : '',
                'closed_at' => $task->closed_at ? $task->closed_at->format('Y-m-d H:i') : '',
                'notes' => $task->notes ?? ''
            ];
        });

        // Calculate summary
        $summary = $this->calculateDriverTasksSummary($processedTasks);

        return [
            'tasks' => $preview ? $processedTasks->take(10) : $processedTasks,
            'summary' => $summary,
            'generated_at' => now(),
            'generated_by' => $user->name ?? 'System',
            'filters_applied' => $this->getAppliedFilters($filters)
        ];
    }

    /**
     * Generate team tasks report
     */
    public function generateTeamTasksReport(array $filters, bool $preview = false)
    {
        $user = Auth::user();

        // Build base query
        $query = Task::with([
            'customer:id,name,phone,company_name',
            'driver:id,name,phone,team_id',
            'driver.team:id,name'
        ]);

        // Filter by teams (required)
        if (!empty($filters['team_ids'])) {
            $query->whereHas('driver', function ($q) use ($filters) {
                $q->whereIn('team_id', $filters['team_ids']);
            });
        }

        // Filter by date range (required)
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('created_at', [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59'
            ]);
        }

        // Additional filters
        if (!empty($filters['customer_ids'])) {
            $query->whereIn('customer_id', $filters['customer_ids']);
        }

        if (!empty($filters['driver_ids'])) {
            $query->whereIn('driver_id', $filters['driver_ids']);
        }

        if (!empty($filters['task_statuses'])) {
            $query->whereIn('status', $filters['task_statuses']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['created_by'])) {
            if ($filters['created_by'] === 'customer') {
                $query->whereNotNull('customer_id');
            } elseif ($filters['created_by'] === 'admin') {
                $query->whereNull('customer_id');
            }
        }

        // Get tasks
        $tasks = $query->orderBy('created_at', 'desc')->get();

        // Process tasks data
        $processedTasks = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'total_price' => $task->total_price ?? 0,
                'commission' => $task->commission ?? 0,
                'pickup_address' => $task->pickup->address ?? '',
                'delivery_address' => $task->delivery->address ?? '',
                'customer_name' => $task->customer->name ?? '',
                'customer_phone' => $task->customer->phone ?? '',
                'customer_company_name' => $task->customer->company_name ?? '',
                'driver_name' => $task->driver->name ?? '',
                'driver_phone' => $task->driver->phone ?? '',
                'team_name' => $task->driver->team->name ?? '',
                'status' => $task->status ?? '',
                 'pickup' => [
                    'address' => $task->pickup->address ?? '',
                    'contact_name' => $task->pickup->contact_name ?? '',
                    'contact_phone' => $task->pickup->contact_phone ?? ''
                ],
                'delivery' => [
                    'address' => $task->delivery->address ?? '',
                    'contact_name' => $task->delivery->contact_name ?? '',
                    'contact_phone' => $task->delivery->contact_phone ?? ''
                ],
                'customer' => [
                    'name' => $task->customer->name ?? '',
                    'phone' => ($task->customer->phone_code . $task->customer->phone) ?? '',
                    'company_name' => $task->customer->company_name ?? ''
                ],
                'driver' => [
                    'name' => $task->driver->name ?? '',
                    'phone' => ($task->driver->phone_code . $task->driver->phone) ?? ''
                ],
                'created_by' => $task->user ? 'admin' : 'customer',
                'created_by_name' => $task->user->name ?? $task->customer->name ?? 'غير محدد',

                'status_ar' => $this->getStatusInArabic($task->status),
                'payment_status' => $task->payment_status ?? '',
                'payment_status_ar' => $this->getPaymentStatusInArabic($task->payment_status),
                'payment_method' => $task->payment_method ?? '',
                'payment_method_ar' => $this->getPaymentMethodInArabic($task->payment_method),
                'created_at_formatted' => $task->created_at ? $task->created_at->format('Y-m-d H:i') : '',
                'closed_at_formatted' => $task->closed_at ? $task->closed_at->format('Y-m-d H:i') : '',
                'notes' => $task->notes ?? ''
            ];
        });



        // Calculate summary
        $summary = $this->calculateTeamTasksSummary($processedTasks);

        return [
            'tasks' => $preview ? $processedTasks->take(10) : $processedTasks,
            'summary' => $summary,
            'generated_at' => now(),
            'generated_by' => $user->name ?? 'System',
            'filters_applied' => $this->getAppliedFilters($filters)
        ];
    }

    /**
     * Calculate driver tasks summary
     */
    private function calculateDriverTasksSummary($tasks)
    {
        $totalTasks = $tasks->count();
        $totalAmount = 0;
        $totalCommission = 0;
        $paidAmount = 0;
        $partiallyPaidAmount = 0;
        $unpaidAmount = 0;

        foreach ($tasks as $task) {
            $taskAmount = ($task['total_price'] ?? 0) - ($task['commission'] ?? 0);
            $totalAmount += $taskAmount;
            $totalCommission += $task['commission'] ?? 0;

            // Calculate payment amounts
            if ($task['payment_status'] === 'completed') {
                $paidAmount += $taskAmount;
            } elseif ($task['payment_status'] === 'pending') {
                $partiallyPaidAmount += $taskAmount;
            } else {
                $unpaidAmount += $taskAmount;
            }
        }

        return [
            'total_tasks' => $totalTasks,
            'total_amount' => $totalAmount,
            'total_commission' => $totalCommission,
            'average_amount' => $totalTasks > 0 ? $totalAmount / $totalTasks : 0,
            'paid_amount' => $paidAmount,
            'partially_paid_amount' => $partiallyPaidAmount,
            'unpaid_amount' => $unpaidAmount
        ];
    }

    /**
     * Calculate team tasks summary
     */
    private function calculateTeamTasksSummary($tasks)
    {
        $totalTasks = $tasks->count();
        $totalAmount = 0;
        $totalCommission = 0;
        $paidAmount = 0;
        $partiallyPaidAmount = 0;
        $unpaidAmount = 0;

        foreach ($tasks as $task) {
            $taskAmount = ($task['total_price'] ?? 0) - ($task['commission'] ?? 0);
            $totalAmount += $taskAmount;
            $totalCommission += $task['commission'] ?? 0;

            // Calculate payment amounts
            if ($task['payment_status'] === 'completed') {
                $paidAmount += $taskAmount;
            } elseif ($task['payment_status'] === 'pending') {
                $partiallyPaidAmount += $taskAmount;
            } else {
                $unpaidAmount += $taskAmount;
            }
        }

        return [
            'total_tasks' => $totalTasks,
            'total_amount' => $totalAmount,
            'total_commission' => $totalCommission,
            'average_amount' => $totalTasks > 0 ? $totalAmount / $totalTasks : 0,
            'paid_amount' => $paidAmount,
            'partially_paid_amount' => $partiallyPaidAmount,
            'unpaid_amount' => $unpaidAmount
        ];
    }

    /**
     * Export driver tasks to Excel
     */
    public function exportDriverTasksToExcel($reportData, $filters)
    {
        // Create Excel export similar to customer tasks
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = ['رقم المهمة', 'سعر المهمة', 'المسار', 'العميل', 'حالة المهمة', 'حالة الدفع', 'طريقة الدفع', 'تاريخ الإنشاء', 'تاريخ الإغلاق'];
        $sheet->fromArray($headers, null, 'A1');

        // Add data
        $row = 2;
        foreach ($reportData['tasks'] as $task) {
            $displayPrice = ($task['total_price'] ?? 0) - ($task['commission'] ?? 0);
            $route = 'من: ' . ($task['pickup_address'] ?? '') . ' إلى: ' . ($task['delivery_address'] ?? '');

            $sheet->fromArray([
                $task['id'],
                number_format($displayPrice, 2) . ' ريال',
                $route,
                $task['customer_name'],
                $task['status_ar'],
                $task['payment_status_ar'],
                $task['payment_method_ar'],
                $task['created_at_formatted'],
                $task['closed_at_formatted'] ?: 'لم يتم الإغلاق بعد'
            ], null, 'A' . $row);
            $row++;
        }

        // Create response
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'driver-tasks-report-' . date('Y-m-d-H-i-s') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export driver tasks to PDF
     */
    public function exportDriverTasksToPdf($reportData, $filters)
    {
        // Get driver names for the report
        $driverIds = $filters['driver_ids'] ?? [];
        $driverNames = \App\Models\Driver::whereIn('id', $driverIds)->get(['id', 'name', 'phone']);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.reports.pdf.driver-tasks-simple', [
            'reportData' => $reportData,
            'filters' => $filters,
            'driverNames' => $driverNames
        ]);

        return $pdf->download('driver-tasks-report-' . date('Y-m-d-H-i-s') . '.pdf');
    }

    /**
     * Export team tasks to Excel
     */
    public function exportTeamTasksToExcel($reportData, $filters)
    {
        // Create Excel export similar to customer tasks
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = ['رقم المهمة', 'سعر المهمة', 'المسار', 'العميل', 'السائق', 'حالة المهمة', 'حالة الدفع', 'طريقة الدفع', 'تاريخ الإنشاء', 'تاريخ الإغلاق'];
        $sheet->fromArray($headers, null, 'A1');

        // Add data
        $row = 2;
        foreach ($reportData['tasks'] as $task) {
            $displayPrice = ($task['total_price'] ?? 0) - ($task['commission'] ?? 0);
            $route = 'من: ' . ($task['pickup_address'] ?? '') . ' إلى: ' . ($task['delivery_address'] ?? '');

            $sheet->fromArray([
                $task['id'],
                number_format($displayPrice, 2) . ' ريال',
                $route,
                $task['customer_name'],
                $task['driver_name'],
                $task['status_ar'],
                $task['payment_status_ar'],
                $task['payment_method_ar'],
                $task['created_at_formatted'],
                $task['closed_at_formatted'] ?: 'لم يتم الإغلاق بعد'
            ], null, 'A' . $row);
            $row++;
        }

        // Create response
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'team-tasks-report-' . date('Y-m-d-H-i-s') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


}


