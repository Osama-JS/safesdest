<?php

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

use App\Http\Middleware\EnsureCorrectGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Schedule\DriverScheduler;
use App\Schedule\CheckDriversOnline;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Spatie\Permission\Middlewares\RoleMiddleware;
use Illuminate\Console\Scheduling\Schedule;




return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
    (new DriverScheduler())($schedule);
    (new CheckDriversOnline())($schedule);
  })
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(LocaleMiddleware::class);
    $middleware->alias([
      'guard.strict' => \App\Http\Middleware\EnsureUserIsAuthenticatedWithCorrectGuard::class,
      'permission' => Spatie\Permission\Middleware\PermissionMiddleware::class,
      'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
      'rate.limit' => \App\Http\Middleware\GlobalRateLimit::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    //
  })->create();
