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
    Schema::create('tags_drivers', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('tag_id');
      $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
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
    Schema::dropIfExists('tags_drivers');
  }
};
