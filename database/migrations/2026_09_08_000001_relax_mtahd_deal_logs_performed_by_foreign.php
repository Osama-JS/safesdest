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
        Schema::table('mtahd_deal_logs', function (Blueprint $table) {
            // إسقاط قيد المفتاح الأجنبي على performed_by لضمان عدم حدوث تعارض عند تنفيذ عمليات من قبل العملاء أو السائقين
            $table->dropForeign(['performed_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mtahd_deal_logs', function (Blueprint $table) {
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
