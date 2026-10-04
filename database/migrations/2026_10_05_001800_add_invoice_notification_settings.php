<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AdminNotificationSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            [
                'event_key'      => 'customer_invoice_due',
                'category'       => 'financial',
                'name_ar'        => 'استحقاق فاتورة محاسبية لعميل',
                'name_en'        => 'Customer Invoice Due Today',
                'description_ar' => 'يُطلق عند حلول موعد استحقاق فاتورة محاسبية غير مسددة بالكامل لعميل لتنبيه المحاسب والإدارة بسدادها.',
                'in_app_enabled' => true,
                'email_enabled'  => true,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'high',
            ],
            [
                'event_key'      => 'customer_invoice_overdue',
                'category'       => 'financial',
                'name_ar'        => 'تأخر سداد فاتورة محاسبية لعميل',
                'name_en'        => 'Customer Invoice Overdue',
                'description_ar' => 'يُطلق عند تجاوز فاتورة محاسبية لعميل موعد استحقاقها دون سدادها بالكامل لمتابعة التحصيل.',
                'in_app_enabled' => true,
                'email_enabled'  => true,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'high',
            ],
        ];

        foreach ($settings as $setting) {
            AdminNotificationSetting::updateOrCreate(
                ['event_key' => $setting['event_key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        AdminNotificationSetting::whereIn('event_key', [
            'customer_invoice_due',
            'customer_invoice_overdue'
        ])->delete();
    }
};
