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
    Schema::create('team_wallet_transactions', function (Blueprint $table) {
      $table->id()->startingValue(1000);
      $table->unsignedBigInteger('sequence')->default(1);
      $table->decimal('amount', 10, 2);
      $table->enum('transaction_type', ['credit', 'debit']);
      $table->string('description');
      $table->string('image')->nullable();
      $table->timestamp('maturity_time')->nullable();
      $table->unsignedBigInteger('team_wallet_id');
      $table->foreign('team_wallet_id')->references('id')->on('team_wallet')->onDelete('restrict');
      $table->unsignedBigInteger('task_id')->nullable();
      $table->foreign('task_id')->references('id')->on('tasks')->onDelete('restrict');
      $table->unsignedBigInteger('user_id')->nullable();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
      $table->timestamps();

      $table->unique(['team_wallet_id', 'sequence'], 'team_wallet_sequence_unique');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('team_wallet_transactions');
  }
};
