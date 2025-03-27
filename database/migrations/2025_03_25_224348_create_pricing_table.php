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
    Schema::create('pricing', function (Blueprint $table) {
      $table->id();
      $table->boolean('status')->default(1);
      $table->unsignedBigInteger('pricing_template_id');
      $table->foreign('pricing_template_id')->references('id')->on('pricing_templates')->onDelete('cascade');
      $table->unsignedBigInteger('pricing_method_id');
      $table->foreign('pricing_method_id')->references('id')->on('pricing_methods')->onDelete('restrict');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('pricing');
  }
};
