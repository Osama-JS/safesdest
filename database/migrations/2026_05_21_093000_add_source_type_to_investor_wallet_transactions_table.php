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
        Schema::table('investor_wallet_transactions', function (Blueprint $table) {
            $table->string('source_type', 20)->nullable()->default('capital')->after('transaction_type');
        });

        // Update existing transactions:
        // Any credit transaction that has a task_id is a refund (استعادة استثمار)
        DB::table('investor_wallet_transactions')
            ->where('transaction_type', 'credit')
            ->whereNotNull('task_id')
            ->update(['source_type' => 'refund']);

        // Other credit transactions without a task_id are capital deposits (رأس مال)
        DB::table('investor_wallet_transactions')
            ->where('transaction_type', 'credit')
            ->whereNull('task_id')
            ->update(['source_type' => 'capital']);

        // All debit transactions are capital outputs (رأس مال)
        DB::table('investor_wallet_transactions')
            ->where('transaction_type', 'debit')
            ->update(['source_type' => 'capital']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investor_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }
};
