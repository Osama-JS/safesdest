<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Settings;
use App\Services\MtahdService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * خدمة إدارة صفقات الضمان المالي للمهام عبر منصة متعهد (أمن)
 * تطبق نموذج: العميل (المشتري) ⟷ المنصة (البائع والجهة الضامنة)
 */
class MtahdEscrowTaskService
{
    protected MtahdService $mtahdService;

    public function __construct(MtahdService $mtahdService)
    {
        $this->mtahdService = $mtahdService;
    }

    /**
     * الحصول على أو إنشاء رقم حساب المنصة كبائع معتمد في أمن
     */
    public function ensurePlatformSellerNumber(): string
    {
        $configuredNumber = config('services.mtahd.platform_seller_number', env('MTAHD_PLATFORM_SELLER_NUMBER'));

        if (!empty($configuredNumber) && $configuredNumber !== 'CUST_SAFEDESTS_PLATFORM') {
            return $configuredNumber;
        }

        // في حال عدم وجود رقم مسبق أو في بيئة التجربة، نتأكد من وجود حساب المنصة كعميل بائع
        $platformCustomerNumber = Settings::where('key', 'mtahd_platform_customer_number')->value('value');

        if ($platformCustomerNumber) {
            return $platformCustomerNumber;
        }

        // إنشاء حساب المنصة في أمن لأول مرة
        $platformData = [
            'name'         => 'منصة سيف ديست للخدمات اللوجستية (SafeDests)',
            'phone_number' => '+966500000000',
            'email'        => 'finance@safedests.com',
            'type'         => 'company',
        ];

        $res = $this->mtahdService->createCustomer($platformData);

        if ($res['status'] && isset($res['data']['customer_number'])) {
            $sellerNumber = $res['data']['customer_number'];
            Settings::updateOrCreate(
                ['key' => 'mtahd_platform_customer_number'],
                ['value' => $sellerNumber]
            );
            return $sellerNumber;
        }

        // Fallback default identifier
        return $configuredNumber ?: 'CUST_SAFEDESTS_MAIN';
    }

    /**
     * التأكد من وجود العميل كـ Customer في أمن وتخزين رقمه
     */
    public function ensureCustomerAmnnNumber(Customer $customer): string
    {
        if (!empty($customer->amnn_customer_number)) {
            return $customer->amnn_customer_number;
        }

        // معالجة رقم الجوال للتأكد من الصيغة الدولية
        $phone = $customer->phone ?? ($customer->phone_number ?? '');
        if (!str_starts_with($phone, '+')) {
            $phone = '+966' . ltrim($phone, '0');
        }

        $customerData = [
            'name'         => $customer->name ?: 'عميل سيف ديست',
            'phone_number' => $phone,
            'email'        => $customer->email ?: "customer_{$customer->id}@safedests.com",
            'type'         => $customer->is_company ? 'company' : 'individual',
        ];

        $res = $this->mtahdService->createCustomer($customerData);

        if ($res['status'] && isset($res['data']['customer_number'])) {
            $customerNumber = $res['data']['customer_number'];
            $customer->update(['amnn_customer_number' => $customerNumber]);
            return $customerNumber;
        }

        // في حال تم إنشاؤه مسبقاً برقم الهاتف وأرجع الـ API الرقم الموجود
        if (isset($res['details']['customer_number'])) {
            $customerNumber = $res['details']['customer_number'];
            $customer->update(['amnn_customer_number' => $customerNumber]);
            return $customerNumber;
        }

        // إذا تعذر إنشاء رقم رسمي، نستخدم معرف العميل الداخلي
        $fallback = 'CUST_' . $customer->id . '_' . time();
        $customer->update(['amnn_customer_number' => $fallback]);
        return $fallback;
    }

    /**
     * إنشاء صفقة ضمان مالي كاملة لمهمة (Create, Add Parties, Submit & Get Payment URL)
     */
    public function createEscrowDealForTask(Task $task): array
    {
        try {
            $customer = $task->customer;
            if (!$customer) {
                return ['status' => false, 'error' => 'العميل غير موجود في المهمة'];
            }

            $amount = floatval($task->total_price);
            if ($amount <= 0) {
                return ['status' => false, 'error' => 'مبلغ المهمة غير صحيح'];
            }

            // 1. جلب أرقام الأطراف (العميل = المشتري، المنصة = البائع)
            $buyerNumber = $this->ensureCustomerAmnnNumber($customer);
            $sellerNumber = $this->ensurePlatformSellerNumber();

            // 2. إنشاء مسودة الصفقة (Deal)
            $dealPayload = [
                'title'       => "ضمان مالي لمهمة توصيل #{$task->id}",
                'description' => "خدمات نقل وشحن عبر منصة سيف ديست للمهمة رقم {$task->id}" . ($task->customer_task_number ? " (رقم الشحنة: {$task->customer_task_number})" : ""),
                'amount'      => $amount,
                'currency'    => 'SAR',
                'category'    => 'logistics_services',
                'custom_id'   => "TASK_{$task->id}",
            ];

            $dealRes = $this->mtahdService->createDeal($dealPayload, $task->id);
            if (!$dealRes['status']) {
                return ['status' => false, 'error' => $dealRes['error'] ?? 'فشل في إنشاء الصفقة في متعهد'];
            }

            $dealNumber = $dealRes['deal_number'] ?? null;
            $dealId = $dealRes['deal_id'] ?? null;

            if (!$dealNumber) {
                return ['status' => false, 'error' => 'لم يتم إرجاع رقم الصفقة من منصة متعهد'];
            }

            // 3. ربط أطراف الصفقة (Buyers & Sellers)
            $partiesRes = $this->mtahdService->addDealParties($dealNumber, [$buyerNumber], [$sellerNumber], $task->id);
            if (!$partiesRes['status']) {
                Log::warning("Mtahd addDealParties warning for task #{$task->id}: " . ($partiesRes['error'] ?? ''));
            }

            // 4. اعتماد الصفقة وطلب السداد (Submit Deal)
            $submitRes = $this->mtahdService->submitDeal($dealNumber, $task->id);
            if (!$submitRes['status']) {
                Log::warning("Mtahd submitDeal warning for task #{$task->id}: " . ($submitRes['error'] ?? ''));
            }

            // استخراج رابط الدفع المباشر
            $paymentUrl = $submitRes['data']['payment_url'] 
                       ?? ($submitRes['data']['checkout_url'] 
                       ?? "https://checkout.amnn.sa/pay/{$dealNumber}");

            // 5. حفظ البيانات في المهمة
            $task->update([
                'payment_method'   => 'mtahd',
                'is_escrow'        => true,
                'amnn_deal_number' => $dealNumber,
                'amnn_deal_id'     => $dealId ? (string)$dealId : null,
                'amnn_payment_url' => $paymentUrl,
                'amnn_deal_status' => 'pending_payment',
            ]);

            return [
                'status'       => true,
                'deal_number'  => $dealNumber,
                'payment_url'  => $paymentUrl,
                'amount'       => $amount,
                'message'      => 'تم إنشاء صفقة الضمان المالي في متعهد بنجاح'
            ];

        } catch (Exception $e) {
            Log::error("MtahdEscrowTaskService createEscrowDealForTask Exception [Task #{$task->id}]: " . $e->getMessage());
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * تحرير الضمان المالي عند إتمام تسليم المهمة
     */
    public function releaseTaskEscrow(Task $task): array
    {
        if (empty($task->amnn_deal_number)) {
            return ['status' => false, 'error' => 'لا يوجد رقم صفقة متعهد مرتبطة بهذه المهمة'];
        }

        if ($task->amnn_deal_status === 'released') {
            return ['status' => true, 'message' => 'تم تحرير الضمان مسبقاً'];
        }

        $res = $this->mtahdService->releaseFunds($task->amnn_deal_number, [
            'task_id' => $task->id,
            'amount'  => $task->total_price,
        ], $task->id);

        if ($res['status']) {
            $task->update(['amnn_deal_status' => 'released']);
        }

        return $res;
    }

    /**
     * إلغاء صفقة الضمان المالي واسترداد الأموال للعميل
     */
    public function cancelTaskEscrow(Task $task, ?string $reason = null): array
    {
        if (empty($task->amnn_deal_number)) {
            return ['status' => false, 'error' => 'لا يوجد رقم صفقة متعهد مرتبطة بهذه المهمة'];
        }

        if ($task->amnn_deal_status === 'cancelled') {
            return ['status' => true, 'message' => 'الصفقة ملغاة مسبقاً'];
        }

        $res = $this->mtahdService->cancelDeal($task->amnn_deal_number, $reason ?? "إلغاء المهمة رقم {$task->id}", $task->id);

        if ($res['status']) {
            $task->update(['amnn_deal_status' => 'cancelled']);
        }

        return $res;
    }
}
