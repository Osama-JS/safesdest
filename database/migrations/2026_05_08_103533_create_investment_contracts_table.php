<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // المستثمر
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // نوع الاستثمار
            $table->enum('contract_type', ['task_investment', 'general_investment']);

            // شروط العمولة
            $table->enum('commission_type', ['percentage', 'fixed']);
            $table->decimal('commission_value', 10, 2); // نسبة مئوية أو مبلغ ثابت

            // مدة العقد
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = مفتوح

            // حالة العقد
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');

            // إعدادات الاستثمار بالمهام فقط
            $table->decimal('min_commission_threshold', 10, 2)->nullable(); // حد أدنى لعمولة المهمة
            $table->json('filter_customer_ids')->nullable(); // قائمة IDs العملاء المخصصين

            // معلومات إدارية
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by'); // Admin الذي أنشأ العقد
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_contracts');
    }
};
