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
        Schema::create('hyperpay_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->unique(); // Our internal reference (MT-...)
            $table->string('payout_id')->nullable(); // HyperPay payout ID
            $table->string('bulk_id')->nullable(); // HyperPay bulk ID
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->json('transaction_details')->nullable(); // Stores original transaction details for WP
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->text('failure_reason')->nullable();
            $table->string('payout_type', 10)->nullable(); // MT, WP, WD
            $table->unsignedBigInteger('source_withdrawal_id')->nullable(); // If type is WD
            
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('set null');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hyperpay_payouts');
    }
};
