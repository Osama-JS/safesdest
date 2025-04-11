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
    Schema::create('days_work', function (Blueprint $table) {
      $table->id();
      $table->time('start_time');
      $table->time('end_time');
      $table->boolean('day_off')->default(false);
      $table->unsignedBigInteger('day_id');
      $table->foreign('day_id')->references('id')->on('days')->onDelete('cascade');
      $table->unsignedBigInteger('driver_id');
      $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('days_work');
  }
};
