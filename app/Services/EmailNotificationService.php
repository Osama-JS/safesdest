<?php

namespace App\Services;

use App\Jobs\SendEmailNotificationJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EmailNotificationService
{
    /**
     * Send email notification
     *
     * @param array $emailData
     * @param array $attachments
     * @param string|null $delay
     * @return bool
     */
    public function send(array $emailData, array $attachments = [], $delay = null): bool
    {
        try {
            // Validate email data
            $validator = $this->validateEmailData($emailData);

            if ($validator->fails()) {
                Log::error('Email notification validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'data' => $emailData
                ]);
                return false;
            }

            // Prepare the job
            $job = new SendEmailNotificationJob($emailData, $attachments);

            // Dispatch with delay if specified
            if ($delay) {
                $job->delay($this->parseDelay($delay));
            }

            dispatch($job);

            Log::info('Email notification job dispatched', [
                'to' => $emailData['to'],
                'subject' => $emailData['subject'],
                'delay' => $delay
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch email notification job', [
                'error' => $e->getMessage(),
                'data' => $emailData
            ]);
            return false;
        }
    }

    /**
     * Send bulk email notifications
     *
     * @param array $recipients
     * @param array $emailData
     * @param array $attachments
     * @param string|null $delay
     * @return array
     */
    public function sendBulk(array $recipients, array $emailData, array $attachments = [], $delay = null): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $individualEmailData = array_merge($emailData, ['to' => $recipient]);
            $results[$recipient] = $this->send($individualEmailData, $attachments, $delay);
        }

        return $results;
    }

    /**
     * Send notification with template
     *
     * @param string $template
     * @param string $to
     * @param string $subject
     * @param array $data
     * @param array $attachments
     * @param array $options
     * @return bool
     */
    public function sendWithTemplate(
        string $template,
        string $to,
        string $subject,
        array $data = [],
        array $attachments = [],
        array $options = []
    ): bool {
        $emailData = array_merge([
            'to' => $to,
            'subject' => $subject,
            'template' => $template,
            'type' => 'template',
            'priority' => 'normal'
        ], $data, $options);

        return $this->send($emailData, $attachments, $options['delay'] ?? null);
    }

    /**
     * Send high priority notification
     *
     * @param array $emailData
     * @param array $attachments
     * @return bool
     */
    public function sendHighPriority(array $emailData, array $attachments = []): bool
    {
        $emailData['priority'] = 'high';
        return $this->send($emailData, $attachments);
    }

    /**
     * Send low priority notification
     *
     * @param array $emailData
     * @param array $attachments
     * @param string $delay
     * @return bool
     */
    public function sendLowPriority(array $emailData, array $attachments = [], string $delay = '5 minutes'): bool
    {
        $emailData['priority'] = 'low';
        return $this->send($emailData, $attachments, $delay);
    }

    /**
     * Validate email data
     *
     * @param array $emailData
     * @return \Illuminate\Validation\Validator
     */
    private function validateEmailData(array $emailData)
    {
        return Validator::make($emailData, [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'content' => 'nullable|string',
            'template' => 'nullable|string',
            'from_email' => 'nullable|email',
            'from_name' => 'nullable|string|max:255',
            'reply_to' => 'nullable|email',
            'cc' => 'nullable|array',
            'cc.*' => 'email',
            'bcc' => 'nullable|array',
            'bcc.*' => 'email',
            'priority' => 'nullable|in:high,normal,low',
            'type' => 'nullable|string|max:50',
            'user_name' => 'nullable|string|max:255',
            'action_url' => 'nullable|url',
            'action_text' => 'nullable|string|max:100'
        ]);
    }

    /**
     * Parse delay string to Carbon instance
     *
     * @param string $delay
     * @return Carbon
     */
    private function parseDelay(string $delay): Carbon
    {
        if (is_numeric($delay)) {
            return now()->addMinutes((int) $delay);
        }

        return now()->add(\DateInterval::createFromDateString($delay));
    }

    /**
     * Get predefined email templates
     *
     * @return array
     */
    public static function getAvailableTemplates(): array
    {
        return [
            'notification' => 'emails.notification',
            'welcome' => 'emails.welcome',
            'task_assigned' => 'emails.task-assigned',
            'task_completed' => 'emails.task-completed',
            'payment_received' => 'emails.payment-received',
            'account_activated' => 'emails.account-activated',
            'password_changed' => 'emails.password-changed',
            'system_maintenance' => 'emails.system-maintenance',
            // قوالب نظام انتهاء صلاحية الملفات
            'file-expiration-notification' => 'emails.file-expiration-notification',
            'account-suspension-notification' => 'emails.account-suspension-notification'
        ];
    }
}
