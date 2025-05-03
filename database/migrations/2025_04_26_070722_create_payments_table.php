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
    Schema::create('payments', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('task_id')->nullable();
      $table->unsignedBigInteger('customer_id')->nullable();
      $table->decimal('amount', 10, 2);
      $table->enum('payment_method', ['cash', 'gateway', 'banking']);
      $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
      $table->string('transaction_reference')->nullable();
      $table->string('gateway_name')->nullable();
      $table->text('gateway_response')->nullable();
      $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');
      $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('payments');
  }
};
