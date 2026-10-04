<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
                'event_key'      => 'whatsapp_message_received',
                'category'       => 'communication',
                'name_ar'        => 'استلام رسالة واتساب جديدة',
                'name_en'        => 'New WhatsApp Message Received',
                'description_ar' => 'يُطلق فور تلقي رسالة واتساب جديدة من عميل أو سائق على رقم المنصة المعتمد لتنبيه المسؤولين للرد الفوري والمباشر.',
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
        AdminNotificationSetting::where('event_key', 'whatsapp_message_received')->delete();
    }
};
