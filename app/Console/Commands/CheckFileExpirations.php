<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileExpirationService;
use App\Models\FileExpirationNotification;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckFileExpirations extends Command
{
    /**
     * اسم الأمر وتوقيعه
     */
    protected $signature = 'files:check-expirations 
                            {--dry-run : تشغيل تجريبي بدون إرسال إيميلات أو تعليق حسابات}
                            {--user-type= : فحص نوع مستخدم محدد (user, customer, driver)}
                            {--stats : عرض الإحصائيات فقط}
                            {--verbose : عرض تفاصيل إضافية}';

    /**
     * وصف الأمر
     */
    protected $description = 'فحص الملفات منتهية الصلاحية وإرسال التنبيهات وتعليق الحسابات المطلوبة';

    /**
     * خدمة فحص انتهاء الصلاحية
     */
    protected $fileExpirationService;

    /**
     * إنشاء مثيل جديد من الأمر
     */
    public function __construct(FileExpirationService $fileExpirationService)
    {
        parent::__construct();
        $this->fileExpirationService = $fileExpirationService;
    }

    /**
     * تنفيذ الأمر
     */
    public function handle()
    {
        $startTime = microtime(true);
        
        try {
            $this->displayHeader();
            
            // عرض الإحصائيات فقط إذا طُلب ذلك
            if ($this->option('stats')) {
                return $this->displayStatistics();
            }
            
            // التحقق من الوضع التجريبي
            if ($this->option('dry-run')) {
                $this->warn('🧪 تشغيل تجريبي - لن يتم إرسال إيميلات أو تعليق حسابات');
                $this->newLine();
            }
            
            // بدء عملية الفحص
            $this->info('🔍 بدء فحص الملفات منتهية الصلاحية...');
            $this->newLine();
            
            $results = $this->fileExpirationService->checkAndNotifyExpiredFiles();
            
            // عرض النتائج
            $this->displayResults($results, microtime(true) - $startTime);
            
            // عرض الإحصائيات إذا طُلب ذلك
            if ($this->option('verbose')) {
                $this->newLine();
                $this->displayStatistics();
            }
            
            return $this->getExitCode($results);
            
        } catch (Exception $e) {
            $this->error('❌ حدث خطأ أثناء تنفيذ الأمر:');
            $this->error($e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error('تفاصيل الخطأ:');
                $this->error($e->getTraceAsString());
            }
            
            Log::error('CheckFileExpirations command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }

    /**
     * عرض رأس الأمر
     */
    protected function displayHeader()
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                  🔔 نظام فحص انتهاء صلاحية الملفات                  ║');
        $this->info('║                        SafeDests Platform                        ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->info('📅 التاريخ: ' . now()->format('Y-m-d H:i:s'));
        $this->info('🌍 المنطقة الزمنية: ' . config('app.timezone', 'UTC'));
        $this->newLine();
    }

    /**
     * عرض نتائج الفحص
     */
    protected function displayResults(array $results, float $executionTime)
    {
        $this->info('✅ تم إكمال فحص الملفات بنجاح!');
        $this->newLine();
        
        // إنشاء جدول النتائج
        $tableData = [
            ['المؤشر', 'العدد', 'الحالة'],
            ['المستخدمين المفحوصين', $results['users_checked'], '✓'],
            ['العملاء المفحوصين', $results['customers_checked'], '✓'],
            ['السائقين المفحوصين', $results['drivers_checked'], '✓'],
            ['التنبيهات المرسلة', $results['notifications_sent'], $results['notifications_sent'] > 0 ? '📧' : '-'],
            ['الحسابات المعلقة', $results['accounts_suspended'], $results['accounts_suspended'] > 0 ? '🚫' : '-'],
        ];
        
        $this->table($tableData[0], array_slice($tableData, 1));
        
        // عرض الأخطاء إن وجدت
        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('⚠️  الأخطاء التي حدثت:');
            foreach ($results['errors'] as $error) {
                $this->error("   • $error");
            }
        }
        
        // معلومات الأداء
        $this->newLine();
        $this->info("⏱️  وقت التنفيذ: " . round($executionTime, 2) . " ثانية");
        $this->info("💾 استهلاك الذاكرة: " . $this->formatBytes(memory_get_peak_usage(true)));
    }

    /**
     * عرض الإحصائيات
     */
    protected function displayStatistics()
    {
        try {
            $this->info('📊 إحصائيات النظام:');
            $this->newLine();
            
            $stats = $this->fileExpirationService->getSystemStatistics();
            
            // إحصائيات المستخدمين النشطين
            $this->info('👥 المستخدمين النشطين:');
            $activeUsersData = [
                ['النوع', 'العدد'],
                ['مستخدمي النظام', $stats['active_users']['users']],
                ['العملاء', $stats['active_users']['customers']],
                ['السائقين', $stats['active_users']['drivers']],
                ['الإجمالي', array_sum($stats['active_users'])]
            ];
            $this->table($activeUsersData[0], array_slice($activeUsersData, 1));
            
            // إحصائيات التنبيهات
            if (!empty($stats['notifications'])) {
                $this->newLine();
                $this->info('🔔 تنبيهات اليوم (' . $stats['date'] . '):');
                
                $notificationData = [
                    ['المؤشر', 'العدد']
                ];
                
                $notificationData[] = ['إجمالي التنبيهات', $stats['notifications']['total']];
                
                if (!empty($stats['notifications']['by_user_type'])) {
                    foreach ($stats['notifications']['by_user_type'] as $type => $count) {
                        $typeInArabic = [
                            'user' => 'مستخدمي النظام',
                            'customer' => 'العملاء', 
                            'driver' => 'السائقين'
                        ][$type] ?? $type;
                        $notificationData[] = ["تنبيهات {$typeInArabic}", $count];
                    }
                }
                
                $notificationData[] = ['الملفات المنتهية', $stats['notifications']['expired_files'] ?? 0];
                $notificationData[] = ['الملفات ستنتهي قريباً', $stats['notifications']['expiring_soon'] ?? 0];
                $notificationData[] = ['الحسابات المعلقة اليوم', $stats['suspended_today']];
                
                $this->table($notificationData[0], array_slice($notificationData, 1));
            }
            
            return 0;
            
        } catch (Exception $e) {
            $this->error('❌ خطأ في عرض الإحصائيات: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * تحديد رمز الخروج بناءً على النتائج
     */
    protected function getExitCode(array $results): int
    {
        // إذا كانت هناك أخطاء
        if (!empty($results['errors'])) {
            return 1;
        }
        
        // إذا تم تعليق حسابات كثيرة (أكثر من 10)
        if ($results['accounts_suspended'] > 10) {
            $this->warn('⚠️  تحذير: تم تعليق عدد كبير من الحسابات (' . $results['accounts_suspended'] . ')');
            return 2;
        }
        
        return 0;
    }

    /**
     * تنسيق حجم الذاكرة
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
