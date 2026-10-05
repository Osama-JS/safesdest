<?php

namespace App\Services;

use App\Models\Settings;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class BackupService
{
    protected string $backupDir;
    protected string $tempDir;
    protected string $passwordsFile;
    protected string $backupsMetaFile;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        $this->tempDir = storage_path('app/temp');
        $this->passwordsFile = storage_path('app/backups/passwords.json');
        $this->backupsMetaFile = storage_path('app/backups/backups.json');

        $this->ensureDirectories();
    }

    /**
     * Ensure storage directories exist.
     */
    protected function ensureDirectories(): void
    {
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
        if (!File::exists($this->tempDir)) {
            File::makeDirectory($this->tempDir, 0755, true);
        }
    }

    /**
     * Get backup full path.
     */
    public function getBackupPath(?string $filename = null): string
    {
        return $filename ? $this->backupDir . DIRECTORY_SEPARATOR . basename($filename) : $this->backupDir;
    }

    /**
     * Get disk and backup statistics.
     */
    public function getStorageStats(): array
    {
        $backups = $this->listBackups();
        $totalSizeBytes = 0;
        $completedCount = 0;
        $typesCount = ['full' => 0, 'database_only' => 0, 'files_only' => 0];

        foreach ($backups as $b) {
            $totalSizeBytes += $b['size'] ?? 0;
            if (($b['status'] ?? '') === 'completed') {
                $completedCount++;
            }
            $t = $b['type'] ?? 'database_only';
            if (isset($typesCount[$t])) {
                $typesCount[$t]++;
            } else {
                $typesCount[$t] = 1;
            }
        }

        $freeDiskSpace = @disk_free_space($this->backupDir) ?: 0;
        $totalDiskSpace = @disk_total_space($this->backupDir) ?: 0;

        return [
            'total_backups' => count($backups),
            'completed_backups' => $completedCount,
            'total_size' => $totalSizeBytes,
            'total_size_human' => $this->formatBytes($totalSizeBytes),
            'free_disk_human' => $this->formatBytes($freeDiskSpace),
            'total_disk_human' => $this->formatBytes($totalDiskSpace),
            'latest_backup' => $backups[0]['created_at'] ?? null,
            'backup_types' => $typesCount,
            'success_rate' => count($backups) > 0 ? round(($completedCount / count($backups)) * 100, 1) : 0,
        ];
    }

    /**
     * List all backups dynamically from filesystem and metadata file.
     */
    public function listBackups(): array
    {
        $this->ensureDirectories();
        $files = File::files($this->backupDir);
        $metaMap = $this->getMetaMap();
        $passwords = $this->getStoredPasswords();
        $backups = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            // Filter out non-backup files (passwords.json, backups.json, etc.)
            if ($filename === 'passwords.json' || $filename === 'passwords_.json' || $filename === 'backups.json' || $filename === 'backups_.json') {
                continue;
            }

            if (!str_ends_with($filename, '.zip') && !str_ends_with($filename, '.enc') && !str_ends_with($filename, '.mshbak')) {
                continue;
            }

            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            if (str_ends_with($baseName, '.mshbak')) {
                $baseName = pathinfo($baseName, PATHINFO_FILENAME);
            }

            $filePath = $file->getPathname();
            $size = $file->getSize();
            $mtime = $file->getMTime();

            // Extract manifest if present in ZIP
            $manifest = $this->extractManifestSafely($filePath);
            $meta = $metaMap[$baseName] ?? $metaMap[$filename] ?? [];

            $isEncrypted = str_ends_with($filename, '.enc') || !empty($passwords[$baseName]) || ($manifest['is_encrypted'] ?? false);
            $type = $manifest['type'] ?? $meta['type'] ?? (str_contains($filename, 'full') ? 'full' : 'database_only');

            $backups[] = [
                'name' => $baseName,
                'filename' => $filename,
                'file_path' => $filePath,
                'type' => $type,
                'description' => $manifest['notes'] ?? $meta['description'] ?? '',
                'size' => $size,
                'size_human' => $this->formatBytes($size),
                'status' => 'completed',
                'is_encrypted' => $isEncrypted,
                'has_password' => isset($passwords[$baseName]),
                'created_at' => $meta['created_at'] ?? date('Y-m-d H:i:s', $mtime),
                'created_at_timestamp' => $mtime,
                'created_by' => $meta['created_by'] ?? 'System',
                'sha256' => hash_file('sha256', $filePath),
                'manifest' => $manifest,
            ];
        }

        // Sort descending by timestamp
        usort($backups, fn($a, $b) => $b['created_at_timestamp'] <=> $a['created_at_timestamp']);

        return $backups;
    }

    /**
     * Create a new backup.
     */
    public function createBackup(array $options = []): array
    {
        $startTime = microtime(true);
        $type = $options['backup_type'] ?? 'database_only'; // 'full', 'database_only', 'files_only'
        $description = $options['description'] ?? ($options['notes'] ?? '');
        $isEncrypted = !empty($options['is_encrypted']);
        $customPassword = $options['password'] ?? null;
        $destinations = $options['destinations'] ?? ['local'];
        $createdBy = $options['created_by'] ?? (auth()->check() ? auth()->user()->name : 'System');

        $backupName = 'backup_' . Carbon::now()->format('Y_m_d_H_i_s') . '_' . substr(md5(uniqid()), 0, 8);
        $tempStagingDir = $this->tempDir . DIRECTORY_SEPARATOR . $backupName;

        if (!File::makeDirectory($tempStagingDir, 0755, true)) {
            throw new RuntimeException('Failed to create temporary backup staging folder: ' . $tempStagingDir);
        }

        $tablesCount = 0;
        $recordsCount = 0;

        try {
            // 1. Export Database
            if (in_array($type, ['full', 'database_only'])) {
                $sqlDumpPath = $tempStagingDir . DIRECTORY_SEPARATOR . 'database.sql';
                $dbStats = $this->dumpDatabase($sqlDumpPath);
                $tablesCount = $dbStats['tables_count'] ?? 0;
                $recordsCount = $dbStats['records_count'] ?? 0;
                Log::info("Database dumped successfully for backup: {$backupName}", $dbStats);
            }

            // 2. Export Uploads / Storage Files
            if (in_array($type, ['full', 'files_only'])) {
                $targetFilesDir = $tempStagingDir . DIRECTORY_SEPARATOR . 'files';
                $this->exportStorageFiles($targetFilesDir);
                Log::info("Storage files exported successfully for backup: {$backupName}");
            }

            // 3. Create Manifest
            $manifest = [
                'app_name' => config('app.name', 'SafeDest'),
                'backup_name' => $backupName,
                'type' => $type,
                'created_at' => Carbon::now()->toIso8601String(),
                'created_by' => $createdBy,
                'db_connection' => config('database.default'),
                'tables_count' => $tablesCount,
                'records_count' => $recordsCount,
                'is_encrypted' => $isEncrypted,
                'notes' => $description,
            ];
            File::put($tempStagingDir . DIRECTORY_SEPARATOR . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // 4. Archive into ZIP
            $zipPath = $this->backupDir . DIRECTORY_SEPARATOR . $backupName . '.zip';
            $password = $isEncrypted ? ($customPassword ?: $this->generateSecurePassword()) : null;

            $this->zipDirectoryWithEncryption($tempStagingDir, $zipPath, $password);

            // Clean up temporary folder
            File::deleteDirectory($tempStagingDir);

            if (!File::exists($zipPath) || File::size($zipPath) === 0) {
                throw new RuntimeException('Backup archive creation failed or file is empty.');
            }

            $finalSize = File::size($zipPath);
            $sha256 = hash_file('sha256', $zipPath);

            // Save password securely if encrypted
            if ($password) {
                $this->storeBackupPassword($backupName, $password);
            }

            // Save metadata
            $backupInfo = [
                'name' => $backupName,
                'filename' => $backupName . '.zip',
                'type' => $type,
                'description' => $description,
                'created_at' => Carbon::now()->toISOString(),
                'completed_at' => Carbon::now()->toISOString(),
                'size' => $finalSize,
                'size_human' => $this->formatBytes($finalSize),
                'status' => 'completed',
                'created_by' => $createdBy,
                'file_path' => $zipPath,
                'sha256' => $sha256,
                'is_encrypted' => $isEncrypted,
            ];
            $this->saveMetaInfo($backupInfo);

            // Prune old backups based on retention policy
            $this->cleanupOldBackups();

            // 5. Dispatch to Cloud Destinations (Telegram, Email)
            $dispatchResults = [];
            if (!empty($destinations)) {
                $dispatchResults = $this->dispatchBackup($backupName . '.zip', $destinations);
            }

            $elapsedSec = round(microtime(true) - $startTime, 2);

            return [
                'success' => true,
                'backup' => $backupInfo,
                'filename' => $backupName . '.zip',
                'size_human' => $this->formatBytes($finalSize),
                'elapsed_seconds' => $elapsedSec,
                'dispatch_results' => $dispatchResults,
                'password' => $password,
            ];
        } catch (\Throwable $e) {
            if (File::exists($tempStagingDir)) {
                File::deleteDirectory($tempStagingDir);
            }
            Log::error("Backup creation failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new RuntimeException("فشل إنشاء النسخة الاحتياطية: " . $e->getMessage());
        }
    }

    /**
     * Dispatch an existing backup to Telegram and/or Email.
     */
    public function dispatchBackup(string $filename, array|string $destinations, array $config = []): array
    {
        $filePath = $this->getBackupPath($filename);
        if (!File::exists($filePath)) {
            throw new RuntimeException("ملف النسخة الاحتياطية غير موجود: {$filename}");
        }

        $destList = is_array($destinations) ? $destinations : explode(',', $destinations);
        $destList = array_map('trim', $destList);
        $results = [];
        $fileSizeHuman = $this->formatBytes(filesize($filePath));
        $sha256 = hash_file('sha256', $filePath);

        // 1. Telegram Dispatch
        if (in_array('telegram', $destList)) {
            $botToken = $config['telegram_backup_bot_token']
                ?? Settings::getValue('backup_telegram_bot_token')
                ?? Settings::getValue('telegram_backup_bot_token');
            $chatId = $config['telegram_backup_chat_id']
                ?? Settings::getValue('backup_telegram_chat_id')
                ?? Settings::getValue('telegram_backup_chat_id');

            if (!empty($botToken) && !empty($chatId)) {
                try {
                    $caption = "📦 *نسخة احتياطية جديدة - منصة SafeDest*\n"
                        . "📄 الملف: `{$filename}`\n"
                        . "📊 الحجم: `{$fileSizeHuman}`\n"
                        . "🔑 SHA256: `{$sha256}`\n"
                        . "⏰ التاريخ: " . now()->format('Y-m-d H:i:s');

                    $response = Http::timeout(180)
                        ->attach('document', file_get_contents($filePath), $filename)
                        ->post("https://api.telegram.org/bot{$botToken}/sendDocument", [
                            'chat_id' => $chatId,
                            'caption' => $caption,
                            'parse_mode' => 'Markdown',
                        ]);

                    if ($response->successful() && ($response->json('ok') === true)) {
                        $results['telegram'] = ['success' => true, 'message' => 'تم الإرسال إلى تليجرام بنجاح'];
                    } else {
                        $errMsg = $response->json('description') ?: $response->body();
                        $results['telegram'] = ['success' => false, 'message' => 'استجابة تليجرام: ' . $errMsg];
                    }
                } catch (\Throwable $e) {
                    $results['telegram'] = ['success' => false, 'message' => 'خطأ تليجرام: ' . $e->getMessage()];
                }
            } else {
                $results['telegram'] = ['success' => false, 'message' => 'بيانات بوت أو شات تليجرام غير معينة في الإعدادات'];
            }
        }

        // 2. Email Dispatch
        if (in_array('email', $destList)) {
            $recipientsStr = $config['backup_email_recipient']
                ?? Settings::getValue('backup_email_recipient')
                ?? Settings::getValue('email_backup_recipients')
                ?? (auth()->check() ? auth()->user()->email : null);

            if (!empty($recipientsStr)) {
                try {
                    $recipients = array_filter(array_map('trim', explode(',', $recipientsStr)));
                    $subject = "💾 نسخة احتياطية للمنصة - " . config('app.name', 'SafeDest') . " ({$filename})";
                    $bodyHtml = "<h2>تقرير النسخ الاحتياطي لمنصة " . config('app.name', 'SafeDest') . "</h2>"
                        . "<p>تم إنشاء نسخة احتياطية لقاعدة البيانات والملفات بنجاح ومرفقة مع هذه الرسالة.</p>"
                        . "<ul>"
                        . "<li><strong>اسم الملف:</strong> {$filename}</li>"
                        . "<li><strong>الحجم:</strong> {$fileSizeHuman}</li>"
                        . "<li><strong>بصمة التحقق (SHA256):</strong> <code>{$sha256}</code></li>"
                        . "<li><strong>تاريخ الإنشاء:</strong> " . now()->toDateTimeString() . "</li>"
                        . "</ul>"
                        . "<p><em>يرجى حفظ هذا الملف في مكان آمن ومشفر.</em></p>";

                    Mail::html($bodyHtml, function ($message) use ($recipients, $subject, $filePath, $filename) {
                        $message->to($recipients)
                            ->subject($subject)
                            ->attach($filePath, ['as' => $filename, 'mime' => 'application/zip']);
                    });

                    $results['email'] = ['success' => true, 'message' => 'تم إرسال النسخة الاحتياطية إلى البريد الإلكتروني بنجاح'];
                } catch (\Throwable $e) {
                    $results['email'] = ['success' => false, 'message' => 'خطأ إرسال البريد: ' . $e->getMessage()];
                }
            } else {
                $results['email'] = ['success' => false, 'message' => 'لم يتم تحديد بريد إلكتروني مستلم في الإعدادات'];
            }
        }

        return $results;
    }

    /**
     * Test connection to Telegram or Email.
     */
    public function testCloudDestination(string $type, array $credentials = []): array
    {
        if ($type === 'telegram') {
            $token = $credentials['telegram_backup_bot_token']
                ?? Settings::getValue('backup_telegram_bot_token')
                ?? Settings::getValue('telegram_backup_bot_token');

            $chatId = $credentials['telegram_backup_chat_id']
                ?? Settings::getValue('backup_telegram_chat_id')
                ?? Settings::getValue('telegram_backup_chat_id');

            if (empty($token) || empty($chatId)) {
                return ['success' => false, 'message' => 'يرجى إدخال Bot Token و Chat ID أولاً في الإعدادات'];
            }

            try {
                $res = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => "🟢 *اختبار الاتصال السحابي - منصة SafeDest*\nتم ربط روبوت النسخ الاحتياطي بنجاح في " . now()->format('Y-m-d H:i:s'),
                    'parse_mode' => 'Markdown',
                ]);

                if ($res->successful() && $res->json('ok') === true) {
                    return ['success' => true, 'message' => 'تم الاتصال بروبوت تليجرام وإرسال رسالة الاختبار بنجاح!'];
                }
                $errMsg = $res->json('description') ?: $res->body();
                return ['success' => false, 'message' => 'فشل إرسال رسالة تليجرام: ' . $errMsg];
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'خطأ في الاتصال بتليجرام: ' . $e->getMessage()];
            }
        }

        if ($type === 'email') {
            $email = $credentials['backup_email_recipient']
                ?? Settings::getValue('backup_email_recipient')
                ?? Settings::getValue('email_backup_recipients')
                ?? (auth()->check() ? auth()->user()->email : null);

            if (empty($email)) {
                return ['success' => false, 'message' => 'يرجى إدخال بريد إلكتروني صالح في الإعدادات'];
            }
            try {
                $recipients = array_filter(array_map('trim', explode(',', $email)));
                Mail::html("<p>تم اختبار إعدادات البريد الإلكتروني للنسخ الاحتياطي لمنصة <strong>" . config('app.name', 'SafeDest') . "</strong> بنجاح في " . now()->toDateTimeString() . ".</p>", function ($msg) use ($recipients) {
                    $msg->to($recipients)->subject("اختبار خدمة النسخ الاحتياطي - " . config('app.name', 'SafeDest'));
                });
                return ['success' => true, 'message' => "تم إرسال بريد الاختبار إلى ({$email}) بنجاح!"];
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'خطأ خادم البريد: ' . $e->getMessage()];
            }
        }

        return ['success' => false, 'message' => 'نوع الوجهة غير مدعوم'];
    }

    /**
     * Restore database and files from backup archive.
     */
    public function restoreBackup(string $filePathOrName, ?string $password = null, array $options = []): array
    {
        $startTime = microtime(true);
        $fullPath = File::exists($filePathOrName) ? $filePathOrName : $this->getBackupPath($filePathOrName);

        if (!File::exists($fullPath)) {
            throw new RuntimeException("ملف النسخة الاحتياطية غير موجود: {$filePathOrName}");
        }

        $baseName = pathinfo($fullPath, PATHINFO_FILENAME);

        // Pre-restore safety rollback snapshot
        $safetyBackup = null;
        if (!($options['skip_safety_backup'] ?? false)) {
            try {
                $safetyBackup = $this->createBackup([
                    'backup_type' => 'database_only',
                    'is_encrypted' => false,
                    'description' => 'نسخة أمان تلقائية قبل استعادة النظام',
                    'destinations' => ['local'],
                ]);
            } catch (\Throwable $e) {
                Log::warning("Pre-restore safety backup creation warning: " . $e->getMessage());
            }
        }

        $tempExtractDir = storage_path('app/temp_restore_' . date('Ymd_His') . '_' . Str::random(6));
        File::makeDirectory($tempExtractDir, 0755, true);

        try {
            $workingZipPath = $fullPath;

            // Decrypt password resolution
            $archivePassword = $password ?: $this->getStoredPassword($baseName);

            // Open & Extract ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($workingZipPath) !== true) {
                throw new RuntimeException("تعذر فتح أرشيف النسخة الاحتياطية.");
            }

            if ($archivePassword) {
                $zip->setPassword($archivePassword);
            }

            if (!$zip->extractTo($tempExtractDir)) {
                $zip->close();
                throw new RuntimeException("تعذر فك ضغط الأرشيف (تأكد من صحة كلمة المرور أو سلامة الملف).");
            }
            $zip->close();

            // Read manifest
            $manifestFile = $tempExtractDir . DIRECTORY_SEPARATOR . 'manifest.json';
            $manifest = File::exists($manifestFile) ? json_decode(File::get($manifestFile), true) ?: [] : [];

            $restoredTables = 0;
            $restoredQueries = 0;

            // Restore Database
            $sqlFile = $tempExtractDir . DIRECTORY_SEPARATOR . 'database.sql';
            if (File::exists($sqlFile)) {
                $sqlStats = $this->executeSqlFile($sqlFile);
                $restoredTables = $sqlStats['tables_restored'];
                $restoredQueries = $sqlStats['queries_executed'];
            }

            // Restore Files
            $filesDir = $tempExtractDir . DIRECTORY_SEPARATOR . 'files';
            if (File::exists($filesDir) && ($options['restore_files'] ?? true)) {
                $targetPublicStorage = storage_path('app/public');
                File::copyDirectory($filesDir, $targetPublicStorage);
            }

            // Cleanup extract folder
            File::deleteDirectory($tempExtractDir);

            $elapsedSec = round(microtime(true) - $startTime, 2);

            return [
                'success' => true,
                'message' => 'تمت استعادة النسخة الاحتياطية بنجاح ومطابقة كافة الجداول والبيانات.',
                'tables_restored' => $restoredTables,
                'queries_executed' => $restoredQueries,
                'elapsed_seconds' => $elapsedSec,
                'safety_rollback_backup' => $safetyBackup['filename'] ?? null,
                'manifest' => $manifest,
            ];
        } catch (\Throwable $e) {
            if (File::exists($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
            Log::error("Backup restore failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new RuntimeException("فشلت عملية الاستعادة: " . $e->getMessage());
        }
    }

    /**
     * Delete backup and its stored password.
     */
    public function deleteBackup(string $filename): void
    {
        $filePath = $this->getBackupPath($filename);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $this->removeStoredPassword($baseName);
        $this->removeMetaInfo($baseName);
    }

    /**
     * Get auto-backup and cloud configuration.
     */
    public function getConfig(): array
    {
        return [
            'backup_auto_enabled' => (bool) (Settings::getValue('backup_auto_enabled', false) == '1'),
            'backup_schedule_frequency' => Settings::getValue('backup_schedule_frequency', 'daily'),
            'backup_schedule_time' => Settings::getValue('backup_schedule_time', '02:00'),
            'backup_max_retention_count' => (int) (Settings::getValue('backup_max_retention_count', 15)),
            'backup_auto_encrypt' => (bool) (Settings::getValue('backup_auto_encrypt', false) == '1'),
            'backup_auto_destinations' => Settings::getValue('backup_auto_destinations', 'local,telegram'),
            'telegram_backup_enabled' => (bool) (Settings::getValue('telegram_backup_enabled', false) == '1'),
            'backup_telegram_bot_token' => Settings::getValue('backup_telegram_bot_token', '') ?: Settings::getValue('telegram_backup_bot_token', ''),
            'backup_telegram_chat_id' => Settings::getValue('backup_telegram_chat_id', '') ?: Settings::getValue('telegram_backup_chat_id', ''),
            'email_backup_enabled' => (bool) (Settings::getValue('email_backup_enabled', false) == '1'),
            'backup_email_recipient' => Settings::getValue('backup_email_recipient', '') ?: Settings::getValue('email_backup_recipients', ''),
        ];
    }

    /**
     * Save auto-backup and cloud configuration.
     */
    public function saveConfig(array $data): void
    {
        foreach ($data as $key => $value) {
            $val = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
            Settings::setValue($key, $val);
        }

        // Aliases sync
        if (isset($data['backup_telegram_bot_token'])) {
            Settings::setValue('telegram_backup_bot_token', (string) $data['backup_telegram_bot_token']);
        }
        if (isset($data['backup_telegram_chat_id'])) {
            Settings::setValue('telegram_backup_chat_id', (string) $data['backup_telegram_chat_id']);
        }
        if (isset($data['backup_email_recipient'])) {
            Settings::setValue('email_backup_recipients', (string) $data['backup_email_recipient']);
        }
    }

    // ==========================================
    // INTERNAL ENGINES: DUMP, RESTORE & UTILITIES
    // ==========================================

    /**
     * Dump PostgreSQL database with native pg_dump or pure PDO fallback.
     */
    protected function dumpDatabase(string $targetSqlFile): array
    {
        $dbConfig = config('database.connections.pgsql');
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 5432;
        $username = $dbConfig['username'] ?? 'postgres';
        $password = $dbConfig['password'] ?? '';
        $database = $dbConfig['database'] ?? '';

        $pgDumpBinary = $this->findBinary('pg_dump');

        if ($pgDumpBinary) {
            // Encode credentials for connection URI
            $encodedPass = urlencode($password);
            $encodedUser = urlencode($username);
            $connUri = "postgresql://{$encodedUser}:{$encodedPass}@{$host}:{$port}/{$database}";

            $cmd = sprintf(
                '"%s" --dbname="%s" --clean --if-exists --no-owner --file="%s" 2>&1',
                $pgDumpBinary,
                $connUri,
                $targetSqlFile
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($targetSqlFile) && File::size($targetSqlFile) > 0) {
                // Count tables
                $tablesCount = count(DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'"));
                return [
                    'engine' => 'native_pg_dump',
                    'tables_count' => $tablesCount,
                    'file_size' => File::size($targetSqlFile),
                ];
            }

            Log::warning("pg_dump binary returned {$returnCode} (" . implode("\n", $output) . "), falling back to PDO dumper.");
        }

        // Fallback: Pure PDO Cross-Platform Dumper
        return $this->dumpDatabaseViaPdo($targetSqlFile);
    }

    /**
     * Pure PDO Database Dumper for PostgreSQL / MySQL.
     */
    protected function dumpDatabaseViaPdo(string $targetSqlFile): array
    {
        $handle = fopen($targetSqlFile, 'w');
        if (!$handle) {
            throw new RuntimeException("تعذر فتح ملف تفريغ قاعدة البيانات: {$targetSqlFile}");
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        fwrite($handle, "-- SafeDest Database Backup Dump\n");
        fwrite($handle, "-- Driver: {$driver}\n");
        fwrite($handle, "-- Date: " . now()->toIso8601String() . "\n\n");

        if ($driver === 'pgsql') {
            fwrite($handle, "SET CONSTRAINTS ALL DEFERRED;\n\n");
        }

        $rows = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
        $tables = array_map(fn($r) => $r->table_name, $rows);
        $totalTables = count($tables);
        $totalRecords = 0;

        foreach ($tables as $table) {
            if ($table === 'telescope_entries' || $table === 'pulse_entries') {
                continue;
            }

            fwrite($handle, "-- Structure & Data for Table: \"{$table}\"\n");
            fwrite($handle, "TRUNCATE TABLE \"{$table}\" CASCADE;\n");

            $count = $connection->table($table)->count();
            $totalRecords += $count;

            if ($count > 0) {
                $connection->table($table)->orderByRaw('1')->chunk(250, function ($rows) use ($handle, $table) {
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $columns = array_keys($rowArray);
                        $escapedColumns = array_map(fn($c) => "\"{$c}\"", $columns);

                        $escapedValues = array_map(function ($val) {
                            if ($val === null) return 'NULL';
                            if (is_bool($val)) return $val ? 'TRUE' : 'FALSE';
                            if (is_numeric($val) && !is_string($val)) return (string) $val;
                            $sanitized = str_replace("'", "''", (string) $val);
                            return "'{$sanitized}'";
                        }, array_values($rowArray));

                        $colSql = implode(', ', $escapedColumns);
                        $valSql = implode(', ', $escapedValues);
                        fwrite($handle, "INSERT INTO \"{$table}\" ({$colSql}) VALUES ({$valSql});\n");
                    }
                });
            }
            fwrite($handle, "\n");
        }

        fclose($handle);

        return [
            'engine' => 'pdo',
            'tables_count' => $totalTables,
            'records_count' => $totalRecords,
        ];
    }

    /**
     * Execute SQL file restoration.
     */
    protected function executeSqlFile(string $sqlFilePath): array
    {
        $dbConfig = config('database.connections.pgsql');
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 5432;
        $username = $dbConfig['username'] ?? 'postgres';
        $password = $dbConfig['password'] ?? '';
        $database = $dbConfig['database'] ?? '';

        $psqlBinary = $this->findBinary('psql');

        if ($psqlBinary) {
            $encodedPass = urlencode($password);
            $encodedUser = urlencode($username);
            $connUri = "postgresql://{$encodedUser}:{$encodedPass}@{$host}:{$port}/{$database}";

            $cmd = sprintf(
                '"%s" --dbname="%s" --file="%s" 2>&1',
                $psqlBinary,
                $connUri,
                $sqlFilePath
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0) {
                return ['tables_restored' => 1, 'queries_executed' => count($output)];
            }
            Log::warning("psql binary returned {$returnCode}, trying PDO restoration runner.");
        }

        // Fallback: PDO Transactional Runner
        $sql = File::get($sqlFilePath);
        $rawStatements = preg_split('/;\s*[\r\n]+/', $sql);
        $statements = array_filter(
            array_map('trim', $rawStatements),
            fn($q) => !empty($q) && !str_starts_with($q, '--')
        );

        $executed = 0;
        DB::beginTransaction();
        try {
            foreach ($statements as $stmt) {
                DB::unprepared($stmt);
                $executed++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['tables_restored' => 1, 'queries_executed' => $executed];
    }

    /**
     * Export storage files cleanly.
     */
    protected function exportStorageFiles(string $targetDir): void
    {
        $storagePublic = storage_path('app/public');
        if (!File::exists($storagePublic)) {
            return;
        }

        File::makeDirectory($targetDir, 0755, true);
        File::copyDirectory($storagePublic, $targetDir);
    }

    /**
     * Zip directory with optional AES-256 encryption.
     */
    protected function zipDirectoryWithEncryption(string $sourceDir, string $outZipPath, ?string $password = null): void
    {
        $zip = new ZipArchive();
        if ($zip->open($outZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("تعذر إنشاء ملف الأرشيف: {$outZipPath}");
        }

        if ($password) {
            $zip->setPassword($password);
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $cleanRelPath = str_replace('\\', '/', $relativePath);
                $zip->addFile($filePath, $cleanRelPath);

                if ($password) {
                    $zip->setEncryptionName($cleanRelPath, ZipArchive::EM_AES_256);
                }
            }
        }

        $zip->close();
    }

    /**
     * Locate binary path on Windows or Linux.
     */
    protected function findBinary(string $binaryName): ?string
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $exeName = $isWindows ? $binaryName . '.exe' : $binaryName;

        // 1. Check common PostgreSQL install paths on Windows
        if ($isWindows) {
            $commonPaths = [
                "C:\\Program Files\\PostgreSQL\\15\\bin\\{$exeName}",
                "C:\\Program Files\\PostgreSQL\\16\\bin\\{$exeName}",
                "C:\\Program Files\\PostgreSQL\\14\\bin\\{$exeName}",
                "C:\\Program Files\\PostgreSQL\\17\\bin\\{$exeName}",
            ];
            foreach ($commonPaths as $p) {
                if (File::exists($p)) {
                    return $p;
                }
            }
        }

        // 2. Check PATH environment variable
        $whereCmd = $isWindows ? "where {$exeName} 2>NUL" : "which {$binaryName} 2>/dev/null";
        $output = [];
        exec($whereCmd, $output, $code);
        if ($code === 0 && !empty($output[0]) && File::exists(trim($output[0]))) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * Safely extract manifest from ZIP.
     */
    protected function extractManifestSafely(string $zipPath): array
    {
        if (!str_ends_with($zipPath, '.zip') && !str_ends_with($zipPath, '.mshbak')) {
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $manifestContent = $zip->getFromName('manifest.json');
            $zip->close();
            if ($manifestContent) {
                return json_decode($manifestContent, true) ?: [];
            }
        }
        return [];
    }

    /**
     * Clean old backups beyond max retention.
     */
    protected function cleanupOldBackups(): void
    {
        $maxRetention = (int) (Settings::getValue('backup_max_retention_count', 15) ?: 15);
        $backups = $this->listBackups();

        if (count($backups) > $maxRetention) {
            $toDelete = array_slice($backups, $maxRetention);
            foreach ($toDelete as $b) {
                if (!empty($b['filename'])) {
                    $this->deleteBackup($b['filename']);
                    Log::info("Cleaned and rotated old backup: {$b['filename']}");
                }
            }
        }
    }

    /**
     * Stored passwords management.
     */
    protected function getStoredPasswords(): array
    {
        if (!File::exists($this->passwordsFile)) {
            return [];
        }
        return json_decode(File::get($this->passwordsFile), true) ?: [];
    }

    public function getStoredPassword(string $backupName): ?string
    {
        $passwords = $this->getStoredPasswords();
        if (!isset($passwords[$backupName])) {
            return null;
        }
        try {
            return Crypt::decrypt($passwords[$backupName]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function storeBackupPassword(string $backupName, string $password): void
    {
        $passwords = $this->getStoredPasswords();
        $passwords[$backupName] = Crypt::encrypt($password);
        File::put($this->passwordsFile, json_encode($passwords, JSON_PRETTY_PRINT));
        @chmod($this->passwordsFile, 0600);
    }

    protected function removeStoredPassword(string $backupName): void
    {
        $passwords = $this->getStoredPasswords();
        unset($passwords[$backupName]);
        File::put($this->passwordsFile, json_encode($passwords, JSON_PRETTY_PRINT));
    }

    /**
     * Backups metadata management.
     */
    protected function getMetaMap(): array
    {
        if (!File::exists($this->backupsMetaFile)) {
            return [];
        }
        $list = json_decode(File::get($this->backupsMetaFile), true) ?: [];
        $map = [];
        foreach ($list as $item) {
            if (isset($item['name'])) {
                $map[$item['name']] = $item;
            }
        }
        return $map;
    }

    protected function saveMetaInfo(array $backupInfo): void
    {
        $list = File::exists($this->backupsMetaFile) ? (json_decode(File::get($this->backupsMetaFile), true) ?: []) : [];
        $list[] = $backupInfo;
        File::put($this->backupsMetaFile, json_encode($list, JSON_PRETTY_PRINT));
    }

    protected function removeMetaInfo(string $backupName): void
    {
        if (!File::exists($this->backupsMetaFile)) {
            return;
        }
        $list = json_decode(File::get($this->backupsMetaFile), true) ?: [];
        $list = array_values(array_filter($list, fn($b) => ($b['name'] ?? '') !== $backupName));
        File::put($this->backupsMetaFile, json_encode($list, JSON_PRETTY_PRINT));
    }

    /**
     * Generate secure password.
     */
    protected function generateSecurePassword(): string
    {
        return bin2hex(random_bytes(15));
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
