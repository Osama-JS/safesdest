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
    Schema::table('tasks', function (Blueprint $table) {
      $table->unsignedBigInteger('team_id')->nullable()->after('id'); // حقل الفريق بعد id مثلاً
      $table->foreign('team_id')
        ->references('id')
        ->on('teams')
        ->onDelete('set null'); // إذا حذف الفريق، تصير القيمة null

    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('tasks', function (Blueprint $table) {
      $table->dropForeign(['team_id']);
      $table->dropColumn('team_id');
    });
  }
};
