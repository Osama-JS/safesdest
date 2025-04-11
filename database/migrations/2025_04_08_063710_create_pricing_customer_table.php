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
    Schema::create('pricing_customer', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('pricing_template_id');
      $table->foreign('pricing_template_id')->references('id')->on('pricing_templates')->onDelete('cascade');
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
    Schema::dropIfExists('pricing_customer');
  }
};
