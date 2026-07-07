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
        Schema::table('driver_brokers', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_brokers', 'commission_type')) {
                $table->string('commission_type')->default('percentage');
            }
            if (!Schema::hasColumn('driver_brokers', 'commission_value')) {
                $table->decimal('commission_value', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('driver_brokers', 'commission_start_date')) {
                $table->date('commission_start_date')->nullable();
            }
        });

        Schema::table('task_brokers', function (Blueprint $table) {
            if (!Schema::hasColumn('task_brokers', 'commission_type')) {
                $table->string('commission_type')->default('percentage');
            }
            if (!Schema::hasColumn('task_brokers', 'commission_value')) {
                $table->decimal('commission_value', 10, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_brokers', function (Blueprint $table) {
            if (Schema::hasColumn('driver_brokers', 'commission_type')) {
                $table->dropColumn(['commission_type', 'commission_value', 'commission_start_date']);
            }
        });

        Schema::table('task_brokers', function (Blueprint $table) {
            if (Schema::hasColumn('task_brokers', 'commission_type')) {
                $table->dropColumn(['commission_type', 'commission_value']);
            }
        });
    }
};
