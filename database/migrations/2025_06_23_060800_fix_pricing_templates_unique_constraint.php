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
        Schema::table('pricing_templates', function (Blueprint $table) {
            // Drop the existing incorrect unique constraint using the actual constraint name
            DB::statement('ALTER TABLE pricing_templates DROP CONSTRAINT IF EXISTS "form_template_id"');

            // Add the correct unique constraint on both 'name' and 'form_template_id'
            $table->unique(['name', 'form_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_templates', function (Blueprint $table) {
            // Drop the correct unique constraint
            $table->dropUnique(['name', 'form_template_id']);

            // Restore the incorrect unique constraint (for rollback purposes)
            $table->unique(['name']);
        });
    }
};
