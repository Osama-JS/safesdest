<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EmailNotificationService;

class EmailNotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EmailNotificationService::class, function ($app) {
            return new EmailNotificationService();
        });

        $this->app->alias(EmailNotificationService::class, 'email.notification');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ProcessPendingNotifications::class,
            ]);
        }
    }
}
