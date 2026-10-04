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
            if (!Schema::hasColumn('hyperpay_payouts', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->after('driver_id');
                $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
            }
            if (!Schema::hasColumn('hyperpay_payouts', 'team_wallet_id')) {
                $table->unsignedBigInteger('team_wallet_id')->nullable()->after('team_id');
                $table->foreign('team_wallet_id')->references('id')->on('team_wallet')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hyperpay_payouts', function (Blueprint $table) {
            if (Schema::hasColumn('hyperpay_payouts', 'team_wallet_id')) {
                $table->dropForeign(['team_wallet_id']);
                $table->dropColumn('team_wallet_id');
            }
            if (Schema::hasColumn('hyperpay_payouts', 'team_id')) {
                $table->dropForeign(['team_id']);
                $table->dropColumn('team_id');
            }
        });
    }
};
