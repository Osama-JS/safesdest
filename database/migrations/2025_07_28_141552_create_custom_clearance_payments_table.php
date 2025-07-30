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
    Schema::create('custom_clearance_payments', function (Blueprint $table) {
      $table->id();
      $table->decimal('amount', 10, 2);
      $table->enum('payment_method', ['cash', 'gateway', 'banking']);
      $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
      $table->string('transaction_reference')->nullable();
      $table->string('gateway_name')->nullable();
      $table->text('gateway_response')->nullable();
      $table->unsignedBigInteger('customs_clearance_id');
      $table->foreign('customs_clearance_id')->references('id')->on('customs_clearance')->onDelete('cascade');
      $table->unsignedBigInteger('customer_id')->nullable();
      $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('custom_clearance_payments');
  }
};
