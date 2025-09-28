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
    Schema::create('user_wallets', function (Blueprint $table) {
      $table->id()->startingValue(1000);
      $table->enum('user_type', ['user']); // مطابق لجدول wallets لكن للمستخدمين فقط
      $table->decimal('debt_ceiling', 10, 2)->default(5000);
      $table->boolean('status')->default(0);
      $table->boolean('preview')->default(0);
      $table->unsignedBigInteger('user_id');
      $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
      $table->softDeletes();
      $table->timestamps();

      // فهرس فريد لضمان محفظة واحدة لكل مستخدم
      $table->unique(['user_id', 'deleted_at'], 'user_wallet_unique');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('user_wallets');
  }
};
