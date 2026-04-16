<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update customers table
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'is_company')) {
                $table->boolean('is_company')->default(false)->after('status');
            }
        });

        // Update tasks table
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'company_warehouse_id')) {
                $table->foreignId('company_warehouse_id')->nullable()->after('vehicle_size_id');
            }
            if (!Schema::hasColumn('tasks', 'company_end_client_id')) {
                $table->foreignId('company_end_client_id')->nullable()->after('company_warehouse_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['is_company']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['company_warehouse_id', 'company_end_client_id']);
        });
    }
};
