<?php

namespace App\Services;

use App\Models\Settings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MailConfigService
{
    /**
     * Apply database mail settings to runtime configuration
     */
    public static function apply(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $mailSettings = Settings::whereIn('key', [
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ])->pluck('value', 'key');

            if ($mailSettings->isEmpty()) {
                return;
            }

            $mailer = $mailSettings['mail_mailer'] ?? config('mail.default', 'smtp');
            $host = $mailSettings['mail_host'] ?? config('mail.mailers.smtp.host');
            $port = $mailSettings['mail_port'] ?? config('mail.mailers.smtp.port');
            $username = $mailSettings['mail_username'] ?? config('mail.mailers.smtp.username');
            $password = $mailSettings['mail_password'] ?? config('mail.mailers.smtp.password');
            $encryption = $mailSettings['mail_encryption'] ?? config('mail.mailers.smtp.encryption');
            if ($encryption === 'null' || $encryption === 'none' || empty($encryption)) {
                $encryption = null;
            }
            $fromAddress = $mailSettings['mail_from_address'] ?? config('mail.from.address');
            $fromName = $mailSettings['mail_from_name'] ?? config('mail.from.name');

            Config::set('mail.default', $mailer);
            Config::set('mail.mailers.smtp.transport', $mailer);
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.encryption', $encryption);
            Config::set('mail.from.address', $fromAddress);
            Config::set('mail.from.name', $fromName);

            // Purge instances to apply changes immediately
            Mail::purge('smtp');
            Mail::purge($mailer);
        } catch (Throwable $e) {
            // Keep default environment configurations if database is inaccessible
        }
    }

    /**
     * Apply custom parameters directly (used for testing credentials)
     */
    public static function applyCustom(array $params): void
    {
        $mailer = $params['mail_mailer'] ?? config('mail.default', 'smtp');
        $host = $params['mail_host'] ?? config('mail.mailers.smtp.host');
        $port = $params['mail_port'] ?? config('mail.mailers.smtp.port');
        $username = $params['mail_username'] ?? config('mail.mailers.smtp.username');
        $password = $params['mail_password'] ?? config('mail.mailers.smtp.password');
        $encryption = $params['mail_encryption'] ?? config('mail.mailers.smtp.encryption');
        if ($encryption === 'null' || $encryption === 'none' || empty($encryption)) {
            $encryption = null;
        }
        $fromAddress = $params['mail_from_address'] ?? config('mail.from.address');
        $fromName = $params['mail_from_name'] ?? config('mail.from.name');

        Config::set('mail.default', $mailer);
        Config::set('mail.mailers.smtp.transport', $mailer);
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.encryption', $encryption);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        Mail::purge('smtp');
        Mail::purge($mailer);
    }
}
