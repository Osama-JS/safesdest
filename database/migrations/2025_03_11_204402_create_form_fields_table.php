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
    Schema::create('form_fields', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->enum('type', ['number', 'string', 'email', 'select', 'date']);
      $table->text('value')->nullable();
      $table->boolean('required')->default(1);
      $table->enum('driver_can', ['read & write', 'read', 'hidden']);
      $table->enum('customer_can', ['read & write', 'read', 'hidden']);
      $table->unsignedBigInteger('from_template_id');
      $table->foreign('from_template_id')->references('id')->on('form_templates')->onDelete('restrict');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('form_fields');
  }
};
