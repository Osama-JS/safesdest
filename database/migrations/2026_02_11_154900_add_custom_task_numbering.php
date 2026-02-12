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
        // Add task numbering fields to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('task_number_start')->nullable()->after('signature_image')
                ->comment('رقم بداية ترقيم المهام المخصص للعميل');
            $table->unsignedInteger('task_number_next')->nullable()->after('task_number_start')
                ->comment('العداد الحالي - الرقم التالي المتاح');
        });

        // Add customer_task_number to tasks table
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('customer_task_number')->nullable()->after('customer_id')
                ->comment('رقم المهمة المخصص للعميل');

            // Index for performance and uniqueness per customer
            $table->unique(['customer_id', 'customer_task_number'], 'tasks_customer_task_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique('tasks_customer_task_number_unique');
            $table->dropColumn('customer_task_number');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['task_number_start', 'task_number_next']);
        });
    }
};
