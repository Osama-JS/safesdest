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
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('whatsapp_country_code', 10)->nullable()->after('phone_code')
                ->comment('WhatsApp country code (e.g., +966)');
            $table->string('whatsapp_number', 20)->nullable()->after('whatsapp_country_code')
                ->comment('WhatsApp phone number without country code');
            $table->boolean('phone_is_whatsapp')->default(false)->after('whatsapp_number')
                ->comment('Whether phone number is same as WhatsApp number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_country_code', 'whatsapp_number', 'phone_is_whatsapp']);
        });
    }
};
