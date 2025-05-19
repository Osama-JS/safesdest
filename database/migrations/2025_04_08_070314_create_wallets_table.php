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
    Schema::create('wallets', function (Blueprint $table) {
      $table->id()->startingValue(1000);
      $table->enum('user_type', ['customer', 'driver']);
      $table->decimal('debt_ceiling', 10, 2)->default(5000);
      $table->boolean('status')->default(0);
      $table->boolean('preview')->default(0);
      $table->bigInteger('customer_id')->nullable();
      $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
      $table->bigInteger('driver_id')->nullable();
      $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('restrict');
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('wallets');
  }
};
