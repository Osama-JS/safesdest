<?php

namespace App\Services;

use App\Models\AdminNotificationSetting;
use App\Models\Notification;
use App\Models\Notification_Users;
use App\Models\User;
use App\Jobs\SendEmailNotificationJob;
use App\Notifications\GeneralPushNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminNotificationDispatcher
{
    /**
     * Dispatch an admin notification across enabled channels.
     *
     * @param string $eventKey
     * @param string $title
     * @param string $message
     * @param string|null $actionUrl
     * @param string|null $icon
     * @param array $extraData
     * @param string|null $priority
     * @return bool
     */
    public static function dispatch(
        string $eventKey,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $icon = null,
        array $extraData = [],
        ?string $priority = null
    ): bool {
        try {
            $setting = AdminNotificationSetting::where('event_key', $eventKey)->first();

            // Default fallback if setting row is missing
            $inAppEnabled   = $setting ? $setting->in_app_enabled : true;
            $emailEnabled   = $setting ? $setting->email_enabled : true;
            $webpushEnabled = $setting ? $setting->webpush_enabled : false;
            $resolvedIcon   = $icon ?: self::getDefaultIcon($eventKey);
            $finalPriority  = $priority ?: ($setting->priority ?? 'normal');

            // 1. Channel: In-App Dashboard (notifications & notifications_users)
            if ($inAppEnabled) {
                self::dispatchInApp(
                    $setting,
                    $eventKey,
                    $title,
                    $message,
                    $actionUrl,
                    $resolvedIcon,
                    $extraData
                );
            }

            // 2. Channel: Email
            if ($emailEnabled) {
                self::dispatchEmail(
                    $setting,
                    $title,
                    $message,
                    $actionUrl,
                    $finalPriority,
                    $extraData
                );
            }

            // 3. Channel: WebPush (Browser Push)
            if ($webpushEnabled) {
                self::dispatchWebPush(
                    $setting,
                    $title,
                    $message,
                    $actionUrl,
                    $eventKey
                );
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("AdminNotificationDispatcher Error [{$eventKey}]: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Dispatch in-app notifications for navbar & live popups.
     */
    protected static function dispatchInApp(
        ?AdminNotificationSetting $setting,
        string $eventKey,
        string $title,
        string $message,
        ?string $actionUrl,
        string $icon,
        array $extraData
    ): void {
        try {
            $recipients = $setting ? $setting->getRecipientUsers() : User::where('status', 'active')
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['Owner', 'Admin']))
                ->get();

            if ($recipients->isEmpty()) {
                // Absolute fallback to first active user or ID 1
                $fallbackUser = User::where('status', 'active')->first();
                if ($fallbackUser) {
                    $recipients = collect([$fallbackUser]);
                }
            }

            DB::transaction(function () use ($recipients, $eventKey, $title, $message, $actionUrl, $icon, $extraData) {
                $notification = Notification::create([
                    'title'      => $title,
                    'message'    => $message,
                    'group'      => 'users',
                    'type'       => 'all',
                    'action_url' => $actionUrl,
                    'icon'       => $icon,
                    'event_key'  => $eventKey,
                    'data'       => $extraData,
                ]);

                foreach ($recipients as $user) {
                    Notification_Users::create([
                        'notification_id' => $notification->id,
                        'user_id'         => $user->id,
                        'status'          => false, // unread
                        'is_shown'        => false, // ready for real-time in-app modal & chime
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error("AdminNotificationDispatcher In-App Failed: " . $e->getMessage());
        }
    }

    /**
     * Dispatch email notification jobs to recipient list.
     */
    protected static function dispatchEmail(
        ?AdminNotificationSetting $setting,
        string $title,
        string $message,
        ?string $actionUrl,
        string $priority,
        array $extraData
    ): void {
        try {
            $emails = $setting ? $setting->getRecipientEmails() : [];

            // Fallback to configured app.admin_email if list is empty
            if (empty($emails)) {
                $fallbackEmail = config('app.admin_email', 'admin@safedests.com');
                if (filter_var($fallbackEmail, FILTER_VALIDATE_EMAIL)) {
                    $emails = [$fallbackEmail];
                }
            }

            foreach ($emails as $email) {
                $emailData = [
                    'to'              => $email,
                    'subject'         => "[Safedest Admin] " . $title,
                    'content'         => $message,
                    'user_name'       => 'مدير المنصة',
                    'template'        => 'emails.notification',
                    'type'            => 'admin_alert',
                    'priority'        => $priority,
                    'action_url'      => $actionUrl ?: url('/admin'),
                    'action_text'     => 'عرض التفاصيل في لوحة التحكم',
                    'additional_data' => $extraData,
                ];

                dispatch(new SendEmailNotificationJob($emailData))->afterCommit();
            }
        } catch (\Throwable $e) {
            Log::error("AdminNotificationDispatcher Email Failed: " . $e->getMessage());
        }
    }

    /**
     * Dispatch WebPush browser notifications.
     */
    protected static function dispatchWebPush(
        ?AdminNotificationSetting $setting,
        string $title,
        string $message,
        ?string $actionUrl,
        string $eventKey
    ): void {
        try {
            $recipients = $setting ? $setting->getRecipientUsers() : collect();

            foreach ($recipients as $user) {
                if (method_exists($user, 'pushSubscriptions') && $user->pushSubscriptions()->exists()) {
                    $user->notify(new GeneralPushNotification([
                        'title' => $title,
                        'body'  => $message,
                        'icon'  => '/images/admin-icon.png',
                        'url'   => $actionUrl ?: '/admin',
                        'type'  => $eventKey,
                    ]));
                }
            }
        } catch (\Throwable $e) {
            Log::error("AdminNotificationDispatcher WebPush Failed: " . $e->getMessage());
        }
    }

    /**
     * Get appropriate icon class for event key.
     */
    public static function getDefaultIcon(string $eventKey): string
    {
        return match ($eventKey) {
            'driver_withdrawal_requested' => 'ti-wallet text-warning',
            'payout_approval_required'    => 'ti-shield-lock text-danger',
            'payout_status_updated'       => 'ti-building-bank text-info',
            'task_created'                => 'ti-package text-primary',
            'task_cancellation_requested' => 'ti-alert-triangle text-danger',
            'task_status_changed'         => 'ti-truck text-info',
            'task_offer_created'          => 'ti-tag text-secondary',
            'task_offer_accepted'         => 'ti-circle-check text-success',
            'customer_registered'         => 'ti-user-plus text-success',
            'driver_registered'           => 'ti-steering-wheel text-primary',
            'team_created'                => 'ti-users text-info',
            'file_expired'                => 'ti-file-alert text-warning',
            'customer_invoice_due'        => 'ti-file-invoice text-warning',
            'customer_invoice_overdue'    => 'ti-file-alert text-danger',
            'whatsapp_message_received'   => 'ti-brand-whatsapp text-success',
            default                       => 'ti-bell text-primary',
        };
    }
}
