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
        Schema::table('pricing_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_templates', 'service_commission_status')) {
                // إضافة حقل تفعيل/تعطيل العمولة
                $table->boolean('service_commission_status')->default(true)->after('service_tax_commission');
            }

            if (!Schema::hasColumn('pricing_templates', 'service_commission_type')) {
                // إضافة حقل نوع العمولة (ثابت أو نسبة)
                $table->enum('service_commission_type', ['fixed', 'percentage'])->default('percentage')->after('service_commission_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_templates', function (Blueprint $table) {
            $table->dropColumn(['service_commission_status', 'service_commission_type']);
        });
    }
};
