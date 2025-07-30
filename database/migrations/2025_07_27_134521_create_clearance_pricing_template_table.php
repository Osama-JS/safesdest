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
    Schema::create('clearance_pricing_template', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->integer('decimal_places');
      $table->decimal('vat_commission', 10, 2)->default(0);
      $table->decimal('service_commission', 10, 2)->default(0);
      $table->boolean('service_commission_status')->default(true);
      $table->enum('service_commission_type', ['fixed', 'percentage'])->default('percentage');
      $table->boolean('all_customer')->default(0);
      $table->unsignedBigInteger('form_template_id');
      $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('cascade');

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('clearance_pricing_template');
  }
};
