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
    Schema::create('pointes', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('contact_name')->nullable();
      $table->string('contact_phone')->nullable();
      $table->string('address')->nullable();
      $table->decimal('latitude', 10, 8);
      $table->decimal('longitude', 11, 8);
      $table->boolean('status')->default(1);
      $table->boolean('privet')->default(0);
      $table->unsignedBigInteger('customer_id')->nullable();
      $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('pointes');
  }
};
