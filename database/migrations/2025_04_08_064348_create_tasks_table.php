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
    Schema::create('tasks', function (Blueprint $table) {
      $table->id();
      $table->enum('status', ['in progress', 'assign', 'accepted', 'start', 'completed', 'canceled'])->default('in progress');
      $table->enum('pricing_type', ['dynamic', 'manual'])->default('dynamic');
      $table->decimal('total_price', 10, 2)->default(0);
      $table->decimal('commission', 10, 2)->default(0);
      $table->enum('pyment_method', ['cash', 'gateway', 'banking'])->default('cash');
      $table->enum('payment_status', ['waiting', 'completed', 'just commission'])->default('pending');
      $table->string('additional_data')->nullable();
      $table->string('pricing_history')->nullable();
      $table->unsignedBigInteger('order_id')->nullable();
      $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
      $table->unsignedBigInteger('customer_id')->nullable();
      $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
      $table->unsignedBigInteger('driver_id')->nullable();
      $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('restrict');
      $table->unsignedBigInteger('form_template_id')->nullable();
      $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('restrict');
      $table->unsignedBigInteger('pricing_id')->nullable();
      $table->foreign('pricing_id')->references('id')->on('pricing_templates')->onDelete('restrict');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tasks');
  }
};
