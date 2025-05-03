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
    Schema::create('email_verification_resends', function (Blueprint $table) {
      $table->id();
      $table->string('email');
      $table->string('ip_address')->nullable();
      $table->unsignedInteger('resend_count')->default(1);
      $table->timestamp('last_sent_at')->nullable();
      $table->timestamps();

      $table->index('email');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('email_verification_resends');
  }
};
