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
        // Adding columns to driver_brokers if not exists
        Schema::table('driver_brokers', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_brokers', 'driver_id')) {
                $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
                $table->foreignId('broker_id')->constrained('users')->onDelete('cascade');
                $table->string('commission_type')->default('percentage');
                $table->decimal('commission_value', 10, 2)->default(0);
                $table->date('commission_start_date')->nullable();
            }
        });

        // Adding columns to task_brokers if not exists
        Schema::table('task_brokers', function (Blueprint $table) {
            if (!Schema::hasColumn('task_brokers', 'task_id')) {
                $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
                $table->foreignId('broker_id')->constrained('users')->onDelete('cascade');
                $table->string('commission_type')->default('percentage');
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
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['broker_id']);
            $table->dropColumn(['driver_id', 'broker_id', 'commission_type', 'commission_value', 'commission_start_date']);
        });

        Schema::table('task_brokers', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropForeign(['broker_id']);
            $table->dropColumn(['task_id', 'broker_id', 'commission_type', 'commission_value']);
        });
    }
};
