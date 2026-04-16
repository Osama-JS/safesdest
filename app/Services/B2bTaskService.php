<?php

namespace App\Services;

use App\Models\Company_End_Client;
use App\Models\Company_Warehouse;
use App\Models\Task;
use App\Models\Task_Points;
use App\Models\TaskB2bDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class B2bTaskService
{
    public function __construct(
        protected CompanyPricingService $pricingService
    ) {}

    // ──────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────

    /**
     * احسب السعر الكامل للمهمة B2B.
     */
    public function calculatePrice(
        int $companyId,
        int $warehouseId,
        int $endClientId,
        int $vehicleSizeId
    ): array {
        $basePriceResult = $this->pricingService->resolveBasePrice(
            $companyId,
            $warehouseId,
            $endClientId,
            $vehicleSizeId
        );

        return $this->pricingService->calculateFinalPrice($companyId, $basePriceResult);
    }

    /**
     * إنشاء مهمة B2B كاملة (tasks + task_points + task_b2b_details).
     */
    public function createTask(array $data): Task
    {
        return DB::transaction(function () use ($data) {

            $warehouse = Company_Warehouse::findOrFail($data['warehouse_id']);
            $endClient = Company_End_Client::findOrFail($data['end_client_id']);

            // 1. احسب السعر
            $pricing = $this->calculatePrice(
                $data['company_id'],
                $data['warehouse_id'],
                $data['end_client_id'],
                $data['vehicle_size_id']
            );

            // 2. أنشئ المهمة الأساسية
            $task = Task::create([
                'customer_id'          => $data['company_id'],
                'vehicle_size_id'      => $data['vehicle_size_id'],
                'form_template_id'     => $data['form_template_id'] ?? null,
                'company_warehouse_id' => $warehouse->id,
                'company_end_client_id' => $endClient->id,
                'total_price'          => $pricing['total_price'],
                'pricing_type'         => 'b2b',
                'status'               => 'in_progress', // Set to in_progress so it appears in tasks API
                'conditions'           => $data['conditions'] ?? null,
                'user_id'              => auth()->id(),
                'created_at'           => $data['created_at'] ?? now(),
            ]);

            // 3. أنشئ نقطة الاستلام (Pickup ← المستودع)
            $pickupPoint = $this->buildPickupSnapshot($warehouse);
            Task_Points::create(array_merge($pickupPoint, [
                'task_id'        => $task->id,
                'type'           => 'pickup',
                'sequence'       => 1,
                'scheduled_time' => $data['pickup_before'] ?? now()->addHours(1),
                'note'           => $data['pickup_note'] ?? null,
            ]));

            // 4. أنشئ نقطة التسليم (Delivery ← العميل النهائي)
            $deliveryPoint = $this->buildDeliverySnapshot($endClient);
            Task_Points::create(array_merge($deliveryPoint, [
                'task_id'        => $task->id,
                'type'           => 'delivery',
                'sequence'       => 2,
                'scheduled_time' => $data['delivery_before'] ?? now()->addHours(3),
                'note'           => $data['delivery_note'] ?? null,
            ]));

            // 5. أنشئ سجل الـ B2B detail (الربط + الـ snapshot)
            TaskB2bDetail::create([
                'task_id'         => $task->id,
                'company_id'      => $data['company_id'],
                'warehouse_id'    => $warehouse->id,
                'end_client_id'   => $endClient->id,
                'vehicle_size_id' => $data['vehicle_size_id'],
                'base_price'      => $pricing['base_price'],
                'commission'      => $pricing['commission'],
                'vat_amount'      => $pricing['vat_amount'],
                'total_price'     => $pricing['total_price'],
                'pricing_rule'    => $pricing['pricing_rule'],
                // Snapshot المستودع
                'pickup_name'     => $warehouse->contact_name,
                'pickup_phone'    => $warehouse->contact_phone,
                'pickup_address'  => $warehouse->address,
                'pickup_lat'      => $warehouse->latitude,
                'pickup_lng'      => $warehouse->longitude,
                // Snapshot العميل النهائي
                'delivery_name'    => $endClient->name,
                'delivery_phone'   => $endClient->phone,
                'delivery_address' => $endClient->address,
                'delivery_lat'     => $endClient->latitude,
                'delivery_lng'     => $endClient->longitude,
            ]);

            // 6. إضافة حركة السجل
            $task->history()->create([
                'action_type' => 'creation',
                'description' => 'create B2B task manual',
                'ip'          => \App\Helpers\IpHelper::getUserIpAddress(),
                'user_id'     => auth()->id(),
            ]);

            return $task->fresh(['b2bDetail', 'point', 'delivery']);
        });
    }

    /**
     * تعديل مهمة B2B — يسمح بتغيير المستودع أو العميل أو نوع المركبة.
     */
    public function updateTask(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {

            $warehouseId  = $data['warehouse_id']  ?? $task->company_warehouse_id;
            $endClientId  = $data['end_client_id']  ?? $task->company_end_client_id;
            $vehicleSizeId = $data['vehicle_size_id'] ?? $task->vehicle_size_id;
            $companyId    = $task->customer_id;

            $warehouse = Company_Warehouse::findOrFail($warehouseId);
            $endClient = Company_End_Client::findOrFail($endClientId);

            // 1. إعادة حساب السعر بالبيانات الجديدة
            $pricing = $this->calculatePrice($companyId, $warehouseId, $endClientId, $vehicleSizeId);

            // 2. تحديث المهمة الأساسية
            $task->update([
                'company_warehouse_id'  => $warehouse->id,
                'company_end_client_id' => $endClient->id,
                'vehicle_size_id'       => $vehicleSizeId,
                'total_price'           => $pricing['total_price'],
                'conditions'            => $data['conditions'] ?? $task->conditions,
            ]);

            // 3. تحديث نقطة الاستلام
            $pickupPoint = $this->buildPickupSnapshot($warehouse);
            $task->pickup()->update(array_merge($pickupPoint, [
                'scheduled_time' => $data['pickup_before']  ?? $task->pickup?->scheduled_time,
                'note'           => $data['pickup_note']    ?? $task->pickup?->note,
            ]));

            // 4. تحديث نقطة التسليم
            $deliveryPoint = $this->buildDeliverySnapshot($endClient);
            $task->delivery()->update(array_merge($deliveryPoint, [
                'scheduled_time' => $data['delivery_before'] ?? $task->delivery?->scheduled_time,
                'note'           => $data['delivery_note']   ?? $task->delivery?->note,
            ]));

            // 5. تحديث سجل الـ B2B detail
            $task->b2bDetail()->update([
                'warehouse_id'    => $warehouse->id,
                'end_client_id'   => $endClient->id,
                'vehicle_size_id' => $vehicleSizeId,
                'base_price'      => $pricing['base_price'],
                'commission'      => $pricing['commission'],
                'vat_amount'      => $pricing['vat_amount'],
                'total_price'     => $pricing['total_price'],
                'pricing_rule'    => $pricing['pricing_rule'],
                // Snapshot محدّث
                'pickup_name'     => $warehouse->contact_name,
                'pickup_phone'    => $warehouse->contact_phone,
                'pickup_address'  => $warehouse->address,
                'pickup_lat'      => $warehouse->latitude,
                'pickup_lng'      => $warehouse->longitude,
                'delivery_name'   => $endClient->name,
                'delivery_phone'  => $endClient->phone,
                'delivery_address' => $endClient->address,
                'delivery_lat'    => $endClient->latitude,
                'delivery_lng'    => $endClient->longitude,
            ]);

            return $task->fresh(['b2bDetail', 'pickup', 'delivery']);
        });
    }

    // ──────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────

    /**
     * بناء بيانات نقطة الاستلام من المستودع.
     */
    private function buildPickupSnapshot(Company_Warehouse $warehouse): array
    {
        return [
            'contact_name'  => $warehouse->contact_name,
            'contact_phone' => $warehouse->contact_phone,
            'address'       => $warehouse->address ?? '',
            'latitude'      => $warehouse->latitude,
            'longitude'     => $warehouse->longitude,
        ];
    }

    /**
     * بناء بيانات نقطة التسليم من العميل النهائي.
     */
    private function buildDeliverySnapshot(Company_End_Client $endClient): array
    {
        return [
            'contact_name'  => $endClient->name,
            'contact_phone' => $endClient->phone  ?? '',
            'address'       => $endClient->address ?? '',
            'latitude'      => $endClient->latitude  ?? null,
            'longitude'     => $endClient->longitude ?? null,
        ];
    }
}
