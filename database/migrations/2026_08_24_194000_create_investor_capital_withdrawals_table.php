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
        Schema::create('investor_capital_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('investor_wallet_id')->constrained('investor_wallets')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending'); // pending, approved, rejected, completed, cancelled
            $table->boolean('agreed_terms')->default(true);
            $table->text('investor_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('request_date')->useCurrent();
            $table->timestamp('scheduled_disbursement_date')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->foreignId('investor_wallet_transaction_id')->nullable()->constrained('investor_wallet_transactions')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_capital_withdrawals');
    }
};
