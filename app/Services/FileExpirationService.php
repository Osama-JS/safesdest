<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\FileExpirationNotification;
use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class FileExpirationService
{
    protected $emailService;
    protected $platformEmail;
    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
        $this->platformEmail = config('app.platform_notification_email', 'osama.samomy@gmail.com');
    }
    public function checkAndNotifyExpiredFiles(): array
    {
        $results = [
            'users_checked' => 0,
            'customers_checked' => 0,
            'drivers_checked' => 0,
            'notifications_sent' => 0,
            'accounts_suspended' => 0,
            'expired_files' => [], // <-- هنا نخزن الملفات المنتهية
            'errors' => []
        ];
        DB::beginTransaction();
        try {
            Log::info('Starting file expiration check process');

            $results['users_checked'] = $this->checkUsersFiles($results['expired_files']);
            $results['customers_checked'] = $this->checkCustomersFiles($results['expired_files']);
            $results['drivers_checked'] = $this->checkDriversFiles($results['expired_files']);


            $results['notifications_sent'] = FileExpirationNotification::sentToday()->count();
            $results['accounts_suspended'] = $this->suspendAccountsWithExpiredFiles();

            DB::commit();

            // إرسال تقرير للـ admin
            if (!empty($results['expired_files'])) {
                $this->sendAdminReport($results['expired_files']);
            }

            Log::info('File expiration check completed successfully', $results);
        } catch (Exception $e) {
            DB::rollBack();
            $error = 'File expiration check failed: ' . $e->getMessage();
            $results['errors'][] = $error;
            Log::error($error, [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            $this->notifyAdminOfError($e, $results);
        }
        return $results;
    }
    protected function checkUsersFiles(array &$expiredFiles): int
    {
        try {
            $users = User::where('status', 'active')
                ->whereNotNull('additional_data')
                ->get();
            $count = 0;
            foreach ($users as $user) {
                if ($this->processUserFiles($user, 'user', $expiredFiles)) {
                    $count++;
                }
            }
            Log::info("Checked {$users->count()} users, {$count} had expired files");
            return $users->count();
        } catch (Exception $e) {
            Log::error('Error checking users files', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function checkCustomersFiles(array &$expiredFiles): int
    {
        try {
            $customers = Customer::where('status', 'active')
                ->whereNotNull('additional_data')
                ->get();
            $count = 0;
            foreach ($customers as $customer) {
                if ($this->processUserFiles($customer, 'customer', $expiredFiles)) {
                    $count++;
                }
            }
            Log::info("Checked {$customers->count()} customers, {$count} had expired files");
            return $customers->count();
        } catch (Exception $e) {
            Log::error('Error checking customers files', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function checkDriversFiles(array &$expiredFiles): int
    {
        try {
            $drivers = Driver::where('status', 'active')
                ->whereNotNull('additional_data')
                ->with(['team.users.user' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->get();
            $count = 0;
            foreach ($drivers as $driver) {
                if ($this->processUserFiles($driver, 'driver', $expiredFiles)) {
                    $count++;
                }
            }
            Log::info("Checked {$drivers->count()} drivers, {$count} had expired files");
            return $drivers->count();
        } catch (Exception $e) {
            Log::error('Error checking drivers files', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function processUserFiles($user, string $userType, array &$expiredFiles = []): bool
    {
        if (!is_array($user->additional_data)) {
            return false;
        }

        $hasExpiredFiles = false;

        foreach ($user->additional_data as $fieldName => $fieldData) {
            if (!isset($fieldData['type']) || $fieldData['type'] !== 'file_expiration_date') {
                continue;
            }

            if (!isset($fieldData['expiration']) || !$fieldData['expiration']) {
                continue;
            }

            try {
                $expirationDate = Carbon::parse($fieldData['expiration']);
                $today = now()->startOfDay();

                if ($expirationDate->lte($today->copy()->addDay())) {
                    // إضافة الملف لقائمة المنتهين
                    $expiredFiles[] = [
                        'user_type' => $userType,
                        'user_name' => $user->name,
                        'email' => $user->email,
                        'field_label' => $fieldData['label'],
                        'expiration_date' => $expirationDate->format('Y-m-d')
                    ];

                    $existingNotification = FileExpirationNotification::where([
                        'user_type' => $userType,
                        'user_id' => $user->id,
                        'field_name' => $fieldName,
                        'notification_sent_date' => today()
                    ])->first();

                    if (!$existingNotification) {
                        $this->sendExpirationNotification($user, $userType, $fieldName, $fieldData, $expirationDate);
                        $hasExpiredFiles = true;
                    }
                }
            } catch (Exception $e) {
                Log::error('Error processing file expiration', [
                    'user_type' => $userType,
                    'user_id' => $user->id,
                    'field_name' => $fieldName,
                    'error' => $e->getMessage()
                ]);
            }
        }
        return $hasExpiredFiles;
    }

    protected function sendAdminReport(array $expiredFiles)
    {

        // إذا كانت المصفوفة فارغة لا ترسل الإيميل
        if (empty($expiredFiles)) {
            return; // لا يوجد ملفات منتهية
        }

        $adminEmail = config('app.admin_email', 'osama.samomy@gmail.com');

        $htmlTable = "<table class='report-table'>
        <tr>
            <th>User Type</th>
            <th>Name</th>
            <th>Email</th>
            <th>File</th>
            <th>Expiration Date</th>
        </tr>";

        foreach ($expiredFiles as $file) {
            $htmlTable .= "<tr>
            <td>{$file['user_type']}</td>
            <td>{$file['user_name']}</td>
            <td>{$file['email']}</td>
            <td>{$file['field_label']}</td>
            <td>{$file['expiration_date']}</td>
        </tr>";
        }
        $htmlTable .= "</table>";

        $this->emailService->sendHighPriority([
            'to' => $adminEmail,
            'subject' => 'Expired Files Report',
            'template' => 'emails.admin-expired-files-report',
            'report_html' => $htmlTable
        ]);
    }

    protected function sendExpirationNotification($user, string $userType, string $fieldName, array $fieldData, Carbon $expirationDate)
    {
        try {
            $daysBeforeExpiration = now()->startOfDay()->diffInDays($expirationDate, false);
            $recipients = [$user->email];
            // إضافة مدير الفريق للسائقين
            if ($userType === 'driver' && $user->team_id && $user->team) {
                $teamManagers = $user->team->users()->with('user')->get();
                foreach ($teamManagers as $teamUser) {
                    if ($teamUser->user && $teamUser->user->status === 'active') {
                        $recipients[] = $teamUser->user->email;
                    }
                }
            }
            // إضافة إيميل المنصة
            $recipients = [];
            // إزالة التكرارات وتنظيف القائمة
            $recipients = array_unique(array_filter($recipients));
            // إرسال الإيميل لكل مستلم
            foreach ($recipients as $email) {
                $this->emailService->sendWithTemplate(
                    'emails.file-expiration-notification',
                    $email,
                    'تنبيه: انتهاء صلاحية الملف - ' . $fieldData['label'],
                    [
                        'user_name' => $user->name,
                        'user_type' => $this->getUserTypeInArabic($userType),
                        'field_label' => $fieldData['label'],
                        'expiration_date' => $expirationDate->format('Y-m-d'),
                        'days_remaining' => max(0, $daysBeforeExpiration),
                        'is_expired' => $daysBeforeExpiration < 0,
                        'file_path' => $fieldData['value'] ?? null,
                        'action_url' => $this->getUpdateUrl($userType),
                        'action_text' => 'تحديث الملف'
                    ]
                );
            }
            // تسجيل التنبيه في قاعدة البيانات
            FileExpirationNotification::createSafely([
                'user_type' => $userType,
                'user_id' => $user->id,
                'field_name' => $fieldName,
                'field_label' => $fieldData['label'],
                'file_path' => $fieldData['value'] ?? '',
                'expiration_date' => $expirationDate,
                'notification_sent_date' => today(),
                'days_before_expiration' => $daysBeforeExpiration,
                'recipients' => $recipients
            ]);
            Log::info('File expiration notification sent successfully', [
                'user_type' => $userType,
                'user_id' => $user->id,
                'field_name' => $fieldName,
                'field_label' => $fieldData['label'],
                'expiration_date' => $expirationDate->format('Y-m-d'),
                'recipients_count' => count($recipients)
            ]);
        } catch (Exception $e) {
            Log::error('Error sending expiration notification', [
                'user_type' => $userType,
                'user_id' => $user->id,
                'field_name' => $fieldName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    protected function suspendAccountsWithExpiredFiles(): int
    {
        try {
            $notificationsToSuspend = FileExpirationNotification::needsSuspension()->get();
            $suspendedCount = 0;

            foreach ($notificationsToSuspend as $notification) {
                $user = $notification->user;

                if ($user && $user->status === 'active') {
                    // تحديث حالة المستخدم
                    $user->update(['status' => 'blocked']);

                    // تحديث حالة التنبيه
                    $notification->update(['status' => 'account_suspended']);

                    // إرسال إيميل إشعار بتعليق الحساب
                    $this->sendAccountSuspensionNotification($user, $notification);

                    $suspendedCount++;

                    Log::warning('Account suspended due to expired file', [
                        'user_type' => $notification->user_type,
                        'user_id' => $notification->user_id,
                        'field_label' => $notification->field_label,
                        'expiration_date' => $notification->expiration_date->format('Y-m-d')
                    ]);
                }
            }

            if ($suspendedCount > 0) {
                Log::info("Suspended {$suspendedCount} accounts due to expired files");
            }

            return $suspendedCount;
        } catch (Exception $e) {
            Log::error('Error suspending accounts with expired files', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    protected function sendAccountSuspensionNotification($user, FileExpirationNotification $notification)
    {
        try {
            $recipients = $notification->recipients ;
            foreach ($recipients as $email) {
                $this->emailService->sendHighPriority([
                    'to' => $email,
                    'subject' => 'تم تعليق الحساب - عدم تحديث الملف المطلوب',
                    'template' => 'emails.account-suspension-notification',
                    'user_name' => $user->name,
                    'user_type' => $this->getUserTypeInArabic($notification->user_type),
                    'field_label' => $notification->field_label,
                    'expiration_date' => $notification->expiration_date->format('Y-m-d'),
                    'suspension_reason' => 'عدم تحديث الملف المنتهي الصلاحية خلال 3 أيام',
                    'action_url' => $this->getUpdateUrl($notification->user_type),
                    'action_text' => 'تحديث الملف وإعادة تفعيل الحساب'
                ]);
            }
            Log::info('Account suspension notification sent', [
                'user_type' => $notification->user_type,
                'user_id' => $notification->user_id,
                'field_label' => $notification->field_label,
                'recipients_count' => count($recipients)
            ]);
        } catch (Exception $e) {
            Log::error('Error sending account suspension notification', [
                'user_type' => $notification->user_type,
                'user_id' => $notification->user_id,
                'error' => $e->getMessage()
            ]);
            // لا نرمي الخطأ هنا لأن تعليق الحساب تم بنجاح
        }
    }
    protected function notifyAdminOfError(Exception $exception, array $results)
    {
        try {
            $adminEmail = config('app.admin_email', 'osama.samomy@gmail.com');

            $this->emailService->sendHighPriority([
                'to' => $adminEmail,
                'subject' => 'خطأ في نظام فحص انتهاء صلاحية الملفات',
                'template' => 'emails.system-error-notification',
                'error_message' => $exception->getMessage(),
                'error_file' => $exception->getFile(),
                'error_line' => $exception->getLine(),
                'results' => $results,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            Log::error('Failed to notify admin of system error', [
                'original_error' => $exception->getMessage(),
                'notification_error' => $e->getMessage()
            ]);
        }
    }
    protected function getUpdateUrl(string $userType): string
    {
        try {
            switch ($userType) {
                case 'user':
                    return route('active-account-test');
                case 'customer':
                    return route('active-account-test');
                case 'driver':
                    return route('active-account-test');
                default:
                    return route('active-account-test');
            }
        } catch (Exception $e) {
            Log::warning('Error generating update URL', [
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return config('app.url', 'https://safedests.com');
        }
    }
    protected function getUserTypeInArabic(string $userType): string
    {
        $types = [
            'user' => 'مستخدم النظام',
            'customer' => 'عميل',
            'driver' => 'سائق'
        ];
        return $types[$userType] ?? $userType;
    }
    public function getSystemStatistics(?Carbon $date = null): array
    {
        $date = $date ?? today();
        return [
            'date' => $date->format('Y-m-d'),
            'notifications' => FileExpirationNotification::getStatistics($date),
            'active_users' => [
                'users' => User::where('status', 'active')->count(),
                'customers' => Customer::where('status', 'active')->count(),
                'drivers' => Driver::where('status', 'active')->count()
            ],
            'suspended_today' => FileExpirationNotification::whereDate('updated_at', $date)
                ->where('status', 'account_suspended')
                ->count()
        ];
    }
}
