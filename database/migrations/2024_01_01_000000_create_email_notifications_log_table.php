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
        Schema::create('email_notifications_log', function (Blueprint $table) {
            $table->id();
            $table->string('to_email');
            $table->string('from_email')->nullable();
            $table->string('subject');
            $table->text('content')->nullable();
            $table->string('template')->nullable();
            $table->string('type')->default('general');
            $table->enum('priority', ['high', 'normal', 'low'])->default('normal');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->json('additional_data')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->timestamps();
            
            $table->index(['to_email', 'status']);
            $table->index(['type', 'created_at']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_notifications_log');
    }
};
