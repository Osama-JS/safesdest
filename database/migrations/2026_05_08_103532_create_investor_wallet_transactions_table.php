<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('investor_wallet_id');
            $table->foreign('investor_wallet_id')->references('id')->on('investor_wallets')->onDelete('restrict');
            $table->unsignedBigInteger('task_id')->nullable();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');
            $table->enum('transaction_type', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable(); // Admin أو المستثمر نفسه
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');
            $table->decimal('balance_after', 12, 2); // snapshot للرصيد بعد العملية
            $table->timestamps();

            $table->index(['investor_wallet_id', 'transaction_type']);
            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_wallet_transactions');
    }
};
