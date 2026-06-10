<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Schedule\DriverScheduler;
use App\Schedule\CheckDriversOnline;
use App\Schedule\FileExpirationScheduler;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
      web: __DIR__ . '/../routes/web.php',
      api: __DIR__ . '/../routes/api.php',
      commands: __DIR__ . '/../routes/console.php',
      health: '/up',
  )
  ->withSchedule(function (Schedule $schedule) {
      (new DriverScheduler())($schedule);
      (new CheckDriversOnline())($schedule);
      (new FileExpirationScheduler())($schedule);
  })
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->validateCsrfTokens(except: [
        'api/hyperpay/webhook/payout',
        'api/signit/webhook',
      ]);
      $middleware->encryptCookies(except: [
        'direction',
      ]);
      $middleware->web(LocaleMiddleware::class);
      $middleware->alias([
        'guard.strict' => \App\Http\Middleware\EnsureUserIsAuthenticatedWithCorrectGuard::class,
        'permission' => Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'rate.limit' => \App\Http\Middleware\GlobalRateLimit::class,
        'driver.guard' => \App\Http\Middleware\DriverGuard::class,
        'api.route' => \App\Http\Middleware\ApiMiddleware::class,
        'recaptcha' => \App\Http\Middleware\RecaptchaMiddleware::class,
        'investor'       => \App\Http\Middleware\InvestorMiddleware::class,
        'block.investor' => \App\Http\Middleware\BlockInvestorMiddleware::class,
      ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
      //
  })->create();
