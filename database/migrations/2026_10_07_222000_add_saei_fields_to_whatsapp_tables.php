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
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('saei_message_id')->nullable()->index()->after('meta_message_id')->comment('معرف الرسالة في منصة ساعي (msg_... أو imsg_...)');
            $table->string('reference')->nullable()->index()->after('saei_message_id')->comment('معرف التتبع الخاص بنا المرسل لساعي');
            $table->string('media_url')->nullable()->after('content')->comment('رابط الملف المرفق إن وجد');
            $table->string('media_filename')->nullable()->after('media_url')->comment('اسم الملف المرفق');
            $table->string('media_mime_type')->nullable()->after('media_filename')->comment('نوع صيغة الملف المرفق');
            $table->string('error_code')->nullable()->after('error_log')->comment('رمز الخطأ من ساعي أو واتساب');
            $table->string('error_title')->nullable()->after('error_code')->comment('عنوان الخطأ التفصيلي');
        });

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->string('saei_conversation_id')->nullable()->after('user_id')->comment('معرف المحادثة في ساعي إن توفر');
            $table->timestamp('reply_window_expires_at')->nullable()->after('last_message_time')->comment('توقيت انتهاء نافذة الـ 24 ساعة للرد الحر');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn([
                'saei_message_id',
                'reference',
                'media_url',
                'media_filename',
                'media_mime_type',
                'error_code',
                'error_title'
            ]);
        });

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropColumn([
                'saei_conversation_id',
                'reply_window_expires_at'
            ]);
        });
    }
};
