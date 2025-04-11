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
    Schema::create('tags_customers', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('tag_id');
      $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
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
    Schema::dropIfExists('tags_customers');
  }
};
