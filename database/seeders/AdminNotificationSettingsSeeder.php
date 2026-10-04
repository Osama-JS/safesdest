<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminNotificationSetting;

class AdminNotificationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // ==========================================
            // 1. الأحداث المالية الحساسة (Financial)
            // ==========================================
            [
                'event_key'      => 'driver_withdrawal_requested',
                'category'       => 'financial',
                'name_ar'        => 'طلب سحب رصيد جديد من سائق',
                'name_en'        => 'Driver Withdrawal Requested',
                'description_ar' => 'يُطلق عند قيام سائق بطلب سحب مالي من رصيد محفظته عبر التطبيق.',
                'in_app_enabled' => true,
                'email_enabled'  => true,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'high',
            ],
            [
                'event_key'      => 'payout_approval_required',
                'category'       => 'financial',
                'name_ar'        => 'طلب دفع Payout بانتظار المصادقة',
                'name_en'        => 'Payout Approval Required',
                'description_ar' => 'يُطلق عند إنشاء طلب دفع بنكي (تسوية مستحقات أو سحب يدوي) يتطلب مصادقة وكلمة مرور المدير.',
                'in_app_enabled' => true,
                'email_enabled'  => true,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'high',
            ],
            [
                'event_key'      => 'payout_status_updated',
                'category'       => 'financial',
                'name_ar'        => 'تحديث نتيجة تحويل Payout البنكي',
                'name_en'        => 'Payout Bank Transfer Status Updated',
                'description_ar' => 'يُطلق عند استلام استجابة Webhook من البوابة بنجاح التحويل المالي أو فشله.',
                'in_app_enabled' => true,
                'email_enabled'  => true,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'high',
            ],
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

            // ==========================================
            // 2. أحداث المهام والرحلات (Tasks)
            // ==========================================
            [
                'event_key'      => 'task_created',
                'category'       => 'tasks',
                'name_ar'        => 'إنشاء مهمة جديدة في المنصة',
                'name_en'        => 'New Task Created',
                'description_ar' => 'يُطلق عند قيام عميل أو مستخدم بإنشاء مهمة توصيل جديدة في النظام.',
                'in_app_enabled' => true,
                'email_enabled'  => false,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'normal',
            ],
            [
                'event_key'      => 'task_cancellation_requested',
                'category'       => 'tasks',
                'name_ar'        => 'طلب إلغاء مهمة من السائق أو العميل',
                'name_en'        => 'Task Cancellation Requested',
                'description_ar' => 'يُطلق عندما يطلب السائق أو العميل إلغاء المهمة مع تحديد سبب الإلغاء.',
                'in_app_enabled' => true,
                'email_enabled'  => true,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'high',
            ],
            [
                'event_key'      => 'task_status_changed',
                'category'       => 'tasks',
                'name_ar'        => 'تغيير حالة مهمة',
                'name_en'        => 'Task Status Changed',
                'description_ar' => 'يُطلق عند انتقال المهمة بين الحالات (تعيين، قبول، إقفال، إرجاع).',
                'in_app_enabled' => true,
                'email_enabled'  => false,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'normal',
            ],
            [
                'event_key'      => 'task_offer_created',
                'category'       => 'tasks',
                'name_ar'        => 'عرض سعر جديد على إعلان مهمة',
                'name_en'        => 'New Offer on Task Ad',
                'description_ar' => 'يُطلق عند تقديم سائق عرض سعر جديد للمهمة المعلنة.',
                'in_app_enabled' => true,
                'email_enabled'  => false,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'normal',
            ],
            [
                'event_key'      => 'task_offer_accepted',
                'category'       => 'tasks',
                'name_ar'        => 'قبول عرض سائق للمهمة',
                'name_en'        => 'Task Offer Accepted',
                'description_ar' => 'يُطلق عند قبول عرض السائق على المهمة.',
                'in_app_enabled' => true,
                'email_enabled'  => false,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'normal',
            ],

            // ==========================================
            // 3. أحداث المستخدمين والشركاء (Users)
            // ==========================================
            [
                'event_key'      => 'customer_registered',
                'category'       => 'users',
                'name_ar'        => 'تسجيل عميل جديد',
                'name_en'        => 'New Customer Registered',
                'description_ar' => 'يُطلق عند تسجيل حساب عميل/شركة جديد في المنصة.',
                'in_app_enabled' => true,
                'email_enabled'  => true,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'normal',
            ],
            [
                'event_key'      => 'driver_registered',
                'category'       => 'users',
                'name_ar'        => 'تسجيل سائق جديد',
                'name_en'        => 'New Driver Registered',
                'description_ar' => 'يُطلق عند انضمام سائق جديد للمنصة واستخراج الكود الخاص به.',
                'in_app_enabled' => true,
                'email_enabled'  => true,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'normal',
            ],
            [
                'event_key'      => 'team_created',
                'category'       => 'users',
                'name_ar'        => 'إنشاء فريق عمل جديد',
                'name_en'        => 'New Team Created',
                'description_ar' => 'يُطلق عند تأسيس فريق جديد في النظام.',
                'in_app_enabled' => true,
                'email_enabled'  => true,
                'webpush_enabled'=> false,
                'target_roles'   => ['Owner', 'Admin'],
                'priority'       => 'normal',
            ],

            // ==========================================
            // 4. أحداث النظام والمستندات (System)
            // ==========================================
            [
                'event_key'      => 'file_expired',
                'category'       => 'system',
                'name_ar'        => 'انتهاء صلاحية وثيقة أو رخصة',
                'name_en'        => 'Document / License Expired',
                'description_ar' => 'يُطلق عند انتهاء صلاحية إقامة أو رخصة أو تأمين لسائق أو عميل.',
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
}
