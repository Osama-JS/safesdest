<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Task_Offire;
use App\Models\Driver;
use App\Models\Customer;
use App\Models\Teams;
use App\Observers\TaskObserver;
use App\Observers\TaskOfferObserver;
use App\Observers\DriverObserver;
use App\Observers\CustomerObserver;
use App\Observers\TeamObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\Interfaces\WhatsAppServiceInterface::class, function ($app) {
            $provider = env('WHATSAPP_PROVIDER', 'green');
            
            if ($provider === 'cloud') {
                return new \App\Services\CloudWhatsAppService();
            }
            
            return new \App\Services\GreenApiWhatsAppService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define rate limiters for API endpoints
        $this->configureRateLimiters();

        // Register Observers
        Task::observe(TaskObserver::class);
        Task_Offire::observe(TaskOfferObserver::class);
        Driver::observe(DriverObserver::class);
        Customer::observe(CustomerObserver::class);
        Teams::observe(TeamObserver::class);

        // كود Vite كما هو
        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                  'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src)
                    ? 'template-customizer-core-css'
                    : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src)
                      ? 'template-customizer-theme-css'
                      : ''),
                ];
            }
            return [];
        });

        // قراءة ترجمة JSON بدل ملفات PHP
        $locale = App::currentLocale();

        $langFile = base_path("lang/{$locale}.json");
        $translations = [];

        if (File::exists($langFile)) {
            $translations = json_decode(File::get($langFile), true);
        }

        View::share('jsTranslations', json_encode($translations));
    }

    /**
     * Configure rate limiters for the application.
     */
    protected function configureRateLimiters(): void
    {
        // Location updates rate limiter - 60 requests per minute per driver
        RateLimiter::for('location', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // General API rate limiter - 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login rate limiter - 5 attempts per minute
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') . '|' . $request->ip());
        });
    }
}
