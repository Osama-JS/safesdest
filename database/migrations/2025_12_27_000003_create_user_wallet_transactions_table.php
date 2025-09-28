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
    Schema::create('user_wallet_transactions', function (Blueprint $table) {
      $table->id()->startingValue(1000);
      $table->unsignedBigInteger('sequence')->default(1);
      $table->decimal('amount', 10, 2);
      $table->enum('transaction_type', ['credit', 'debit']);
      $table->string('description');
      $table->string('image')->nullable();
      $table->boolean('status')->default(0);
      $table->timestamp('maturity_time')->nullable();
      $table->unsignedBigInteger('user_wallet_id');
      $table->foreign('user_wallet_id')->references('id')->on('user_wallets')->onDelete('restrict');
      $table->unsignedBigInteger('task_id')->nullable();
      $table->foreign('task_id')->references('id')->on('tasks')->onDelete('restrict');
      $table->unsignedBigInteger('user_id')->nullable();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
      $table->unsignedBigInteger('team_id')->nullable();
      $table->unsignedBigInteger('clearance_id')->nullable();
      $table->foreign('clearance_id')->references('id')->on('customs_clearance')->onDelete('restrict');
      $table->timestamps();

      $table->unique(['user_wallet_id', 'sequence'], 'user_wallet_sequence_unique');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('user_wallet_transactions');
  }
};
