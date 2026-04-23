<?php

namespace App\Services;

use App\Models\Company_End_Client;
use App\Models\Company_Warehouse;
use App\Models\Task;
use App\Models\Task_Points;
use App\Models\TaskB2bDetail;
use App\Models\Form_Template;
use App\Models\Order;
use App\Helpers\FileHelper;
use App\Helpers\IpHelper;
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
     * يدعم إنشاء مهام متعددة (Quantity) وتكرار الملفات.
     */
    public function createTask(array $data)
    {
        return DB::transaction(function () use ($data) {
            $quantity = $data['quantity'] ?? 1;
            $warehouse = Company_Warehouse::findOrFail($data['warehouse_id']);
            $endClient = Company_End_Client::findOrFail($data['end_client_id']);

            // 1. احسب السعر (نفس السعر لكل المهام في هذه الدفعة)
            $pricing = $this->calculatePrice(
                $data['company_id'],
                $data['warehouse_id'],
                $data['end_client_id'],
                $data['vehicle_size_id']
            );

            // 2. إنشاء مجموعة مهام (Order) إذا كان العدد أكبر من 1
            $orderId = null;
            if ($quantity > 1) {
                $order = Order::create([
                    'customer_id' => $data['company_id'],
                    'user_id'     => auth()->id(),
                    'status'      => 'in progress',
                ]);
                $orderId = $order->id;
            }

            // 3. معالجة الحقول الإضافية الأساسية (لأول مهمة أو كأساس)
            $baseAdditionalData = $this->processAdditionalFields($data['template'] ?? null, $data['additional_fields'] ?? []);

            $tasks = [];

            for ($i = 0; $i < $quantity; $i++) {
                // تكرار الملفات للمهام الإضافية
                $currentAdditionalData = $baseAdditionalData;
                if ($i > 0) {
                    $currentAdditionalData = $this->duplicateFilesInData($baseAdditionalData);
                }

                // أنشئ المهمة الأساسية
                $task = Task::create([
                    'order_id'             => $orderId,
                    'customer_id'          => $data['company_id'],
                    'vehicle_size_id'      => $data['vehicle_size_id'],
                    'form_template_id'     => $data['template'] ?? null,
                    'additional_data'      => $currentAdditionalData,
                    'company_warehouse_id' => $warehouse->id,
                    'company_end_client_id' => $endClient->id,
                    'total_price'          => $pricing['total_price'],
                    'pricing_type'         => 'b2b',
                    'status'               => 'in_progress',
                    'conditions'           => $data['conditions'] ?? null,
                    'user_id'              => auth()->id(),
                    'created_at'           => $data['created_at'] ?? now(),
                ]);

                // أنشئ نقطة الاستلام
                $pickupPoint = $this->buildPickupSnapshot($warehouse);
                Task_Points::create(array_merge($pickupPoint, [
                    'task_id'        => $task->id,
                    'type'           => 'pickup',
                    'sequence'       => 1,
                    'scheduled_time' => $data['pickup_before'] ?? now()->addHours(1),
                    'note'           => $data['pickup_note'] ?? null,
                ]));

                // أنشئ نقطة التسليم
                $deliveryPoint = $this->buildDeliverySnapshot($endClient);
                Task_Points::create(array_merge($deliveryPoint, [
                    'task_id'        => $task->id,
                    'type'           => 'delivery',
                    'sequence'       => 2,
                    'scheduled_time' => $data['delivery_before'] ?? now()->addHours(3),
                    'note'           => $data['delivery_note'] ?? null,
                ]));

                // أنشئ سجل الـ B2B detail
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
                    'pickup_name'     => $warehouse->contact_name,
                    'pickup_phone'    => $warehouse->contact_phone,
                    'pickup_address'  => $warehouse->address,
                    'pickup_lat'      => $warehouse->latitude,
                    'pickup_lng'      => $warehouse->longitude,
                    'delivery_name'    => $endClient->name,
                    'delivery_phone'   => $endClient->phone,
                    'delivery_address' => $endClient->address,
                    'delivery_lat'     => $endClient->latitude,
                    'delivery_lng'     => $endClient->longitude,
                ]);

                // إضافة حركة السجل
                $task->history()->create([
                    'action_type' => 'creation',
                    'description' => 'create B2B task manual' . ($quantity > 1 ? " (Part of Group #$orderId)" : ""),
                    'ip'          => IpHelper::getUserIpAddress(),
                    'user_id'     => auth()->id(),
                ]);

                $tasks[] = $task;
            }

            return $quantity > 1 ? $tasks : $tasks[0];
        });
    }

    /**
     * تكرار الملفات الموجودة في مصفوفة البيانات الإضافية فيزيائياً.
     */
    private function duplicateFilesInData(array $additionalData): array
    {
        $newData = $additionalData;
        foreach ($newData as $key => $field) {
            if (in_array($field['type'], ['file', 'image', 'file_expiration_date']) && !empty($field['value'])) {
                $newPath = FileHelper::duplicateFile($field['value'], 'tasks/files');
                if ($newPath) {
                    $newData[$key]['value'] = $newPath;
                }
            }
        }
        return $newData;
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
                'form_template_id'      => $data['template'] ?? $task->form_template_id,
                'additional_data'       => isset($data['additional_fields']) 
                                            ? $this->processAdditionalFields($data['template'] ?? $task->form_template_id, $data['additional_fields'], $task->additional_data)
                                            : $task->additional_data,
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

    /**
     * معالجة الحقول الإضافية (الملفات، النصوص، إلخ) بناءً على القالب.
     */
    private function processAdditionalFields(?int $templateId, array $inputData, $existingData = []): array
    {
        if (!$templateId) return [];

        $structuredFields = is_array($existingData) ? $existingData : [];
        $template = Form_Template::with('fields')->find($templateId);
        if (!$template) return $structuredFields;

        foreach ($template->fields as $field) {
            $fieldName = $field->name;
            $fieldType = $field->type;

            if ($fieldType === 'file_expiration_date') {
                $fileKey = "{$fieldName}_file";
                $expKey  = "{$fieldName}_expiration";

                if (isset($inputData[$fileKey]) && $inputData[$fileKey] instanceof \Illuminate\Http\UploadedFile) {
                    $path = FileHelper::uploadFile($inputData[$fileKey], 'tasks/files');
                    $structuredFields[$fieldName] = [
                        'label'      => $field->label,
                        'value'      => $path,
                        'expiration' => $inputData[$expKey] ?? null,
                        'type'       => $fieldType,
                    ];
                } elseif (isset($inputData[$expKey])) {
                    $structuredFields[$fieldName] = [
                        'label'      => $field->label,
                        'value'      => $structuredFields[$fieldName]['value'] ?? null,
                        'expiration' => $inputData[$expKey],
                        'type'       => $fieldType,
                    ];
                }
            } elseif (in_array($fieldType, ['file', 'image'])) {
                if (isset($inputData[$fieldName]) && $inputData[$fieldName] instanceof \Illuminate\Http\UploadedFile) {
                    $path = FileHelper::uploadFile($inputData[$fieldName], 'tasks/files');
                    $structuredFields[$fieldName] = [
                        'label' => $field->label,
                        'value' => $path,
                        'type'  => $fieldType,
                    ];
                }
            } else {
                if (isset($inputData[$fieldName])) {
                    $structuredFields[$fieldName] = [
                        'label' => $field->label,
                        'value' => $inputData[$fieldName],
                        'type'  => $fieldType,
                    ];
                }
            }
        }

        return $structuredFields;
    }

    /**
     * تكرار مهمة B2B بكل تفاصيلها (Snapshots) وملفاتها.
     */
    public function duplicateTask(Task $originalTask): Task
    {
        return DB::transaction(function () use ($originalTask) {
            // 1. استنساخ المهمة الأساسية
            $newTaskData = $originalTask->toArray();

            // إزالة الحقول التي لا نريد تكرارها
            unset(
                $newTaskData['id'],
                $newTaskData['created_at'],
                $newTaskData['updated_at'],
                $newTaskData['completed_at'],
                $newTaskData['closed_at'],
                $newTaskData['distribution_attempts'],
                $newTaskData['last_attempt_at']
            );

            // تعيين قيم افتراضية للتكرار
            $newTaskData['status']           = 'in_progress';
            $newTaskData['driver_id']        = null;
            $newTaskData['pending_driver_id'] = null;
            $newTaskData['team_id']           = null;
            $newTaskData['user_id']          = auth()->id();
            $newTaskData['closed']           = false;
            $newTaskData['payment_status']   = 'waiting';
            $newTaskData['payment_paid']     = 'pending';
            $newTaskData['order_id']         = null; // كفصل المهمة عن الطلب الأصلي

            // تكرار الملفات في additional_data
            if (!empty($newTaskData['additional_data'])) {
                $newTaskData['additional_data'] = $this->duplicateFilesInData($newTaskData['additional_data']);
            }

            $newTask = Task::create($newTaskData);

            // 2. تكرار نقاط الاستلام والتسليم
            foreach ($originalTask->points as $point) {
                $newPointData = $point->toArray();
                unset($newPointData['id'], $newPointData['created_at'], $newPointData['updated_at']);
                $newPointData['task_id'] = $newTask->id;

                // تكرار الصورة في النقطة إذا وجدت
                if (!empty($point->image)) {
                    $newImagePath = FileHelper::duplicateFile($point->image, 'tasks/duplicated/points');
                    $newPointData['image'] = $newImagePath ?: null;
                }

                Task_Points::create($newPointData);
            }

            // 3. تكرار سجل B2B detail
            if ($originalTask->b2bDetail) {
                $newB2bData = $originalTask->b2bDetail->toArray();
                unset($newB2bData['id'], $newB2bData['created_at'], $newB2bData['updated_at']);
                $newB2bData['task_id'] = $newTask->id;

                TaskB2bDetail::create($newB2bData);
            }

            // 4. إضافة حركة السجل
            $newTask->history()->create([
                'action_type' => 'creation',
                'description' => 'B2B Task duplicated from #' . $originalTask->id,
                'ip'          => IpHelper::getUserIpAddress(),
                'user_id'     => auth()->id(),
            ]);

            return $newTask;
        });
    }
}
