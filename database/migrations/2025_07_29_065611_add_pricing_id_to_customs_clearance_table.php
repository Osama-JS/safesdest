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
      $table->unsignedBigInteger('pricing_id')->nullable()->after('form_template_id');
      $table->foreign('pricing_id')->references('id')->on('clearance_pricing_template')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('customs_clearance', function (Blueprint $table) {
      $table->dropForeign(['pricing_id']);
      $table->dropColumn('pricing_id');
    });
  }
};
