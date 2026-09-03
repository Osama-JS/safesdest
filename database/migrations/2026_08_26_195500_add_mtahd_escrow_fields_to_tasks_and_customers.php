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
        // 1. Add amnn_customer_number to customers table
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'amnn_customer_number')) {
                $table->string('amnn_customer_number')->nullable()->after('phone_number')->index();
            }
        });

        // 2. Add escrow and mtahd fields to tasks table
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'amnn_deal_number')) {
                $table->string('amnn_deal_number')->nullable()->after('payment_id')->index();
            }
            if (!Schema::hasColumn('tasks', 'amnn_deal_id')) {
                $table->string('amnn_deal_id')->nullable()->after('amnn_deal_number');
            }
            if (!Schema::hasColumn('tasks', 'amnn_payment_url')) {
                $table->text('amnn_payment_url')->nullable()->after('amnn_deal_id');
            }
            if (!Schema::hasColumn('tasks', 'amnn_deal_status')) {
                $table->string('amnn_deal_status', 50)->nullable()->default('draft')->after('amnn_payment_url')->index();
            }
            if (!Schema::hasColumn('tasks', 'is_escrow')) {
                $table->boolean('is_escrow')->default(false)->after('amnn_deal_status')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'amnn_customer_number')) {
                $table->dropColumn('amnn_customer_number');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'amnn_deal_number',
                'amnn_deal_id',
                'amnn_payment_url',
                'amnn_deal_status',
                'is_escrow',
            ]);
        });
    }
};
