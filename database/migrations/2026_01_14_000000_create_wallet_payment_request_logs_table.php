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
        Schema::create('wallet_payment_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade')->comment('معرف المحفظة');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('المستخدم الذي طبع الطلب');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade')->comment('صاحب المحفظة');
            $table->decimal('amount', 10, 2)->comment('المبلغ المطلوب');
            $table->text('notes')->nullable()->comment('الملاحظات');
            $table->string('ip_address', 45)->comment('عنوان IP للجهاز');
            $table->timestamp('printed_at')->comment('تاريخ ووقت الطباعة الفعلية');
            $table->timestamps();
            
            // إضافة فهارس لتحسين الأداء
            $table->index(['wallet_id', 'printed_at'], 'wallet_printed_at_index');
            $table->index(['driver_id', 'printed_at'], 'driver_printed_at_index');
            $table->index(['user_id', 'printed_at'], 'user_printed_at_index');
            $table->index('printed_at', 'printed_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_payment_request_logs');
    }
};
