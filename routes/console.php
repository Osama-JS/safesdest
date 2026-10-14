<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 1. فحص استحقاق وتأخر فواتير العملاء وإرسال الإشعارات (يومياً 08:00 صباحاً)
Schedule::command('invoices:check-due-dates')->dailyAt('08:00');

// 2. إعادة معالجة الإشعارات والبريد الإلكتروني العالق أو المتعثر (كل 15 دقيقة)
Schedule::command('notifications:process-pending')->everyFifteenMinutes();

// 3. تنظيف وأرشفة سجلات الإشعارات القديمة لتخفيف قاعدة البيانات (أسبوعياً)
Schedule::command('notifications:cleanup')->weekly();

// 4. أمر النسخ الاحتياطي التلقائي مع الرفع لتليجرام أو الإيميل
Artisan::command('backup:run {--type=database_only} {--encrypt} {--dest=} {--delete-after-dispatch}', function (\App\Services\BackupService $backupService) {
    $this->info('Starting automated platform backup...');

    $type = $this->option('type') ?: 'database_only';
    $encrypt = $this->option('encrypt') || (\App\Models\Settings::getValue('backup_auto_encrypt', '0') === '1');
    $destRaw = $this->option('dest') ?: \App\Models\Settings::getValue('backup_auto_destinations', 'telegram,email');
    $destinations = array_filter(array_map('trim', explode(',', $destRaw)));
    $deleteAfterDispatch = (bool) $this->option('delete-after-dispatch');

    try {
        $result = $backupService->createBackup([
            'backup_type' => $type,
            'is_encrypted' => $encrypt,
            'destinations' => $destinations,
            'description' => 'Scheduled Automated Backup',
            'created_by' => 'System Cron',
            'delete_after_dispatch' => $deleteAfterDispatch,
        ]);
        $destStr = implode(', ', $destinations) ?: 'Cloud';
        $this->info("Backup completed successfully! File: {$result['filename']} ({$result['size_human']})");

        // Notify Admins
        \App\Services\AdminNotificationDispatcher::dispatch(
            'system.backup.completed',
            'اكتمال النسخ الاحتياطي الدوري',
            "تم إنشاء النسخة الاحتياطية بنجاح ({$result['size_human']}) وإرسالها إلى ({$destStr})" . ($deleteAfterDispatch ? "، وتم حذفها من السيرفر لحفظ المساحة." : "."),
            route('admin.settings.backup.index'),
            'ti ti-database-check'
        );
    } catch (\Throwable $e) {
        $this->error("Backup failed: " . $e->getMessage());
        \App\Services\AdminNotificationDispatcher::dispatch(
            'system.backup.failed',
            'فشل النسخ الاحتياطي الدوري',
            "فشلت عملية النسخ الاحتياطي الدوري: " . $e->getMessage(),
            route('admin.settings.backup.index'),
            'ti ti-database-x'
        );
    }
})->purpose('Run system backup with optional encryption and cloud dispatch');

// جدولة النسخ الاحتياطي التلقائي بحسب إعدادات لوحة التحكم
Schedule::call(function (\App\Services\BackupService $backupService) {
    $enabled = \App\Models\Settings::getValue('backup_auto_enabled', '0') === '1';
    if (!$enabled) {
        return;
    }

    $frequency = \App\Models\Settings::getValue('backup_schedule_frequency', 'daily');
    if ($frequency === 'weekly' && now()->dayOfWeek !== \Carbon\Carbon::FRIDAY) {
        return;
    }

    $encrypt = \App\Models\Settings::getValue('backup_auto_encrypt', '0') === '1';
    $destRaw = \App\Models\Settings::getValue('backup_auto_destinations', 'telegram,email');
    $destinations = array_filter(array_map('trim', explode(',', $destRaw)));
    $destStr = !empty($destinations) ? implode(', ', $destinations) : 'الوجهات السحابية';

    $createdFilename = null;
    try {
        $result = $backupService->createBackup([
            'backup_type' => 'database_only',
            'is_encrypted' => $encrypt,
            'destinations' => $destinations,
            'description' => 'Scheduled Automated Backup (' . ucfirst($frequency) . ')',
            'created_by' => 'System Scheduler',
            'delete_after_dispatch' => true, // حذف النسخة محلياً فوراً بعد الإرسال لحفظ مساحة السيرفر
        ]);

        $createdFilename = $result['filename'] ?? null;
        $sizeHuman = $result['size_human'] ?? '0 B';

        // إرسال إشعار للمدراء بنجاح النسخ الدوري وإرساله
        \App\Services\AdminNotificationDispatcher::dispatch(
            'system.backup.completed',
            'اكتمال النسخ الاحتياطي الدوري',
            "تم إنشاء النسخة الاحتياطية الدورية لقاعدة البيانات بنجاح ({$sizeHuman}) وإرسالها إلى ({$destStr})، وتم تفريغها من السيرفر لحفظ المساحة.",
            route('admin.settings.backup.index'),
            'ti ti-database-check'
        );

    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("Scheduled Automated Backup Failed: " . $e->getMessage());

        // في حال فشل أي خطوة، التأكد من حذف أي ملف مؤقت تم إنشاؤه
        if ($createdFilename) {
            try {
                $backupService->deleteBackup($createdFilename);
            } catch (\Throwable $delEx) {
                // Ignore
            }
        }

        // إرسال إشعار للمدراء بفشل النسخ الدوري
        \App\Services\AdminNotificationDispatcher::dispatch(
            'system.backup.failed',
            'فشل النسخ الاحتياطي الدوري',
            "تعذر إتمام عملية النسخ الاحتياطي التلقائي المجدولة: " . $e->getMessage(),
            route('admin.settings.backup.index'),
            'ti ti-database-x'
        );
    }
})->name('platform-automated-backup')->dailyAt(\App\Models\Settings::getValue('backup_schedule_time', '02:00') ?: '02:00');


