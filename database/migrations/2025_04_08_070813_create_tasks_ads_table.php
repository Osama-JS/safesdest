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
    Schema::create('tasks_ads', function (Blueprint $table) {
      $table->id();
      $table->string('description');
      $table->enum('status', ['running', 'closed'])->default('running');
      $table->decimal('highest_price', 10, 2)->nullable();
      $table->decimal('lowest_price', 10, 2)->nullable();
      $table->unsignedBigInteger('task_id');
      $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tasks_ads');
  }
};
