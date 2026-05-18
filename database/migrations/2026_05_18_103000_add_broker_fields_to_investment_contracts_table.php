<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('broker_id')->nullable()->after('filter_customer_ids');
            $table->foreign('broker_id')->references('id')->on('users')->onDelete('set null');

            $table->enum('broker_commission_source', ['investor_commission', 'task_commission'])
                  ->default('investor_commission')
                  ->after('broker_id');

            $table->enum('broker_commission_type', ['percentage', 'fixed'])
                  ->default('percentage')
                  ->after('broker_commission_source');

            $table->decimal('broker_commission_value', 10, 2)
                  ->default(0.00)
                  ->after('broker_commission_type');
        });
    }

    public function down(): void
    {
        Schema::table('investment_contracts', function (Blueprint $table) {
            $table->dropForeign(['broker_id']);
            $table->dropColumn([
                'broker_id',
                'broker_commission_source',
                'broker_commission_type',
                'broker_commission_value'
            ]);
        });
    }
};
