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
    Schema::create('notifications_customers', function (Blueprint $table) {
      $table->id();
      $table->boolean('status')->default(false);
      $table->unsignedBigInteger('notification_id');
      $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
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
    Schema::dropIfExists('notifications_customers');
  }
};
