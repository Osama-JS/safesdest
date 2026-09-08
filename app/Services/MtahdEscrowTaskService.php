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

        if (!empty($configuredNumber) && !in_array($configuredNumber, ['CUST_SAFEDESTS_PLATFORM', 'CUST_SAFEDESTS_MAIN', 'IC-000000009'])) {
            return $configuredNumber;
        }

        $platformCustomerNumber = Settings::where('key', 'mtahd_platform_customer_number')->value('value');

        if ($platformCustomerNumber && $platformCustomerNumber !== 'IC-000000009') {
            return $platformCustomerNumber;
        }

        // إنشاء أو جلب حساب العميل الممثل للمنصة كبائع معتمد في منصة أمن
        $res = $this->mtahdService->createCustomer([
            'first_name'   => 'سيف ديست',
            'last_name'    => 'للخدمات اللوجستية',
            'phone_code'   => 'SA',
            'phone_number' => '500000001',
            'email'        => 'finance@safedests.com',
            'type'         => 'company',
        ]);

        $sellerNumber = $res['data']['number'] ?? ($res['data']['customer_number'] ?? ($res['details']['number'] ?? null));

        if ($sellerNumber) {
            Settings::updateOrCreate(
                ['key' => 'mtahd_platform_customer_number'],
                ['value' => $sellerNumber]
            );
            return $sellerNumber;
        }

        return 'IC-000000115';
    }

    /**
     * التأكد من وجود العميل كـ Customer في أمن وتخزين رقمه
     */
    public function ensureCustomerAmnnNumber(Customer $customer): string
    {
        if (!empty($customer->amnn_customer_number)) {
            return $customer->amnn_customer_number;
        }

        $phone = $customer->phone ?? ($customer->phone_number ?? '');

        $customerData = [
            'name'         => $customer->name ?: 'عميل سيف ديست',
            'phone_number' => $phone,
            'email'        => $customer->email ?: "customer_{$customer->id}@safedests.com",
            'type'         => $customer->is_company ? 'company' : 'individual',
        ];

        $res = $this->mtahdService->createCustomer($customerData);

        // منصة أمن ترجع الرقم تحت المفتاح number (مثل: IC-000000107) أو customer_number
        $customerNumber = $res['data']['number'] 
                       ?? ($res['data']['customer_number'] 
                       ?? ($res['details']['number'] 
                       ?? ($res['details']['customer_number'] ?? null)));

        if ($customerNumber) {
            $customer->update(['amnn_customer_number' => $customerNumber]);
            return $customerNumber;
        }

        // إذا تعذر إنشاء رقم رسمي في المنصة، نستخدم معرف العميل الداخلي لتفادي تعطيل العملية
        $fallback = 'CUST_' . $customer->id . '_' . time();
        $customer->update(['amnn_customer_number' => $fallback]);
        return $fallback;
    }

    /**
     * إنشاء صفقة ضمان مالي كاملة لمهمة (Create, Add Parties, Submit, Approve & Online Payment)
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
                'title'                => "ضمان مالي لمهمة توصيل #{$task->id}",
                'description'          => "خدمات نقل وشحن عبر منصة سيف ديست للمهمة رقم {$task->id}" . ($task->customer_task_number ? " (رقم الشحنة: {$task->customer_task_number})" : ""),
                'amount'               => $amount,
                'currency'             => 'SAR',
                'offer_category'       => 1, // Category 1 = Service
                'offer_type'           => 'service',
                'deal_subject_details' => "خدمات نقل وشحن وتوصيل الشحنة للمهمة رقم {$task->id} بحالة سليمة",
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

            // 4. إرسال الصفقة للاعتماد (Submit Deal -> requested)
            $submitRes = $this->mtahdService->submitDeal($dealNumber, $task->id);
            if (!$submitRes['status']) {
                Log::warning("Mtahd submitDeal warning for task #{$task->id}: " . ($submitRes['error'] ?? ''));
            }

            // 5. موافقة البائع وتحديد السعر النهائي (Approve Deal -> payment_pending)
            $approveRes = $this->mtahdService->approveDeal($dealNumber, $amount, $task->id);
            if (!$approveRes['status']) {
                Log::warning("Mtahd approveDeal warning for task #{$task->id}: " . ($approveRes['error'] ?? ''));
            }

            // 6. إنشاء جلسة الدفع الإلكتروني عبر HyperPay
            $payRes = $this->mtahdService->makePaymentOnline($dealNumber, 'mada', $task->id);
            $checkoutId = $payRes['checkout_id'] ?? null;

            // 7. حفظ البيانات في المهمة
            $task->update([
                'payment_method'   => 'mtahd',
                'is_escrow'        => true,
                'amnn_deal_number' => $dealNumber,
                'amnn_deal_id'     => $dealId ? (string)$dealId : null,
                'amnn_deal_status' => 'pending_payment',
            ]);

            return [
                'status'       => true,
                'deal_number'  => $dealNumber,
                'deal_id'      => $dealId,
                'checkout_id'  => $checkoutId,
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
