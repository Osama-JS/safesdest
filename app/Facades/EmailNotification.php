<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool send(array $emailData, array $attachments = [], string|null $delay = null)
 * @method static array sendBulk(array $recipients, array $emailData, array $attachments = [], string|null $delay = null)
 * @method static bool sendWithTemplate(string $template, string $to, string $subject, array $data = [], array $attachments = [], array $options = [])
 * @method static bool sendHighPriority(array $emailData, array $attachments = [])
 * @method static bool sendLowPriority(array $emailData, array $attachments = [], string $delay = '5 minutes')
 * @method static array getAvailableTemplates()
 */
class EmailNotification extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'email.notification';
    }
}
