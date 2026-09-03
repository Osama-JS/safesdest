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
        Schema::create('mtahd_deal_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->nullable()->index();
            $table->string('deal_number')->nullable()->index();
            $table->string('deal_id')->nullable()->index();
            $table->string('action')->index(); // create_customer, create_deal, add_parties, submit_deal, release_funds, cancel_deal, get_deal, webhook_received
            $table->string('status')->default('pending')->index(); // success, failed, pending, info
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 10)->default('SAR');
            $table->string('buyer_info')->nullable();
            $table->string('seller_info')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->integer('http_status')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Additional indexes
            $table->index(['deal_number', 'action']);
            $table->index(['task_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtahd_deal_logs');
    }
};
