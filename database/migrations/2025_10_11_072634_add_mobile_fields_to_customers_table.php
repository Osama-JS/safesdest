<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('phone_is_whatsapp');
            $table->text('fcm_token')->nullable()->after('device_id');
            $table->string('app_version')->nullable()->after('fcm_token');

            $table->index('device_id');
            $table->index('fcm_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['customers_device_id_index']);
            $table->dropIndex(['customers_fcm_token_index']);
            $table->dropIndex(['customers_fcm_token_index']);

            $table->dropColumn([
                'device_id',
                'fcm_token',
                'app_version',
            ]);
        });
    }
};
