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
    Schema::create('drivers', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('phone')->unique();
      $table->string('phone_code');
      $table->string('email')->unique();
      $table->string('image')->nullable();
      $table->string('username')->unique();
      $table->string('password');
      $table->enum('status', ['verified', 'active', 'blocked', 'pending'])->default('pending');
      $table->text('address');
      $table->boolean('online')->default(1);
      $table->decimal('longitude', 10, 2)->nullable();
      $table->decimal('altitude', 10, 2)->nullable();
      $table->enum('commission_type', ['rate', 'fixed', 'subscription'])->nullable();
      $table->decimal('commission_value', 10, 2)->nullable();
      $table->integer('location_update_interval')->default(30);
      $table->string('additional_data')->nullable();
      $table->unsignedBigInteger('form_template_id')->nullable();
      $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('set null');
      $table->unsignedBigInteger('team_id')->nullable();
      $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
      $table->unsignedBigInteger('vehicle_size_id');
      $table->foreign('vehicle_size_id')->references('id')->on('vehicle_sizes')->onDelete('restrict');
      $table->unsignedBigInteger('role_id')->nullable();
      $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('drivers');
  }
};
