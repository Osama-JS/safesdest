<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Cache\RateLimiting\Limit;

class GlobalRateLimit
{
  public function handle(Request $request, Closure $next): Response
  {
    // Exclude high-frequency live dashboard polling endpoints from rate limiting
    if (
        $request->is('admin/settings/backup/progress/*') ||
        $request->is('admin/system-notifications/*') ||
        $request->is('admin/notifications/*') ||
        $request->is('admin/whatsapp-chat/*')
    ) {
        return $next($request);
    }

    $key = $request->ip();
    $maxAttempts = auth()->check() ? 300 : 60;

    RateLimiter::for('global', function () use ($maxAttempts) {
      return Limit::perMinute($maxAttempts)->by(request()->ip());
    });

    if (RateLimiter::tooManyAttempts('global|' . $key, $maxAttempts)) {
      abort(429, 'Too many requests, please slow down.');
    }

    RateLimiter::hit('global|' . $key);

    return $next($request);
  }
}
