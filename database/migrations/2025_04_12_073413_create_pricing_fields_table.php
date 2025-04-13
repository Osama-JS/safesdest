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
    Schema::create('pricing_fields', function (Blueprint $table) {
      $table->id();
      $table->string('value');
      $table->enum('option', ['equal', 'greater', 'less', 'not_equal', 'greater_equal', 'less_equal'])->default('equal');
      $table->enum('type', ['fixed', 'percentage']);
      $table->decimal('amount', 10, 2);
      $table->unsignedBigInteger('field_id');
      $table->foreign('field_id')->references('id')->on('form_fields')->onDelete('cascade');
      $table->unsignedBigInteger('pricing_id');
      $table->foreign('pricing_id')->references('id')->on('pricing_templates')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('pricing_fields');
  }
};
