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
    Schema::create('pricing_geofences', function (Blueprint $table) {
      $table->id();
      $table->enum('type', ['fixed', 'percentage']);
      $table->decimal('amount', 10, 2);
      $table->unsignedBigInteger('pricing_template_id');
      $table->foreign('pricing_template_id')->references('id')->on('pricing_templates')->onDelete('cascade');
      $table->unsignedBigInteger('geofence_id');
      $table->foreign('geofence_id')->references('id')->on('geofences')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('pricing_geofences');
  }
};
