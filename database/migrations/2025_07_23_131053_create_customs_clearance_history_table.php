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
    Schema::create('customs_clearance_history', function (Blueprint $table) {
      $table->id();
      $table->string('action_type');
      $table->text('description')->nullable();
      $table->string('file_path')->nullable();
      $table->string('file_type')->nullable();
      $table->string('ip')->nullable();
      $table->unsignedBigInteger('customs_clearance_id');
      $table->foreign('customs_clearance_id')->references('id')->on('customs_clearance')->onDelete('cascade');
      $table->unsignedBigInteger('clearance_agent_id')->nullable();
      $table->foreign('clearance_agent_id')->references('id')->on('customers')->onDelete('set null');
      $table->unsignedBigInteger('user_id')->nullable();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
      $table->timestamps();

      $table->index('customs_clearance_id');
      $table->index('action_type');
      $table->index('clearance_agent_id');
      $table->index('user_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('customs_clearance_history');
  }
};
