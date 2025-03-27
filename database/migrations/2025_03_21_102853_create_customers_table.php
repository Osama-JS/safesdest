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
    Schema::create('customers', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('email')->unique();
      $table->string('phone')->unique();
      $table->string('image')->nullable();
      $table->string('password');
      $table->enum('status', ['active', 'verified', 'blocked'])->default('verified');
      $table->string('company_name')->nullable();
      $table->string('company_address')->nullable();
      $table->string('additional_data')->nullable();
      $table->unsignedBigInteger('form_template_id')->nullable();
      $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('set null');
      $table->unsignedBigInteger('team_id')->nullable();
      $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
      $table->unsignedBigInteger('role_id');
      $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('customers');
  }
};
