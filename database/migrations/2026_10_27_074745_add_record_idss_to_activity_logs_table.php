<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // التحقق مما إذا كان الحقل غير موجود، ثم إضافته مباشرة في النهاية بدون أي ترتيب
    if (!Schema::hasColumn('activity_logs', 'record_id')) {
      Schema::table('activity_logs', function (Blueprint $table) {
        $table->string('record_id')->nullable()->index();
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    if (Schema::hasColumn('activity_logs', 'record_id')) {
      Schema::table('activity_logs', function (Blueprint $table) {
        $table->dropColumn('record_id');
      });
    }
  }
};
