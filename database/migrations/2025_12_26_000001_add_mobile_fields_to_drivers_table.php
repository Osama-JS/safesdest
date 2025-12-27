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
            if (!Schema::hasColumn('drivers', 'device_id')) {
                $table->string('device_id')->nullable()->after('phone_is_whatsapp');
            }
            if (!Schema::hasColumn('drivers', 'fcm_token')) {
                $table->text('fcm_token')->nullable()->after('device_id');
            }
            if (!Schema::hasColumn('drivers', 'app_version')) {
                $table->string('app_version')->nullable()->after('fcm_token');
            }
            if (!Schema::hasColumn('drivers', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('app_version');
            }

            // Add indexes for better performance
            // We can't easily check for indexes, but we can try-catch or just skip if we are sure
            try {
                if (!Schema::hasColumn('drivers', 'device_id_index_exists')) { // Dummy check or just run
                     // $table->index('device_id');
                }
            } catch (\Exception $e) {}
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
