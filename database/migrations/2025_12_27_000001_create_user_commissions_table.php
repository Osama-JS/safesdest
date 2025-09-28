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
    Schema::create('user_commissions', function (Blueprint $table) {
      $table->id()->startingValue(1000);
      $table->unsignedBigInteger('user_id');
      $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
      $table->unsignedBigInteger('customer_id');
      $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
      $table->enum('commission_type', ['fixed', 'percentage'])->default('percentage');
      $table->decimal('commission_value', 10, 2)->default(0);
      $table->boolean('status')->default(1);
      $table->softDeletes();
      $table->timestamps();

      // فهرس مركب لضمان عدم تكرار ربط نفس المستخدم مع نفس العميل
      $table->unique(['user_id', 'customer_id', 'deleted_at'], 'user_customer_commission_unique');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('user_commissions');
  }
};
