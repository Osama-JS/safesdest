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
        Schema::create('user_wallet_payment_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_wallet_id');
            $table->unsignedBigInteger('user_id'); // المستخدم المستفيد صاحب المحفظة
            $table->unsignedBigInteger('printed_by'); // الموظف الإداري الذي طبع الطلب
            $table->decimal('amount', 10, 2);
            $table->string('payment_request_number', 50);
            $table->string('payment_method', 50)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('iban_number', 34)->nullable();
            $table->text('other_payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('printed_at');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('user_wallet_id')->references('id')->on('user_wallets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('printed_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['user_wallet_id', 'printed_at']);
            $table->index(['user_id', 'printed_at']);
            $table->index('payment_request_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_wallet_payment_request_logs');
    }
};
