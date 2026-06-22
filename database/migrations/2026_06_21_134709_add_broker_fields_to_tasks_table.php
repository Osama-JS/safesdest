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
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('broker_id')->nullable()->after('investor_payment_status');
            $table->enum('broker_commission_type', ['percentage', 'fixed'])->nullable()->after('broker_id');
            $table->decimal('broker_commission_value', 10, 2)->nullable()->after('broker_commission_type');
            
            $table->foreign('broker_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['broker_id']);
            $table->dropColumn(['broker_id', 'broker_commission_type', 'broker_commission_value']);
        });
    }
};
