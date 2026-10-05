<?php

namespace App\Http\Controllers\admin\settings;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->middleware('permission:backups_settings');
        $this->backupService = $backupService;
    }

    /**
     * Display backup management page.
     */
    public function index()
    {
        $stats = $this->backupService->getStorageStats();
        $config = $this->backupService->getConfig();
        return view('admin.settings.backup.index', compact('stats', 'config'));
    }

    /**
     * Get backups data for DataTable (AJAX).
     */
    public function getData(): JsonResponse
    {
        try {
            $backups = $this->backupService->listBackups();

            $formatted = array_map(function ($b) {
                return [
                    'name' => $b['name'] ?? '',
                    'filename' => $b['filename'] ?? ($b['name'] . '.zip'),
                    'type' => $b['type'] ?? 'database_only',
                    'description' => $b['description'] ?? '',
                    'size' => $b['size'] ?? 0,
                    'size_human' => $b['size_human'] ?? '0 B',
                    'status' => $b['status'] ?? 'completed',
                    'is_encrypted' => (bool) ($b['is_encrypted'] ?? false),
                    'created_at' => $b['created_at'] ?? '',
                    'created_by' => $b['created_by'] ?? 'System',
                    'sha256' => $b['sha256'] ?? '',
                    'file_path' => $b['file_path'] ?? '',
                ];
            }, $backups);

            return response()->json($formatted);
        } catch (Exception $e) {
            Log::error('Failed to get backups data: ' . $e->getMessage());
            return response()->json(['error' => __('Failed to load backups data')], 500);
        }
    }

    /**
     * Create new backup.
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'backup_type' => 'required|in:full,database_only,files_only',
            'description' => 'nullable|string|max:255',
            'is_encrypted' => 'nullable|boolean',
            'password' => 'nullable|string|min:6',
            'destinations' => 'nullable|array',
        ]);

        try {
            $destinations = $request->input('destinations', ['local']);
            if (!in_array('local', $destinations)) {
                $destinations[] = 'local';
            }

            $options = [
                'backup_type' => $request->backup_type,
                'description' => $request->description ?? '',
                'is_encrypted' => $request->boolean('is_encrypted'),
                'password' => $request->password,
                'destinations' => $destinations,
                'created_by' => auth()->user()->name ?? 'Admin',
            ];

            $result = $this->backupService->createBackup($options);

            return response()->json([
                'status' => 1,
                'success' => __('Backup created successfully'),
                'backup' => $result['backup'],
                'elapsed_seconds' => $result['elapsed_seconds'] ?? null,
                'dispatch_results' => $result['dispatch_results'] ?? [],
            ]);
        } catch (Exception $e) {
            Log::error('Backup creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 2,
                'error' => __('Failed to create backup: ') . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Direct file download to user browser.
     */
    public function directDownload(string $backupName): BinaryFileResponse|JsonResponse
    {
        $filename = str_ends_with($backupName, '.zip') ? $backupName : $backupName . '.zip';
        $filePath = $this->backupService->getBackupPath($filename);

        if (!File::exists($filePath)) {
            // Check without extension
            $filePath = $this->backupService->getBackupPath($backupName);
        }

        if (!File::exists($filePath)) {
            return response()->json(['status' => 2, 'error' => __('Backup file not found')], 404);
        }

        return response()->download($filePath, basename($filePath), [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Send backup to user's email.
     */
    public function download(string $backupName): JsonResponse
    {
        try {
            $filename = str_ends_with($backupName, '.zip') ? $backupName : $backupName . '.zip';
            $filePath = $this->backupService->getBackupPath($filename);

            if (!File::exists($filePath)) {
                return response()->json(['status' => 2, 'error' => __('Backup file not found')]);
            }

            $userEmail = auth()->user()->email ?? null;
            if (!$userEmail) {
                return response()->json(['status' => 2, 'error' => 'البريد الإلكتروني للمستخدم غير متوفر.']);
            }

            $res = $this->backupService->dispatchBackup($filename, ['email'], [
                'backup_email_recipient' => $userEmail,
            ]);

            if (!empty($res['email']['success'])) {
                return response()->json(['status' => 1, 'message' => 'تم إرسال النسخة الاحتياطية إلى بريدك الإلكتروني بنجاح.']);
            }

            $errMsg = $res['email']['message'] ?? 'فشل إرسال البريد';
            return response()->json(['status' => 2, 'error' => $errMsg]);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => __('Failed to send backup via email: ') . $e->getMessage()]);
        }
    }

    /**
     * Dispatch an existing backup to Telegram or Email on demand.
     */
    public function dispatchBackup(Request $request, string $backupName): JsonResponse
    {
        $request->validate([
            'destination' => 'required|in:telegram,email',
        ]);

        $destination = $request->destination;
        $filename = str_ends_with($backupName, '.zip') ? $backupName : $backupName . '.zip';

        try {
            $results = $this->backupService->dispatchBackup($filename, [$destination]);

            if (!empty($results[$destination]['success'])) {
                $destName = $destination === 'telegram' ? 'تليجرام' : 'البريد الإلكتروني';
                return response()->json([
                    'status' => 1,
                    'message' => "تم إرسال النسخة الاحتياطية إلى {$destName} بنجاح.",
                    'details' => $results[$destination],
                ]);
            }

            $err = $results[$destination]['message'] ?? 'فشلت عملية الإرسال';
            return response()->json(['status' => 2, 'error' => $err], 422);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete backup.
     */
    public function delete(string $backupName): JsonResponse
    {
        try {
            $filename = str_ends_with($backupName, '.zip') ? $backupName : $backupName . '.zip';
            $this->backupService->deleteBackup($filename);

            return response()->json([
                'status' => 1,
                'success' => __('Backup deleted successfully'),
            ]);
        } catch (Exception $e) {
            Log::error('Backup deletion failed: ' . $e->getMessage());
            return response()->json([
                'status' => 2,
                'error' => __('Failed to delete backup'),
            ], 422);
        }
    }

    /**
     * Restore backup.
     */
    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'backup_name' => 'required|string',
            'restore_type' => 'required|in:full,database_only,files_only',
            'password' => 'nullable|string',
        ]);

        try {
            $filename = str_ends_with($request->backup_name, '.zip') ? $request->backup_name : $request->backup_name . '.zip';
            $result = $this->backupService->restoreBackup($filename, $request->password, [
                'restore_files' => in_array($request->restore_type, ['full', 'files_only']),
            ]);

            return response()->json([
                'status' => 1,
                'success' => $result['message'] ?? __('Backup restored successfully'),
                'details' => $result,
            ]);
        } catch (Exception $e) {
            Log::error('Backup restore failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 2,
                'error' => __('Failed to restore backup: ') . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Upload and restore backup from user's device.
     */
    public function uploadAndRestore(Request $request): JsonResponse
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip|max:1048576', // Max 1GB
            'backup_password' => 'nullable|string',
            'restore_type' => 'required|in:full,database_only,files_only',
        ]);

        $tempUploadedPath = null;
        try {
            $uploadedFile = $request->file('backup_file');
            $tempUploadedPath = storage_path('app/temp/uploaded_' . time() . '_' . Str::random(6) . '.zip');

            if (!File::exists(dirname($tempUploadedPath))) {
                File::makeDirectory(dirname($tempUploadedPath), 0755, true);
            }

            $uploadedFile->move(dirname($tempUploadedPath), basename($tempUploadedPath));

            $result = $this->backupService->restoreBackup($tempUploadedPath, $request->backup_password, [
                'restore_files' => in_array($request->restore_type, ['full', 'files_only']),
            ]);

            @unlink($tempUploadedPath);

            return response()->json([
                'status' => 1,
                'success' => __('Backup restored successfully from uploaded file'),
                'details' => $result,
            ]);
        } catch (Exception $e) {
            if ($tempUploadedPath && File::exists($tempUploadedPath)) {
                @unlink($tempUploadedPath);
            }
            Log::error('Uploaded backup restore failed: ' . $e->getMessage());
            return response()->json([
                'status' => 2,
                'error' => __('Failed to restore backup from uploaded file: ') . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get backups statistics.
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = $this->backupService->getStorageStats();
            return response()->json([
                'status' => 1,
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get backup statistics: ' . $e->getMessage());
            return response()->json([
                'status' => 2,
                'error' => __('Failed to load statistics'),
            ], 500);
        }
    }

    /**
     * Get auto-backup & cloud configuration.
     */
    public function getConfig(): JsonResponse
    {
        try {
            $config = $this->backupService->getConfig();
            return response()->json([
                'status' => 1,
                'config' => $config,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save auto-backup & cloud configuration.
     */
    public function saveConfig(Request $request): JsonResponse
    {
        $request->validate([
            'backup_auto_enabled' => 'nullable',
            'backup_schedule_frequency' => 'nullable|in:daily,weekly',
            'backup_schedule_time' => 'nullable|string',
            'backup_max_retention_count' => 'nullable|integer|min:1|max:100',
            'backup_auto_encrypt' => 'nullable',
            'backup_auto_destinations' => 'nullable|string',
            'telegram_backup_enabled' => 'nullable',
            'backup_telegram_bot_token' => 'nullable|string',
            'backup_telegram_chat_id' => 'nullable|string',
            'email_backup_enabled' => 'nullable',
            'backup_email_recipient' => 'nullable|string',
        ]);

        try {
            $data = $request->only([
                'backup_auto_enabled',
                'backup_schedule_frequency',
                'backup_schedule_time',
                'backup_max_retention_count',
                'backup_auto_encrypt',
                'backup_auto_destinations',
                'telegram_backup_enabled',
                'backup_telegram_bot_token',
                'backup_telegram_chat_id',
                'email_backup_enabled',
                'backup_email_recipient',
            ]);

            // Format boolean toggles
            $data['backup_auto_enabled'] = $request->boolean('backup_auto_enabled') ? '1' : '0';
            $data['backup_auto_encrypt'] = $request->boolean('backup_auto_encrypt') ? '1' : '0';
            $data['telegram_backup_enabled'] = $request->boolean('telegram_backup_enabled') ? '1' : '0';
            $data['email_backup_enabled'] = $request->boolean('email_backup_enabled') ? '1' : '0';

            $this->backupService->saveConfig($data);

            return response()->json([
                'status' => 1,
                'message' => 'تم حفظ إعدادات النسخ الاحتياطي التلقائي والسحابي بنجاح.',
                'config' => $this->backupService->getConfig(),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Test connection to Telegram or Email.
     */
    public function testDestination(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:telegram,email',
            'telegram_backup_bot_token' => 'nullable|string',
            'telegram_backup_chat_id' => 'nullable|string',
            'backup_email_recipient' => 'nullable|string',
        ]);

        try {
            $res = $this->backupService->testCloudDestination($request->type, $request->all());
            if (!empty($res['success'])) {
                return response()->json(['status' => 1, 'message' => $res['message']]);
            }
            return response()->json(['status' => 2, 'error' => $res['message']], 422);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()], 422);
        }
    }
}
