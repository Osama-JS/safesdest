<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Notification Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the email notification
    | system. You can customize various aspects of how notifications are
    | processed and sent.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'default' => [
        'priority' => 'normal',
        'template' => 'emails.notification',
        'retry_attempts' => 3,
        'timeout' => 120,
        'backoff' => [30, 60, 120], // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('NOTIFICATION_QUEUE_CONNECTION', 'database'),
        'high_priority' => env('NOTIFICATION_HIGH_QUEUE', 'high'),
        'normal_priority' => env('NOTIFICATION_NORMAL_QUEUE', 'default'),
        'low_priority' => env('NOTIFICATION_LOW_QUEUE', 'low'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => env('NOTIFICATION_RATE_LIMITING', true),
        'max_per_minute' => env('NOTIFICATION_MAX_PER_MINUTE', 60),
        'max_per_hour' => env('NOTIFICATION_MAX_PER_HOUR', 1000),
        'max_per_day' => env('NOTIFICATION_MAX_PER_DAY', 10000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('NOTIFICATION_LOGGING', true),
        'log_successful' => env('NOTIFICATION_LOG_SUCCESS', true),
        'log_failed' => env('NOTIFICATION_LOG_FAILED', true),
        'cleanup_after_days' => env('NOTIFICATION_CLEANUP_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'notification' => 'emails.notification',
        'welcome' => 'emails.welcome',
        'task_assigned' => 'emails.task-assigned',
        'task_completed' => 'emails.task-completed',
        'payment_received' => 'emails.payment-received',
        'account_activated' => 'emails.account-activated',
        'password_changed' => 'emails.password-changed',
        'system_maintenance' => 'emails.system-maintenance',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachment Settings
    |--------------------------------------------------------------------------
    */
    'attachments' => [
        'max_size' => env('NOTIFICATION_MAX_ATTACHMENT_SIZE', 10485760), // 10MB
        'allowed_types' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif'
        ],
        'storage_disk' => env('NOTIFICATION_ATTACHMENT_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('NOTIFICATION_MONITORING', true),
        'alert_on_failure_rate' => env('NOTIFICATION_FAILURE_RATE_ALERT', 10), // percentage
        'alert_on_queue_size' => env('NOTIFICATION_QUEUE_SIZE_ALERT', 1000),
        'alert_recipients' => explode(',', env('NOTIFICATION_ALERT_RECIPIENTS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    */
    'cleanup' => [
        'auto_cleanup' => env('NOTIFICATION_AUTO_CLEANUP', true),
        'keep_successful_days' => env('NOTIFICATION_KEEP_SUCCESS_DAYS', 7),
        'keep_failed_days' => env('NOTIFICATION_KEEP_FAILED_DAYS', 30),
        'batch_size' => env('NOTIFICATION_CLEANUP_BATCH_SIZE', 1000),
    ],

];
