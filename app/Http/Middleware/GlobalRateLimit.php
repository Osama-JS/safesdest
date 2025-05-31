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
    $key = $request->ip(); // يمكن تغييرها حسب نوع التحديد

    RateLimiter::for('global', function () {
      return Limit::perMinute(60)->by(request()->ip());
    });

    if (RateLimiter::tooManyAttempts('global|' . $key, 60)) {
      abort(429, 'Too many requests, please slow down.');
    }

    RateLimiter::hit('global|' . $key);

    return $next($request);
  }
}
