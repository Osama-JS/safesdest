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
    Schema::create('customs_clearance_offers', function (Blueprint $table) {
      $table->id();
      $table->decimal('price', 10, 2);
      $table->string('description');
      $table->boolean('accepted')->default(false);
      $table->jsonb('additional_data')->nullable();

      $table->unsignedBigInteger('customs_clearance_id');
      $table->foreign('customs_clearance_id')->references('id')->on('customs_clearance')->onDelete('restrict');
      $table->unsignedBigInteger('clearance_agent_id');
      $table->foreign('clearance_agent_id')->references('id')->on('customers')->onDelete('restrict');
      $table->unsignedBigInteger('form_template_id')->nullable();
      $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('restrict');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('customs_clearance_offers');
  }
};
