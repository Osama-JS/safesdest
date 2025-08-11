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
            $table->string('device_id')->nullable()->after('phone_is_whatsapp');
            $table->text('fcm_token')->nullable()->after('device_id');
            $table->string('app_version')->nullable()->after('fcm_token');
            $table->timestamp('last_activity_at')->nullable()->after('app_version');
            
            // Add indexes for better performance
            $table->index('device_id');
            $table->index('fcm_token');
            $table->index(['online', 'free']);
            $table->index(['longitude', 'altitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['drivers_device_id_index']);
            $table->dropIndex(['drivers_fcm_token_index']);
            $table->dropIndex(['drivers_online_free_index']);
            $table->dropIndex(['drivers_longitude_altitude_index']);
            
            $table->dropColumn([
                'device_id',
                'fcm_token', 
                'app_version',
                'last_activity_at'
            ]);
        });
    }
};
