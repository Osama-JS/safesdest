<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_expiration_notifications', function (Blueprint $table) {
            $table->id();
            
            // معلومات المستخدم
            $table->enum('user_type', ['user', 'customer', 'driver'])
                  ->comment('نوع المستخدم: user, customer, driver');
            $table->unsignedBigInteger('user_id')
                  ->comment('معرف المستخدم');
            
            // معلومات الملف
            $table->string('field_name', 100)
                  ->comment('اسم الحقل في additional_data');
            $table->string('field_label', 255)
                  ->comment('تسمية الحقل للعرض');
            $table->string('file_path', 500)->nullable()
                  ->comment('مسار الملف المرفوع');
            
            // معلومات التاريخ والانتهاء
            $table->date('expiration_date')
                  ->comment('تاريخ انتهاء صلاحية الملف');
            $table->date('notification_sent_date')
                  ->comment('تاريخ إرسال التنبيه');
            $table->integer('days_before_expiration')
                  ->comment('عدد الأيام قبل الانتهاء (قد يكون سالب إذا انتهى)');
            
            // معلومات الحالة والمستلمين
            $table->enum('status', ['sent', 'account_suspended'])
                  ->default('sent')
                  ->comment('حالة التنبيه: sent, account_suspended');
            $table->json('recipients')->nullable()
                  ->comment('قائمة المستلمين للتنبيه');
            
            // الطوابع الزمنية
            $table->timestamps();
            
            // الفهارس لتحسين الأداء
            $table->index(['user_type', 'user_id'], 'idx_user_type_id');
            $table->index('expiration_date', 'idx_expiration_date');
            $table->index('notification_sent_date', 'idx_notification_sent_date');
            $table->index('status', 'idx_status');
            
            // فهرس مركب لمنع التكرار
            $table->unique([
                'user_type', 
                'user_id', 
                'field_name', 
                'notification_sent_date'
            ], 'unique_daily_notification');
            
            // التعليقات على الجدول
            $table->comment('جدول تسجيل تنبيهات انتهاء صلاحية الملفات');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_expiration_notifications');
    }
};
