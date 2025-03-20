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
    Schema::create('vehicle_sizes', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->unsignedBigInteger('vehicle_type_id');
      $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onDelete('restrict');
      $table->timestamps();
      $table->unique(['name', 'vehicle_type_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('vehicle_sizes');
  }
};
