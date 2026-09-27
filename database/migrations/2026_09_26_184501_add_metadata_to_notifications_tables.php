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
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url', 500)->nullable()->after('type');
            }
            if (!Schema::hasColumn('notifications', 'icon')) {
                $table->string('icon', 100)->nullable()->after('action_url');
            }
            if (!Schema::hasColumn('notifications', 'event_key')) {
                $table->string('event_key', 100)->nullable()->after('icon');
            }
            if (!Schema::hasColumn('notifications', 'data')) {
                $table->json('data')->nullable()->after('event_key');
            }
        });

        Schema::table('notifications_users', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications_users', 'is_shown')) {
                $table->boolean('is_shown')->default(false)->after('status');
            }
            if (!Schema::hasColumn('notifications_users', 'shown_at')) {
                $table->timestamp('shown_at')->nullable()->after('is_shown');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['action_url', 'icon', 'event_key', 'data']);
        });

        Schema::table('notifications_users', function (Blueprint $table) {
            $table->dropColumn(['is_shown', 'shown_at']);
        });
    }
};
