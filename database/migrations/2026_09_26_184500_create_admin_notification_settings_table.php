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
        Schema::create('admin_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('category')->default('system'); // financial, tasks, users, system
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('description_ar', 500)->nullable();
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('webpush_enabled')->default(false);
            $table->json('target_roles')->nullable(); // e.g. ["Owner", "Admin"]
            $table->json('target_user_ids')->nullable(); // e.g. [1, 5]
            $table->text('custom_emails')->nullable(); // comma-separated or json emails
            $table->enum('priority', ['high', 'normal', 'low'])->default('normal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notification_settings');
    }
};
