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
    Schema::create('tasks_points', function (Blueprint $table) {
      $table->id();
      $table->enum('type', ['pickup', 'delivery']);
      $table->integer('sequence')->default(0);
      $table->string('contact_name');
      $table->string('contact_phone');
      $table->string('contact_emil')->nullable();
      $table->string('address');
      $table->decimal('latitude', 10, 8);
      $table->decimal('longitude', 11, 8);
      $table->text('note')->nullable();
      $table->string('image')->nullable();
      $table->dateTime('scheduled_time')->nullable();
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
    Schema::dropIfExists('tasks_points');
  }
};
