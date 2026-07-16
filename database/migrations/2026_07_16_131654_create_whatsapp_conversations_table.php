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
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique()->comment('رقم هاتف العميل مع رمز الدولة');
            $table->string('user_type')->nullable()->comment('نوع المستخدم: customer, driver');
            $table->unsignedBigInteger('user_id')->nullable()->comment('معرف المستخدم في النظام');
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_time')->nullable();
            $table->integer('unread_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
