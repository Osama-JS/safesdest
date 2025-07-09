<?php

namespace App\Helpers;

use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\Storage;

class NotificationHelper
{
    protected $emailService;

    public function __construct()
    {
        $this->emailService = new EmailNotificationService();
    }

    /**
     * Send task assignment notification
     */
    public static function sendTaskAssigned($driver, $task)
    {
        $service = new EmailNotificationService();
        
        return $service->sendWithTemplate(
            'emails.task-assigned',
            $driver->email,
            'تم تعيين مهمة جديدة لك',
            [
                'user_name' => $driver->name,
                'action_url' => route('driver.tasks.show', $task->id),
                'action_text' => 'عرض تفاصيل المهمة',
                'additional_data' => [
                    'رقم المهمة' => $task->id,
                    'نوع المهمة' => $task->type ?? 'توصيل',
                    'عنوان الاستلام' => $task->pickup->address ?? 'غير محدد',
                    'عنوان التسليم' => $task->delivery->address ?? 'غير محدد',
                    'السعر' => $task->price . ' ريال',
                    'تاريخ البدء' => $task->start_before ? date('Y-m-d H:i', strtotime($task->start_before)) : 'غير محدد'
                ]
            ]
        );
    }

    /**
     * Send task completion notification
     */
    public static function sendTaskCompleted($customer, $task)
    {
        $service = new EmailNotificationService();
        
        return $service->sendWithTemplate(
            'emails.notification',
            $customer->email,
            'تم إكمال مهمتك بنجاح',
            [
                'user_name' => $customer->name,
                'content' => 'تم إكمال مهمة التوصيل الخاصة بك بنجاح. شكراً لاستخدام خدماتنا.',
                'action_url' => route('customer.tasks.show', $task->id),
                'action_text' => 'عرض تفاصيل المهمة',
                'additional_data' => [
                    'رقم المهمة' => $task->id,
                    'السائق' => $task->driver->name ?? 'غير محدد',
                    'تاريخ الإكمال' => now()->format('Y-m-d H:i'),
                    'التقييم' => 'يمكنك تقييم الخدمة الآن'
                ]
            ]
        );
    }

    /**
     * Send payment notification
     */
    public static function sendPaymentReceived($user, $amount, $transactionId, $description = null)
    {
        $service = new EmailNotificationService();
        
        return $service->sendWithTemplate(
            'emails.payment-received',
            $user->email,
            'تم استلام دفعة جديدة',
            [
                'user_name' => $user->name,
                'action_url' => route('wallet.index'),
                'action_text' => 'عرض المحفظة',
                'additional_data' => [
                    'المبلغ' => $amount . ' ريال',
                    'رقم المعاملة' => $transactionId,
                    'التاريخ' => now()->format('Y-m-d H:i'),
                    'الوصف' => $description ?? 'دفعة جديدة',
                    'الرصيد الحالي' => $user->wallet_balance . ' ريال'
                ]
            ]
        );
    }

    /**
     * Send welcome notification
     */
    public static function sendWelcome($user, $userType = 'user')
    {
        $service = new EmailNotificationService();
        
        $dashboardRoute = $userType === 'driver' ? 'driver.dashboard' : 'customer.dashboard';
        
        return $service->sendWithTemplate(
            'emails.notification',
            $user->email,
            'مرحباً بك في ' . config('app.name'),
            [
                'user_name' => $user->name,
                'content' => 'مرحباً بك في منصة ' . config('app.name') . '. نحن سعداء لانضمامك إلينا ونتطلع لخدمتك.',
                'action_url' => route($dashboardRoute),
                'action_text' => 'الذهاب إلى لوحة التحكم',
                'additional_data' => [
                    'نوع الحساب' => $userType === 'driver' ? 'سائق' : 'عميل',
                    'تاريخ التسجيل' => $user->created_at->format('Y-m-d'),
                    'حالة الحساب' => $user->status === 'active' ? 'نشط' : 'في انتظار التفعيل'
                ]
            ]
        );
    }

    /**
     * Send system maintenance notification
     */
    public static function sendMaintenanceNotification($users, $startTime, $endTime, $description)
    {
        $service = new EmailNotificationService();
        
        $recipients = is_array($users) ? $users : $users->pluck('email')->toArray();
        
        return $service->sendBulk(
            $recipients,
            [
                'subject' => 'إشعار صيانة النظام',
                'template' => 'emails.notification',
                'content' => $description,
                'priority' => 'high',
                'additional_data' => [
                    'وقت البدء' => $startTime,
                    'وقت الانتهاء' => $endTime,
                    'المدة المتوقعة' => 'حوالي ' . (strtotime($endTime) - strtotime($startTime)) / 3600 . ' ساعات'
                ]
            ]
        );
    }

    /**
     * Send notification with PDF attachment
     */
    public static function sendWithPDF($email, $subject, $content, $pdfPath, $pdfName = 'document.pdf')
    {
        $service = new EmailNotificationService();
        
        $attachments = [];
        if (Storage::exists($pdfPath)) {
            $attachments[] = [
                'path' => $pdfPath,
                'name' => $pdfName,
                'mime' => 'application/pdf'
            ];
        }
        
        return $service->send([
            'to' => $email,
            'subject' => $subject,
            'content' => $content,
            'template' => 'emails.notification'
        ], $attachments);
    }

    /**
     * Send bulk notification with different content for each recipient
     */
    public static function sendPersonalizedBulk($recipients)
    {
        $service = new EmailNotificationService();
        $results = [];
        
        foreach ($recipients as $recipient) {
            $results[$recipient['email']] = $service->send($recipient);
        }
        
        return $results;
    }
}
