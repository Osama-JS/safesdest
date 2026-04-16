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
        Schema::table('company_end_clients', function (Blueprint $table) {
            // Unique per company so upsert can match on (company_id + client_code)
            // We use a normal index instead of unique because client_code can be NULL
            $table->index(['company_id', 'client_code'], 'idx_company_client_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_end_clients', function (Blueprint $table) {
            $table->dropIndex('idx_company_client_code');
        });
    }
};
