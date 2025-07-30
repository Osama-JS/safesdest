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
    Schema::create('clearance_pricing_customer', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('clearance_pricing_template_id');
      $table->foreign('clearance_pricing_template_id')->references('id')->on('clearance_pricing_template')->onDelete('cascade');
      $table->unsignedBigInteger('customer_id');
      $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('clearance_pricing_customer');
  }
};
