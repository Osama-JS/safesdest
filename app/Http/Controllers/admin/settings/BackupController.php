<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
  private $backupsPath;
  private $tempPath;
  private $passwordsFile;

  public function __construct()
  {
    $this->middleware('permission:backups_settings');
    $this->backupsPath = storage_path('app/backups');
    $this->tempPath = storage_path('app/temp');
    $this->passwordsFile = storage_path('app/backups/passwords.json');

    // Ensure directories exist
    $this->ensureDirectoriesExist();
  }

  /**
   * Ensure required directories exist with proper permissions
   */
  private function ensureDirectoriesExist()
  {
    if (!File::exists($this->backupsPath)) {
      File::makeDirectory($this->backupsPath, 0755, true);
    }

    if (!File::exists($this->tempPath)) {
      File::makeDirectory($this->tempPath, 0755, true);
    }
  }

  /**
   * Display backup management page
   */
  public function index()
  {
    $backups = $this->getBackupsList();
    return view('admin.settings.backup.index', compact('backups'));
  }

  /**
   * Get backups data for DataTable (AJAX endpoint)
   */
  public function getData()
  {
    try {
      $backups = $this->getBackupsList();

      // Format data for DataTable
      $formattedBackups = array_map(function ($backup) {
        return [
          'name' => $backup['name'] ?? '',
          'type' => $backup['type'] ?? '',
          'description' => $backup['description'] ?? '',
          'size' => $backup['size'] ?? 0,
          'status' => $backup['status'] ?? 'unknown',
          'created_at' => $backup['created_at'] ?? '',
          'created_by' => $backup['created_by'] ?? '',
          'file_path' => $backup['file_path'] ?? ''
        ];
      }, $backups);

      return response()->json($formattedBackups);
    } catch (Exception $e) {
      Log::error('Failed to get backups data', ['error' => $e->getMessage()]);

      return response()->json([
        'error' => __('Failed to load backups data')
      ], 500);
    }
  }

  /**
   * Create new backup with enhanced security and error handling
   */
  public function create(Request $request)
  {
    $request->validate([
      'backup_type' => 'required|in:full,database_only,files_only',
      'description' => 'nullable|string|max:255'
    ]);

    $backupName = null;
    $tempBackupPath = null;

    try {
      // Generate unique backup name
      $backupName = 'backup_' . Carbon::now()->format('Y_m_d_H_i_s') . '_' . substr(md5(uniqid()), 0, 8);
      $tempBackupPath = $this->tempPath . '/' . $backupName;

      // Create temporary backup directory
      if (!File::makeDirectory($tempBackupPath, 0755, true)) {
        throw new Exception('Failed to create temporary backup directory');
      }

      // Initialize backup info
      $backupInfo = [
        'name' => $backupName,
        'type' => $request->backup_type,
        'description' => $request->description ?? '',
        'created_at' => Carbon::now()->toISOString(),
        'size' => 0,
        'status' => 'processing',
        'created_by' => auth()->user()->name ?? 'System'
      ];

      // Save initial backup info
      $this->saveBackupInfo($backupInfo);

      Log::info('Starting backup creation', [
        'backup_name' => $backupName,
        'type' => $request->backup_type,
        'user' => auth()->user()->name ?? 'System'
      ]);

      // Create database backup if required
      if (in_array($request->backup_type, ['full', 'database_only'])) {
        $this->createPostgreSQLBackup($tempBackupPath);
        Log::info('Database backup completed', ['backup_name' => $backupName]);
      }

      // Create files backup if required
      if (in_array($request->backup_type, ['full', 'files_only'])) {
        $this->createFilesBackup($tempBackupPath);
        Log::info('Files backup completed', ['backup_name' => $backupName]);
      }

      // Compress and encrypt backup
      $finalBackupPath = $this->compressAndEncryptBackup($tempBackupPath, $backupName);

      // Update backup info with success status
      $backupInfo['size'] = File::size($finalBackupPath);
      $backupInfo['status'] = 'completed';
      $backupInfo['file_path'] = $finalBackupPath;
      $backupInfo['completed_at'] = Carbon::now()->toISOString();

      $this->updateBackupInfo($backupName, $backupInfo);

      // Clean up temporary files
      File::deleteDirectory($tempBackupPath);

      Log::info('Backup created successfully', [
        'backup_name' => $backupName,
        'size' => $backupInfo['size'],
        'type' => $request->backup_type
      ]);

      return response()->json([
        'status' => 1,
        'success' => __('Backup created successfully'),
        'backup' => $backupInfo
      ]);
    } catch (Exception $e) {
      // Clean up on failure
      if ($tempBackupPath && File::exists($tempBackupPath)) {
        File::deleteDirectory($tempBackupPath);
      }

      // Update backup status to failed
      if ($backupName) {
        $this->updateBackupInfo($backupName, [
          'status' => 'failed',
          'error' => $e->getMessage(),
          'failed_at' => Carbon::now()->toISOString()
        ]);
      }

      Log::error('Backup creation failed', [
        'backup_name' => $backupName ?? 'unknown',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'status' => 2,
        'error' => __('Failed to create backup: ') . $e->getMessage()
      ]);
    }
  }

  /**
   * Create PostgreSQL database backup
   */
  private function createPostgreSQLBackup($backupPath)
  {
    $dbConfig = config('database.connections.pgsql');
    $dumpPath = $backupPath . '/database.sql';

    // Validate database configuration
    if (!$dbConfig || !isset($dbConfig['host'], $dbConfig['database'], $dbConfig['username'])) {
      throw new Exception('Invalid PostgreSQL database configuration');
    }

    $pgDumpBinary = 'pg_dump'; // fallback إلى "pg_dump" لو لم يُحدد

    // Build pg_dump command with proper escaping
    $command = sprintf(
      '%s --host=%s --port=%s --username=%s --dbname=%s --no-password --clean --if-exists --no-owner --file=%s 2>&1',
      escapeshellarg($pgDumpBinary),
      escapeshellarg($dbConfig['host']),
      escapeshellarg($dbConfig['port'] ?? 5432),
      escapeshellarg($dbConfig['username']),
      escapeshellarg($dbConfig['database']),
      escapeshellarg($dumpPath)
    );

    // Set PGPASSWORD environment variable securely
    $originalPgPassword = getenv('PGPASSWORD');
    putenv('PGPASSWORD=' . $dbConfig['password']);

    try {
      // Execute pg_dump command
      exec($command, $output, $returnCode);

      Log::info('PostgreSQL backup command executed', [
        'command' => str_replace($dbConfig['password'], '***', $command),
        'return_code' => $returnCode,
        'output_lines' => count($output)
      ]);

      if ($returnCode !== 0) {
        throw new Exception('PostgreSQL backup failed with return code ' . $returnCode . ': ' . implode("\n", $output));
      }

      // Verify backup file was created and has content
      if (!File::exists($dumpPath)) {
        throw new Exception('PostgreSQL backup file was not created');
      }

      if (File::size($dumpPath) === 0) {
        throw new Exception('PostgreSQL backup file is empty');
      }

      Log::info('PostgreSQL backup completed successfully', [
        'file_size' => File::size($dumpPath),
        'file_path' => $dumpPath
      ]);
    } finally {
      // Restore original PGPASSWORD or unset it
      if ($originalPgPassword !== false) {
        putenv('PGPASSWORD=' . $originalPgPassword);
      } else {
        putenv('PGPASSWORD');
      }
    }
  }

  /**
   * Create files backup
   */
  private function createFilesBackup($backupPath)
  {
    $storagePath = storage_path('app/public');
    $filesBackupPath = $backupPath . '/files';

    if (!File::exists($storagePath)) {
      Log::warning('Storage path does not exist, skipping files backup', ['path' => $storagePath]);
      return;
    }

    try {
      // Create files backup directory
      if (!File::makeDirectory($filesBackupPath, 0755, true)) {
        throw new Exception('Failed to create files backup directory');
      }

      // Copy all files from storage/app/public
      File::copyDirectory($storagePath, $filesBackupPath);

      $fileCount = count(File::allFiles($filesBackupPath));
      $totalSize = $this->getDirectorySize($filesBackupPath);

      Log::info('Files backup completed successfully', [
        'file_count' => $fileCount,
        'total_size' => $totalSize,
        'backup_path' => $filesBackupPath
      ]);
    } catch (Exception $e) {
      Log::error('Files backup failed', ['error' => $e->getMessage()]);
      throw new Exception('Files backup failed: ' . $e->getMessage());
    }
  }

  /**
   * Compress and encrypt backup with enhanced security
   */
  private function compressAndEncryptBackup($backupPath, $backupName)
  {
    $zipPath = $this->backupsPath . '/' . $backupName . '.zip';
    $password = $this->generateSecurePassword();

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
      throw new Exception('Cannot create zip file: ' . $zipPath);
    }

    try {
      // Set password for the entire archive
      $zip->setPassword($password);

      // Add and encrypt all files
      $files = File::allFiles($backupPath);
      foreach ($files as $file) {
        $relativePath = str_replace($backupPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $zip->addFile($file->getPathname(), $relativePath);
        // Encrypt each file individually with AES-256
        $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
      }

      $zip->close();

      // Verify the backup was created successfully
      if (!File::exists($zipPath) || File::size($zipPath) === 0) {
        throw new Exception('Backup archive was not created or is empty');
      }

      // Store password securely
      $this->storeBackupPassword($backupName, $password);

      Log::info('Backup compressed and encrypted successfully', [
        'backup_name' => $backupName,
        'zip_path' => $zipPath,
        'file_size' => File::size($zipPath)
      ]);

      return $zipPath;
    } catch (Exception $e) {
      if ($zip) {
        $zip->close();
      }
      if (File::exists($zipPath)) {
        File::delete($zipPath);
      }
      throw $e;
    }
  }

  /**
   * Generate cryptographically secure password
   */
  private function generateSecurePassword()
  {
    return bin2hex(random_bytes(15)); // 64-character random password
  }

  /**
   * Store backup password securely using Laravel encryption
   */
  private function storeBackupPassword($backupName, $password)
  {
    $passwords = [];
    if (File::exists($this->passwordsFile)) {
      $passwords = json_decode(File::get($this->passwordsFile), true) ?: [];
    }

    // Encrypt password using Laravel's encryption
    $passwords[$backupName] = Crypt::encrypt($password);

    File::put($this->passwordsFile, json_encode($passwords, JSON_PRETTY_PRINT));

    // Set restrictive permissions on password file
    chmod($this->passwordsFile, 0600);
  }

  /**
   * Retrieve backup password securely
   */
  private function getBackupPassword($backupName)
  {
    if (!File::exists($this->passwordsFile)) {
      throw new Exception('Password file not found');
    }

    $passwords = json_decode(File::get($this->passwordsFile), true) ?: [];

    if (!isset($passwords[$backupName])) {
      throw new Exception('Password not found for backup: ' . $backupName);
    }

    return Crypt::decrypt($passwords[$backupName]);
  }

  /**
   * Get directory size in bytes
   */
  private function getDirectorySize($directory)
  {
    $size = 0;
    $files = File::allFiles($directory);
    foreach ($files as $file) {
      $size += $file->getSize();
    }
    return $size;
  }

  /**
   * Download backup file
   */


  public function download($backupName)
  {
    try {
      $backupInfo = $this->getBackupInfo($backupName);

      if (!$backupInfo || !File::exists($backupInfo['file_path'])) {
        return response()->json(['status' => 2, 'error' => __('Backup file not found')]);
      }

      // حدد الإيميل المُرسل إليه (ممكن ديناميكي من الطلب)
      $toEmail = auth()->user()->email; // أو auth()->user()->email مثلاً

      $password = $this->getBackupPassword($backupName);
      $type = $backupInfo['type'];
      $created_at = date('Y-m-d H:i:s', strtotime($backupInfo['completed_at']));

      // بيانات الإيميل
      $emailData = [
        'to' => $toEmail,
        'subject' => __('Safedest Backup'),
        'type' => 'backup',
        'content' => __('The backup is attached with all its data in the email'),
        'template' => 'emails.notification',
        'priority' => 'normal',
        'additional_data' => [
          'backup_name' => $backupName,
          'generated_at' => $created_at,
          'type' => $type,
          'password' => $password
        ]
      ];

      // إعداد المرفق

      // الكود الجديد (حل)
      $attachments = [
        [
          'file' => $backupInfo['file_path'],
          'options' => ['as' => $backupName . '.zip', 'mime' => 'application/zip']
        ]
      ];


      // إرسال المهمة
      SendEmailNotificationJob::dispatch($emailData, $attachments);

      Log::info('Send Backup To Email', [
        'backup_name' => $backupName,
        'type' => $type,
        'user' => $toEmail
      ]);

      return response()->json(['status' => 1, 'message' => 'تم إرسال النسخة الاحتياطية إلى البريد الإلكتروني.']);
    } catch (Exception $e) {
      return response()->json(['status' => 2, 'error' => __('Failed to send backup via email')]);
    }
  }


  /**
   * Delete backup with secure cleanup
   */
  public function delete($backupName)
  {
    try {
      $backupInfo = $this->getBackupInfo($backupName);

      if (!$backupInfo) {
        return response()->json(['status' => 2, 'error' => __('Backup not found')]);
      }

      // Delete backup file if exists
      if (isset($backupInfo['file_path']) && File::exists($backupInfo['file_path'])) {
        File::delete($backupInfo['file_path']);
      }

      // Remove password from passwords file
      $this->removeBackupPassword($backupName);

      // Remove from backups list
      $this->removeBackupInfo($backupName);

      Log::info('Backup deleted successfully', ['backup_name' => $backupName]);

      return response()->json([
        'status' => 1,
        'success' => __('Backup deleted successfully')
      ]);
    } catch (Exception $e) {
      Log::error('Backup deletion failed', [
        'backup_name' => $backupName,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'status' => 2,
        'error' => __('Failed to delete backup')
      ]);
    }
  }

  /**
   * Restore backup with enhanced security and validation
   */
  public function restore(Request $request)
  {
    $request->validate([
      'backup_name' => 'required|string',
      'restore_type' => 'required|in:full,database_only,files_only'
    ]);

    $backupName = $request->backup_name;
    $restoreType = $request->restore_type;
    $tempRestorePath = null;

    try {
      // Get backup info
      $backupInfo = $this->getBackupInfo($backupName);
      if (!$backupInfo || !File::exists($backupInfo['file_path'])) {
        return response()->json(['status' => 2, 'error' => __('Backup file not found')]);
      }

      // Create temporary restore directory
      $tempRestorePath = $this->tempPath . '/restore_' . $backupName . '_' . time();
      if (!File::makeDirectory($tempRestorePath, 0755, true)) {
        throw new Exception('Failed to create temporary restore directory');
      }

      // Extract and decrypt backup
      $this->extractBackup($backupInfo['file_path'], $tempRestorePath, $backupName);

      Log::info('Starting backup restore', [
        'backup_name' => $backupName,
        'restore_type' => $restoreType,
        'user' => auth()->user()->name ?? 'System'
      ]);

      // Restore database if required
      if (in_array($restoreType, ['full', 'database_only'])) {
        $this->restoreDatabase($tempRestorePath);
        Log::info('Database restore completed', ['backup_name' => $backupName]);
      }

      // Restore files if required
      if (in_array($restoreType, ['full', 'files_only'])) {
        $this->restoreFiles($tempRestorePath);
        Log::info('Files restore completed', ['backup_name' => $backupName]);
      }

      // Clean up temporary files
      File::deleteDirectory($tempRestorePath);

      Log::info('Backup restored successfully', [
        'backup_name' => $backupName,
        'restore_type' => $restoreType
      ]);

      return response()->json([
        'status' => 1,
        'success' => __('Backup restored successfully')
      ]);
    } catch (Exception $e) {
      // Clean up on failure
      if ($tempRestorePath && File::exists($tempRestorePath)) {
        File::deleteDirectory($tempRestorePath);
      }

      Log::error('Backup restore failed', [
        'backup_name' => $backupName,
        'restore_type' => $restoreType,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->json([
        'status' => 2,
        'error' => __('Failed to restore backup: ') . $e->getMessage()
      ]);
    }
  }



  /**
   * Upload and restore backup from user's device
   */
  public function uploadAndRestore(Request $request)
  {
    $request->validate([
      'backup_file' => 'required|file|mimes:zip|max:1048576', // Max 1GB
      'backup_password' => 'required|string|min:8',
      'restore_type' => 'required|in:full,database_only,files_only'
    ]);
    $tempRestorePath = null;
    $uploadedFilePath = null;
    try {
      // Store uploaded file temporarily
      $uploadedFile = $request->file('backup_file');
      $uploadedFilePath = $uploadedFile->storeAs('temp/uploads', 'uploaded_backup_' . time() . '.zip');
      $fullUploadPath = storage_path('app/' . $uploadedFilePath);
      // Create temporary restore directory
      $tempRestorePath = $this->tempPath . '/upload_restore_' . time();
      if (!File::makeDirectory($tempRestorePath, 0755, true)) {
        throw new Exception('Failed to create temporary restore directory');
      }
      // Extract uploaded backup with provided password
      $this->extractUploadedBackup($fullUploadPath, $tempRestorePath, $request->backup_password);
      Log::info('Starting uploaded backup restore', ['restore_type' => $request->restore_type, 'file_size' => $uploadedFile->getSize(), 'user' => auth()->user()->name ?? 'System']);
      // Restore database if required
      if (in_array($request->restore_type, ['full', 'database_only'])) {
        $this->restoreDatabase($tempRestorePath);
        Log::info('Database restore from upload completed');
      }
      // Restore files if required
      if (in_array($request->restore_type, ['full', 'files_only'])) {
        $this->restoreFiles($tempRestorePath);
        Log::info('Files restore from upload completed');
      }
      // Clean up temporary files
      File::deleteDirectory($tempRestorePath);
      Storage::delete($uploadedFilePath);
      Log::info('Uploaded backup restored successfully', ['restore_type' => $request->restore_type]);
      return response()->json(['status' => 1, 'success' => __('Backup restored successfully from uploaded file')]);
    } catch (Exception $e) {
      // Clean up on failure
      if ($tempRestorePath && File::exists($tempRestorePath)) {
        File::deleteDirectory($tempRestorePath);
      }
      if ($uploadedFilePath && Storage::exists($uploadedFilePath)) {
        Storage::delete($uploadedFilePath);
      }
      Log::error('Uploaded backup restore failed', ['restore_type' => $request->restore_type ?? 'unknown', 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
      return response()->json(['status' => 2, 'error' => __('Failed to restore backup from uploaded file: ') . $e->getMessage()]);
    }
  }

  /**
   * Extract uploaded backup with user-provided password
   */
  private function extractUploadedBackup($zipPath, $extractPath, $password)
  {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== TRUE) {
      throw new Exception('Cannot open uploaded backup archive');
    }
    $zip->setPassword($password);
    if (!$zip->extractTo($extractPath)) {
      $zip->close();
      throw new Exception('Failed to extract uploaded backup archive. Check password.');
    }
    $zip->close();
    Log::info('Uploaded backup extracted successfully', ['extract_path' => $extractPath]);
  }


  /**
   * Extract backup archive
   */
  private function extractBackup($zipPath, $extractPath, $backupName)
  {
    $password = $this->getBackupPassword($backupName);

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== TRUE) {
      throw new Exception('Cannot open backup archive');
    }

    $zip->setPassword($password);

    if (!$zip->extractTo($extractPath)) {
      $zip->close();
      throw new Exception('Failed to extract backup archive');
    }

    $zip->close();

    Log::info('Backup extracted successfully', [
      'backup_name' => $backupName,
      'extract_path' => $extractPath
    ]);
  }

  /**
   * Restore database from backup
   */
  private function restoreDatabase($restorePath)
  {
    $sqlFile = $restorePath . '/database.sql';

    if (!File::exists($sqlFile)) {
      throw new Exception('Database backup file not found');
    }

    $dbConfig = config('database.connections.pgsql');
    $pgDumpBinary = 'C:\Program Files\PostgreSQL\15\bin\psql.exe'; // fallback إلى "pg_dump" لو لم يُحدد

    // Build psql command for restore
    $command = sprintf(
      '%s --host=%s --port=%s --username=%s --dbname=%s --no-password --file=%s 2>&1',
      escapeshellarg($pgDumpBinary),
      escapeshellarg($dbConfig['host']),
      escapeshellarg($dbConfig['port'] ?? 5432),
      escapeshellarg($dbConfig['username']),
      escapeshellarg($dbConfig['database']),
      escapeshellarg($sqlFile)
    );




    // Set PGPASSWORD environment variable securely
    $originalPgPassword = getenv('PGPASSWORD');
    putenv('PGPASSWORD=' . $dbConfig['password']);

    try {
      exec($command, $output, $returnCode);

      Log::info('Database restore command executed', [
        'return_code' => $returnCode,
        'output_lines' => count($output)
      ]);

      if ($returnCode !== 0) {
        throw new Exception('Database restore failed with return code ' . $returnCode . ': ' . implode("\n", $output));
      }
    } finally {
      // Restore original PGPASSWORD or unset it
      if ($originalPgPassword !== false) {
        putenv('PGPASSWORD=' . $originalPgPassword);
      } else {
        putenv('PGPASSWORD');
      }
    }
  }

  /**
   * Restore files from backup
   */
  private function restoreFiles($restorePath)
  {
    $filesBackupPath = $restorePath . '/files';
    $storagePath = storage_path('app/public');

    if (!File::exists($filesBackupPath)) {
      Log::warning('Files backup not found, skipping files restore', ['path' => $filesBackupPath]);
      return;
    }

    try {
      // Backup current files before restore (safety measure)
      $backupCurrentPath = storage_path('app/temp/current_files_backup_' . time());
      if (File::exists($storagePath)) {
        File::copyDirectory($storagePath, $backupCurrentPath);
        Log::info('Current files backed up before restore', ['backup_path' => $backupCurrentPath]);
      }

      // Clear current storage directory
      if (File::exists($storagePath)) {
        File::deleteDirectory($storagePath);
      }

      // Restore files from backup
      File::copyDirectory($filesBackupPath, $storagePath);

      $fileCount = count(File::allFiles($storagePath));
      Log::info('Files restored successfully', [
        'file_count' => $fileCount,
        'storage_path' => $storagePath
      ]);
    } catch (Exception $e) {
      Log::error('Files restore failed', ['error' => $e->getMessage()]);
      throw new Exception('Files restore failed: ' . $e->getMessage());
    }
  }

  /**
   * Remove backup password from passwords file
   */
  private function removeBackupPassword($backupName)
  {
    if (!File::exists($this->passwordsFile)) {
      return;
    }

    $passwords = json_decode(File::get($this->passwordsFile), true) ?: [];
    unset($passwords[$backupName]);

    File::put($this->passwordsFile, json_encode($passwords, JSON_PRETTY_PRINT));
  }

  /**
   * Get backups statistics
   */
  public function getStatistics()
  {
    try {
      $backups = $this->getBackupsList();

      $totalBackups = count($backups);
      $totalSize = 0;
      $latestBackup = null;
      $backupTypes = ['full' => 0, 'database_only' => 0, 'files_only' => 0];
      $completedBackups = 0;

      foreach ($backups as $backup) {
        $totalSize += $backup['size'] ?? 0;

        if (isset($backup['type'])) {
          $backupTypes[$backup['type']]++;
        }

        if ($backup['status'] === 'completed') {
          $completedBackups++;
        }

        if (!$latestBackup || (isset($backup['created_at']) && $backup['created_at'] > $latestBackup)) {
          $latestBackup = $backup['created_at'] ?? null;
        }
      }

      return response()->json([
        'status' => 1,
        'data' => [
          'total_backups' => $totalBackups,
          'completed_backups' => $completedBackups,
          'total_size' => $totalSize,
          'latest_backup' => $latestBackup,
          'backup_types' => $backupTypes,
          'success_rate' => $totalBackups > 0 ? round(($completedBackups / $totalBackups) * 100, 2) : 0
        ]
      ]);
    } catch (Exception $e) {
      Log::error('Failed to get backup statistics', ['error' => $e->getMessage()]);

      return response()->json([
        'status' => 2,
        'error' => __('Failed to load statistics')
      ]);
    }
  }

  /**
   * Get backups list from JSON file
   */
  private function getBackupsList()
  {
    $backupsFile = $this->backupsPath . '/backups.json';

    if (!File::exists($backupsFile)) {
      return [];
    }

    $backups = json_decode(File::get($backupsFile), true);
    return is_array($backups) ? $backups : [];
  }

  /**
   * Get specific backup info
   */
  private function getBackupInfo($backupName)
  {
    $backups = $this->getBackupsList();

    foreach ($backups as $backup) {
      if ($backup['name'] === $backupName) {
        return $backup;
      }
    }

    return null;
  }

  /**
   * Save backup info to JSON file
   */
  private function saveBackupInfo($backupInfo)
  {
    $backups = $this->getBackupsList();
    $backups[] = $backupInfo;

    $backupsFile = $this->backupsPath . '/backups.json';
    File::put($backupsFile, json_encode($backups, JSON_PRETTY_PRINT));
  }

  /**
   * Update backup info in JSON file
   */
  private function updateBackupInfo($backupName, $updateData)
  {
    $backups = $this->getBackupsList();

    foreach ($backups as &$backup) {
      if ($backup['name'] === $backupName) {
        $backup = array_merge($backup, $updateData);
        break;
      }
    }

    $backupsFile = $this->backupsPath . '/backups.json';
    File::put($backupsFile, json_encode($backups, JSON_PRETTY_PRINT));
  }

  /**
   * Remove backup info from JSON file
   */
  private function removeBackupInfo($backupName)
  {
    $backups = $this->getBackupsList();

    $backups = array_filter($backups, function ($backup) use ($backupName) {
      return $backup['name'] !== $backupName;
    });

    $backupsFile = $this->backupsPath . '/backups.json';
    File::put($backupsFile, json_encode(array_values($backups), JSON_PRETTY_PRINT));
  }

  /**
   * Clean up stuck processing backups (utility function)
   */
  public function cleanupStuckBackups()
  {
    try {
      $backups = $this->getBackupsList();
      $cleaned = 0;

      foreach ($backups as $backup) {
        if ($backup['status'] === 'processing') {
          // Check if backup is older than 1 hour
          $createdAt = Carbon::parse($backup['created_at']);
          if ($createdAt->diffInHours(Carbon::now()) > 1) {
            $this->updateBackupInfo($backup['name'], [
              'status' => 'failed',
              'error' => 'Backup timed out',
              'failed_at' => Carbon::now()->toISOString()
            ]);
            $cleaned++;
          }
        }
      }

      Log::info('Cleaned up stuck backups', ['count' => $cleaned]);

      return response()->json([
        'status' => 1,
        'message' => "Cleaned up {$cleaned} stuck backups"
      ]);
    } catch (Exception $e) {
      Log::error('Failed to cleanup stuck backups', ['error' => $e->getMessage()]);

      return response()->json([
        'status' => 2,
        'error' => __('Failed to cleanup stuck backups')
      ]);
    }
  }
}
