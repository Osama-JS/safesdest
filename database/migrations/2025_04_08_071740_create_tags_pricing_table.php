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
    Schema::create('tags_pricing', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('tag_id');
      $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
      $table->unsignedBigInteger('pricing_template_id');
      $table->foreign('pricing_template_id')->references('id')->on('pricing_templates')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tags_pricing');
  }
};
