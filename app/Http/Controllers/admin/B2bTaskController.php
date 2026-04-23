<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Company_End_Client;
use App\Models\Company_Warehouse;
use App\Models\Task;
use App\Models\Vehicle;
use App\Models\Vehicle_Size;
use App\Services\B2bTaskService;
use Illuminate\Http\Request;

class B2bTaskController extends Controller
{
    public function __construct(
        protected B2bTaskService $b2bTaskService
    ) {}

    // ──────────────────────────────────────────────
    // API: بيانات للـ Modal (Dropdowns)
    // ──────────────────────────────────────────────

    /**
     * GET /admin/b2b/api/companies/{id}/warehouses
     * قائمة مستودعات الشركة للـ dropdown.
     */
    public function getWarehouses(int $companyId)
    {
        $warehouses = Company_Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'contact_name', 'contact_phone', 'address', 'latitude', 'longitude']);

        return response()->json($warehouses);
    }

    /**
     * GET /admin/b2b/api/companies/{id}/end-clients
     * قائمة العملاء النهائيين مع دعم البحث والـ pagination.
     */
    public function getEndClients(Request $request, int $companyId)
    {
        $query = Company_End_Client::where('company_id', $companyId)
            ->where('is_active', true);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('client_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $clients = $query->select(['id', 'name', 'client_code', 'phone', 'address', 'city', 'latitude', 'longitude'])
            ->orderBy('name')
            ->paginate(50);

        return response()->json($clients);
    }

    /**
     * GET /admin/b2b/api/vehicles
     * قائمة المركبات (الاسم/البراند) للـ dropdown.
     */
    public function getVehicles()
    {
        return response()->json(Vehicle::all(['id', 'name']));
    }

    /**
     * GET /admin/b2b/api/vehicle-sizes
     * قائمة أنواع المركبات المتاحة.
     */
    public function getVehicleSizes()
    {
        $sizes = Vehicle_Size::all(['id', 'name']);

        return response()->json($sizes);
    }

    // ──────────────────────────────────────────────
    // API: حساب السعر
    // ──────────────────────────────────────────────

    /**
     * POST /admin/b2b/api/calculate-price
     * حساب سعر المهمة قبل إنشائها (AJAX).
     */
    public function calculatePrice(Request $request)
    {
        $validated = $request->validate([
            'company_id'      => 'required|integer|exists:customers,id',
            'warehouse_id'    => 'required|integer|exists:company_warehouses,id',
            'end_client_id'   => 'required|integer|exists:company_end_clients,id',
            'vehicle_size_id' => 'required|integer|exists:vehicle_sizes,id',
        ]);

        try {
            $pricing = $this->b2bTaskService->calculatePrice(
                $validated['company_id'],
                $validated['warehouse_id'],
                $validated['end_client_id'],
                $validated['vehicle_size_id']
            );

            return response()->json([
                'status'  => 1,
                'pricing' => $pricing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ──────────────────────────────────────────────
    // CRUD: إنشاء وتعديل المهام
    // ──────────────────────────────────────────────

    /**
     * POST /admin/b2b/tasks
     * إنشاء مهمة B2B جديدة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id'      => 'required|integer|exists:customers,id',
            'warehouse_id'    => 'required|integer|exists:company_warehouses,id',
            'end_client_id'   => 'required|integer|exists:company_end_clients,id',
            'vehicle_size_id' => 'required|integer|exists:vehicle_sizes,id',
            'quantity'        => 'required|integer|min:1',
            'delivery_before' => 'nullable|date',
            'conditions'      => 'nullable|string|max:1000',
            'pickup_note'     => 'nullable|string|max:500',
            'delivery_note'   => 'nullable|string|max:500',
            'template'        => 'nullable|integer|exists:form_templates,id',
            'additional_fields' => 'nullable|array',
        ]);

        try {
            $task = $this->b2bTaskService->createTask($validated);

            return response()->json([
                'status'  => 1,
                'message' => __('تم إنشاء المهمة بنجاح'),
                'task_id' => is_array($task) ? $task[0]->id : $task->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /admin/b2b/tasks/{task}/data
     * جلب بيانات مهمة B2B لتعيينها في Modal التعديل.
     */
    public function getData(Task $task)
    {
        if (!$task->is_b2b || !$task->b2bDetail) {
            return response()->json(['status' => 0, 'message' => 'ليست مهمة B2B'], 404);
        }

        $detail = $task->b2bDetail()->with(['warehouse', 'endClient', 'vehicleSize.type'])->first();

        return response()->json([
            'status' => 1,
            'data'   => [
                'task_id'         => $task->id,
                'company_id'      => $detail->company_id,
                'warehouse_id'    => $detail->warehouse_id,
                'end_client_id'   => $detail->end_client_id,
                'vehicle_id'      => $detail->vehicleSize->type?->vehicle_id,
                'vehicle_type_id' => $detail->vehicleSize->vehicle_type_id,
                'vehicle_size_id' => $detail->vehicle_size_id,
                'conditions'      => $task->conditions,
                'form_template_id' => $task->form_template_id,
                'additional_data' => $task->additional_data,
                'delivery_before' => $task->delivery?->scheduled_time,
                'pricing'         => [
                    'base_price'   => $detail->base_price,
                    'commission'   => $detail->commission,
                    'vat_amount'   => $detail->vat_amount,
                    'total_price'  => $detail->total_price,
                    'pricing_rule' => $detail->pricing_rule,
                ],
                'warehouse'  => [
                    'id'   => $detail->warehouse->id,
                    'name' => $detail->warehouse->name,
                ],
                'end_client' => [
                    'id'   => $detail->endClient->id,
                    'name' => $detail->endClient->name,
                ],
                'vehicle_size' => [
                    'id'   => $detail->vehicleSize->id,
                    'name' => $detail->vehicleSize->name ?? $detail->vehicleSize->name_ar,
                ],
            ],
        ]);
    }

    /**
     * PUT /admin/b2b/tasks/{task}
     * تعديل مهمة B2B (يسمح بتغيير المستودع/العميل/المركبة).
     */
    public function update(Request $request, Task $task)
    {
        if (!$task->is_b2b) {
            return response()->json(['status' => 0, 'message' => 'ليست مهمة B2B'], 422);
        }

        $validated = $request->validate([
            'warehouse_id'    => 'nullable|integer|exists:company_warehouses,id',
            'end_client_id'   => 'nullable|integer|exists:company_end_clients,id',
            'vehicle_size_id' => 'nullable|integer|exists:vehicle_sizes,id',
            'delivery_before' => 'nullable|date',
            'conditions'      => 'nullable|string|max:1000',
            'pickup_note'     => 'nullable|string|max:500',
            'delivery_note'   => 'nullable|string|max:500',
            'template'        => 'nullable|integer|exists:form_templates,id',
            'additional_fields' => 'nullable|array',
        ]);

        try {
            $updatedTask = $this->b2bTaskService->updateTask($task, $validated);

            return response()->json([
                'status'  => 1,
                'message' => __('تم تحديث المهمة بنجاح'),
                'task_id' => $updatedTask->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
