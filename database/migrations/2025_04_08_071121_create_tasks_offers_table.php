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
    Schema::create('tasks_offers', function (Blueprint $table) {
      $table->id();
      $table->decimal('price', 10, 2);
      $table->string('description');
      $table->boolean('accepted')->default(false);
      $table->unsignedBigInteger('task_ad_id');
      $table->foreign('task_ad_id')->references('id')->on('tasks_ads')->onDelete('restrict');
      $table->unsignedBigInteger('driver_id');
      $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('restrict');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tasks_offers');
  }
};
