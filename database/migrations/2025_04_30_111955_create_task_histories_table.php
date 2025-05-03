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
    Schema::create('task_histories', function (Blueprint $table) {
      $table->id();
      $table->enum('action_type', ['update', 'added', 'pending_payment', 'payment_failed', 'advertised', 'in_progress', 'assign', 'accepted', 'start', 'completed', 'canceled']);
      $table->text('description')->nullable();
      $table->string('file_path')->nullable();
      $table->string('file_type')->nullable();
      $table->unsignedBigInteger('task_id');
      $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
      $table->unsignedBigInteger('driver_id')->nullable();
      $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
      $table->unsignedBigInteger('user_id')->nullable();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
      $table->timestamps();

      // ✅ Indexes
      $table->index('task_id');
      $table->index('action_type');
      $table->index('driver_id');
      $table->index('user_id');
      $table->index(['task_id', 'created_at']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('task_histories');
  }
};
