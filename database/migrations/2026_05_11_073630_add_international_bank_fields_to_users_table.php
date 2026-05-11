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
        Schema::table('users', function (Blueprint $table) {
            $table->string('bic_code')->nullable()->after('iban_number');
            $table->string('beneficiary_name')->nullable()->after('bic_code');
            $table->string('bank_address1')->nullable()->after('beneficiary_name');
            $table->string('bank_address2')->nullable()->after('bank_address1');
            $table->string('bank_city')->nullable()->after('bank_address2');
            $table->string('bank_country')->nullable()->after('bank_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bic_code', 'beneficiary_name', 'bank_address1', 'bank_address2', 'bank_city', 'bank_country']);
        });
    }
};
