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
    Schema::table('tasks_ads', function (Blueprint $table) {
      $table->float('service_commission')->default(0)->after('lowest_price');
      $table->boolean('service_commission_type')->default(0)->after('service_commission');
      $table->float('vat_commission')->default(0)->after('service_commission_type');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('tasks_ads', function (Blueprint $table) {
      $table->dropColumn('service_commission');
      $table->dropColumn('service_commission_type');
      $table->dropColumn('vat_commission');
    });
  }
};
