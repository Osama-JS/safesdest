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
        if (!Schema::hasTable('task_chat_messages')) {
            Schema::create('task_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->string('sender_type', 30); // 'driver', 'customer', 'admin'
                $table->unsignedBigInteger('sender_id');
                $table->string('receiver_type', 30)->nullable();
                $table->unsignedBigInteger('receiver_id')->nullable();
                $table->text('message');
                $table->string('attachment_url')->nullable();
                $table->boolean('is_read')->default(false)->index();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['task_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_chat_messages');
    }
};

