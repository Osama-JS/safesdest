<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('activity_logs', 'record_id')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->string('record_id')->nullable()->index()->after('table_name');
            });
        }

        // Backfill record_id from new_values or old_values for existing records
        try {
            DB::statement("
                UPDATE activity_logs 
                SET record_id = COALESCE(new_values->>'id', old_values->>'id')
                WHERE record_id IS NULL 
                  AND (new_values ? 'id' OR old_values ? 'id')
            ");
        } catch (\Throwable $e) {
            // Fallback or ignore if query syntax differs
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
