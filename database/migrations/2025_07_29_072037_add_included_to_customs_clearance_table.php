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
    Schema::table('customs_clearance', function (Blueprint $table) {
      $table->boolean('included')->default(false)->after('total_price');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('customs_clearance', function (Blueprint $table) {
      $table->dropColumn('included');
    });
  }
};
