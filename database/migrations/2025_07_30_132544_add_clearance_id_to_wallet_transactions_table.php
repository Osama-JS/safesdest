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
    Schema::table('wallet_transactions', function (Blueprint $table) {
      $table->unsignedBigInteger('clearance_id')->nullable();
      $table->foreign('clearance_id')->references('id')->on('customs_clearance')->onDelete('restrict');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('wallet_transactions', function (Blueprint $table) {
      $table->dropColumn('clearance_id');
      $table->dropForeign(['clearance_id']);
    });
  }
};
