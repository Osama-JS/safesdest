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
    Schema::create('pricing_templates', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->integer('decimal_places');
      $table->decimal("base_fare", 10, 2)->nullable();
      $table->integer('base_waiting_time');
      $table->decimal('waiting_fare', 10, 2);
      $table->integer('base_distance');
      $table->decimal('distance_fare', 10, 2);
      $table->decimal('discount_percentage', 10, 2);
      $table->decimal('vat_commission', 10, 2);
      $table->decimal('service_tax_commission', 10, 2);
      $table->boolean('all_customer')->default(0);
      $table->unsignedBigInteger('form_template_id');
      $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('cascade');
      $table->timestamps();

      $table->unique('name', 'form_template_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('pricing_templates');
  }
};
