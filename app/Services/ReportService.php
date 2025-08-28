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
            'vehicle_size:id,name',
            'vehicle_size.type:id,name,vehicle_id',
            'vehicle_size.type.vehicle:id,name'
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

        // Process tasks data
        $processedTasks = $this->processTasksData($tasks, $filters);

        // Generate summary
        $summary = $this->generateSummary($tasks, $filters);

        return [
            'tasks' => $processedTasks,
            'summary' => $summary,
            'filters_applied' => $this->getAppliedFilters($filters),
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
            $requestedCustomerIds = array_intersect($filters['customer_ids'], $allowedCustomerIds);
            $query->whereIn('customer_id', $requestedCustomerIds);
        } else {
            $query->whereIn('customer_id', $filters['customer_ids']);
        }

        // Filter tasks based on user permissions
        if (!$user->can('manage_tasks')) {
            $teamIds = $user->teams->pluck('id')->toArray();
            $query->where(function($q) use ($user, $teamIds) {
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
    private function processTasksData($tasks, $filters)
    {
        return $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'total_price' => $task->total_price,
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
                'payment_method_ar' => $this->getPaymentMethodInArabic($task->payment_method),
                'created_by' => $task->user ? 'إداري' : 'عميل',
                'created_by_name' => $task->user->name ?? $task->customer->name ?? 'غير محدد',
                'created_at' => $task->created_at,
                'completed_at' => $task->completed_at,
                'closed_at' => $task->closed_at,
                'created_at_formatted' => $task->created_at ? $task->created_at->format('Y-m-d H:i') : '',
                'completed_at_formatted' => $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : '',
                'closed_at_formatted' => $task->closed_at ? $task->closed_at->format('Y-m-d H:i') : '',
            ];
        })->toArray();
    }

    /**
     * Get vehicle name
     */
    private function getVehicleName($task)
    {
        if ($task->vehicle_size && $task->vehicle_size->type && $task->vehicle_size->type->vehicle) {
            return $task->vehicle_size->type->vehicle->name . ' - ' . $task->vehicle_size->name;
        }
        return 'غير محدد';
    }

    /**
     * Generate report summary
     */
    private function generateSummary($tasks, $filters)
    {
        $totalTasks = $tasks->count();
        $totalAmount = $tasks->sum('total_price');
        
        $statusCounts = $tasks->groupBy('status')->map->count();
        $paymentStatusCounts = $tasks->groupBy('payment_status')->map->count();

        return [
            'total_tasks' => $totalTasks,
            'total_amount' => $totalAmount,
            'average_amount' => $totalTasks > 0 ? $totalAmount / $totalTasks : 0,
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

        // Date range
        $applied['date_range'] = $filters['date_from'] . ' إلى ' . $filters['date_to'];

        // Task statuses
        if (!empty($filters['task_statuses'])) {
            $applied['task_statuses'] = implode(', ', array_map([$this, 'getStatusInArabic'], $filters['task_statuses']));
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
            'assign' => 'مُعيّنة',
            'accepted' => 'مقبولة',
            'started' => 'بدأت',
            'in pickup point' => 'في نقطة الاستلام',
            'loading' => 'جاري التحميل',
            'in the way' => 'في الطريق',
            'in delivery point' => 'في نقطة التسليم',
            'unloading' => 'جاري التفريغ',
            'completed' => 'مكتملة',
            'canceled' => 'ملغية'
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
}
