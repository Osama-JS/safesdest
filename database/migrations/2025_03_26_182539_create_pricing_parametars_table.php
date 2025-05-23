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
    Schema::create('pricing_parametars', function (Blueprint $table) {
      $table->id();
      $table->string('from_val');
      $table->string('to_val');
      $table->decimal('price', 10, 2);
      $table->unsignedBigInteger('pricing_id');
      $table->foreign('pricing_id')->references('id')->on('pricing')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('pricing_parametars');
  }
};
