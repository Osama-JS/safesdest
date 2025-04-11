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
    Schema::create('pricing_vehicle', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('pricing_template_id');
      $table->foreign('pricing_template_id')->references('id')->on('pricing_templates')->onDelete('cascade');
      $table->unsignedBigInteger('vehicle_size_id');
      $table->foreign('vehicle_size_id')->references('id')->on('vehicle_sizes')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('pricing_vehicle');
  }
};
