<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->string('category')->nullable()->after('language');
            $table->string('meta_status')->nullable()->after('category')->comment('Status from Meta: APPROVED, PENDING, REJECTED');
            $table->text('body_text')->nullable()->after('meta_status')->comment('Body text of the template');
            $table->json('components')->nullable()->after('body_text')->comment('Full components JSON from Meta');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn(['category', 'meta_status', 'body_text', 'components']);
        });
    }
};
