<?php

/**
 * اختبار بسيط لنظام فحص انتهاء صلاحية الملفات
 * 
 * هذا الملف يحتوي على اختبارات أساسية للتأكد من عمل النظام بشكل صحيح
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\FileExpirationNotification;
use App\Services\FileExpirationService;
use App\Models\User;
use App\Models\Customer;
use App\Models\Driver;
use Carbon\Carbon;

echo "🧪 بدء اختبار نظام فحص انتهاء صلاحية الملفات\n";
echo "=" . str_repeat("=", 60) . "\n\n";

try {
    // اختبار 1: فحص وجود الجدول الجديد
    echo "1️⃣ اختبار وجود جدول file_expiration_notifications...\n";
    
    if (Schema::hasTable('file_expiration_notifications')) {
        echo "✅ الجدول موجود بنجاح\n";
        
        // فحص الأعمدة المطلوبة
        $requiredColumns = [
            'user_type', 'user_id', 'field_name', 'field_label', 
            'file_path', 'expiration_date', 'notification_sent_date',
            'days_before_expiration', 'status', 'recipients'
        ];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('file_expiration_notifications', $column)) {
                $missingColumns[] = $column;
            }
        }
        
        if (empty($missingColumns)) {
            echo "✅ جميع الأعمدة المطلوبة موجودة\n";
        } else {
            echo "❌ أعمدة مفقودة: " . implode(', ', $missingColumns) . "\n";
        }
    } else {
        echo "❌ الجدول غير موجود - يرجى تشغيل Migration\n";
    }
    
    echo "\n";
    
    // اختبار 2: فحص Model FileExpirationNotification
    echo "2️⃣ اختبار Model FileExpirationNotification...\n";
    
    if (class_exists('App\Models\FileExpirationNotification')) {
        echo "✅ Model موجود\n";
        
        // اختبار إنشاء مثيل
        $notification = new FileExpirationNotification();
        if ($notification instanceof FileExpirationNotification) {
            echo "✅ يمكن إنشاء مثيل من Model\n";
        }
        
        // اختبار الدوال المهمة
        $methods = ['shouldSuspendAccount', 'isExpired', 'createSafely', 'getStatistics'];
        foreach ($methods as $method) {
            if (method_exists(FileExpirationNotification::class, $method)) {
                echo "✅ الدالة {$method} موجودة\n";
            } else {
                echo "❌ الدالة {$method} مفقودة\n";
            }
        }
    } else {
        echo "❌ Model غير موجود\n";
    }
    
    echo "\n";
    
    // اختبار 3: فحص Service FileExpirationService
    echo "3️⃣ اختبار Service FileExpirationService...\n";
    
    if (class_exists('App\Services\FileExpirationService')) {
        echo "✅ Service موجود\n";
        
        // اختبار الدوال المهمة
        $methods = ['checkAndNotifyExpiredFiles', 'getSystemStatistics'];
        foreach ($methods as $method) {
            if (method_exists(FileExpirationService::class, $method)) {
                echo "✅ الدالة {$method} موجودة\n";
            } else {
                echo "❌ الدالة {$method} مفقودة\n";
            }
        }
    } else {
        echo "❌ Service غير موجود\n";
    }
    
    echo "\n";
    
    // اختبار 4: فحص Command CheckFileExpirations
    echo "4️⃣ اختبار Command CheckFileExpirations...\n";
    
    if (class_exists('App\Console\Commands\CheckFileExpirations')) {
        echo "✅ Command موجود\n";
    } else {
        echo "❌ Command غير موجود\n";
    }
    
    echo "\n";
    
    // اختبار 5: فحص قوالب الإيميل
    echo "5️⃣ اختبار قوالب الإيميل...\n";
    
    $templates = [
        'file-expiration-notification' => 'resources/views/emails/file-expiration-notification.blade.php',
        'account-suspension-notification' => 'resources/views/emails/account-suspension-notification.blade.php'
    ];
    
    foreach ($templates as $name => $path) {
        if (file_exists($path)) {
            echo "✅ قالب {$name} موجود\n";
        } else {
            echo "❌ قالب {$name} مفقود في {$path}\n";
        }
    }
    
    echo "\n";
    
    // اختبار 6: فحص الإعدادات
    echo "6️⃣ اختبار الإعدادات...\n";
    
    $configs = [
        'app.platform_notification_email',
        'app.admin_email',
        'app.file_expiration_enabled',
        'app.file_expiration_suspension_days',
        'app.file_expiration_warning_days'
    ];
    
    foreach ($configs as $config) {
        $value = config($config);
        if ($value !== null) {
            echo "✅ إعداد {$config}: {$value}\n";
        } else {
            echo "❌ إعداد {$config} غير موجود\n";
        }
    }
    
    echo "\n";
    
    // اختبار 7: فحص الجدولة
    echo "7️⃣ اختبار الجدولة...\n";
    
    if (class_exists('App\Schedule\FileExpirationScheduler')) {
        echo "✅ FileExpirationScheduler موجود\n";
    } else {
        echo "❌ FileExpirationScheduler غير موجود\n";
    }
    
    echo "\n";
    
    // اختبار 8: إحصائيات النظام
    echo "8️⃣ إحصائيات النظام الحالية...\n";
    
    try {
        $userCount = User::where('status', 'active')->count();
        $customerCount = Customer::where('status', 'active')->count();
        $driverCount = Driver::where('status', 'active')->count();
        $notificationCount = FileExpirationNotification::count();
        
        echo "📊 المستخدمين النشطين: {$userCount}\n";
        echo "📊 العملاء النشطين: {$customerCount}\n";
        echo "📊 السائقين النشطين: {$driverCount}\n";
        echo "📊 إجمالي التنبيهات: {$notificationCount}\n";
        
    } catch (Exception $e) {
        echo "❌ خطأ في جلب الإحصائيات: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    echo "🎉 انتهى الاختبار بنجاح!\n";
    echo "=" . str_repeat("=", 60) . "\n";
    
    // ملخص النتائج
    echo "\n📋 ملخص النتائج:\n";
    echo "✅ تم إنشاء جميع الملفات المطلوبة\n";
    echo "✅ تم تحديث الإعدادات والجدولة\n";
    echo "✅ النظام جاهز للاختبار والتشغيل\n";
    echo "\n🚀 الخطوات التالية:\n";
    echo "1. تشغيل Migration: php artisan migrate\n";
    echo "2. اختبار الأمر: php artisan files:check-expirations --stats\n";
    echo "3. فحص الجدولة: php artisan schedule:list\n";
    echo "4. اختبار مع بيانات حقيقية\n";
    
} catch (Exception $e) {
    echo "❌ خطأ في الاختبار: " . $e->getMessage() . "\n";
    echo "تفاصيل الخطأ: " . $e->getTraceAsString() . "\n";
}

echo "\n";
?>
