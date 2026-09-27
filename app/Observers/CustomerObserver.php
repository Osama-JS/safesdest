<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\AdminNotificationDispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CustomerObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Customer $customer)
    {
        Log::info("CustomerObserver: New customer registered #{$customer->id}");

        $content = "تم تسجيل عميل جديد في المنصة:\n" .
                   "- الاسم: {$customer->name}\n" .
                   "- الجوال: {$customer->phone}\n" .
                   "- الشركة: " . ($customer->company_name ?? 'N/A');

        AdminNotificationDispatcher::dispatch(
            eventKey: 'customer_registered',
            title: "تسجيل عميل جديد: {$customer->name}",
            message: $content,
            actionUrl: url("/admin/customers"),
            extraData: [
                'customer_id'   => $customer->id,
                'customer_name' => $customer->name,
                'phone'         => $customer->phone,
            ]
        );
    }
}
