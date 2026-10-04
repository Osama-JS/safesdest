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
        Schema::table('hyperpay_payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('hyperpay_payouts', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('team_wallet_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('hyperpay_payouts', 'user_wallet_id')) {
                $table->unsignedBigInteger('user_wallet_id')->nullable()->after('user_id');
                $table->foreign('user_wallet_id')->references('id')->on('user_wallets')->onDelete('set null');
            }

            if (!Schema::hasColumn('hyperpay_payouts', 'source_commission_withdrawal_id')) {
                $table->unsignedBigInteger('source_commission_withdrawal_id')->nullable()->after('source_withdrawal_id');
                $table->foreign('source_commission_withdrawal_id')->references('id')->on('investor_commission_withdrawals')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hyperpay_payouts', function (Blueprint $table) {
            if (Schema::hasColumn('hyperpay_payouts', 'source_commission_withdrawal_id')) {
                $table->dropForeign(['source_commission_withdrawal_id']);
                $table->dropColumn('source_commission_withdrawal_id');
            }

            if (Schema::hasColumn('hyperpay_payouts', 'user_wallet_id')) {
                $table->dropForeign(['user_wallet_id']);
                $table->dropColumn('user_wallet_id');
            }

            if (Schema::hasColumn('hyperpay_payouts', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
