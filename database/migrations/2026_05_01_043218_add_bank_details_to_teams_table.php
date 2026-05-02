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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban_number')->nullable();
            $table->string('bic_code')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('bank_address1')->nullable();
            $table->string('bank_city')->nullable();
            $table->string('bank_country')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'account_number',
                'iban_number',
                'bic_code',
                'beneficiary_name',
                'bank_address1',
                'bank_city',
                'bank_country'
            ]);
        });
    }
};
