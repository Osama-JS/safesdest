<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Symfony\Component\HttpFoundation\Response;

class ApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // قائمة بالمسارات والقيود الخاصة بها
        $rateLimits = [
            'login' => 5,
            'location/update' => 60,
            'api/some-endpoint' => 30,
            'api/another' => 10,
        ];

        $path = trim($request->path(), '/'); // مثال: "login"

        foreach ($rateLimits as $route => $limit) {
            if ($path === $route) {
                $key = $route . ':' . ($request->user()?->id ?: $request->ip());

                if (RateLimiter::tooManyAttempts($key, $limit)) {
                    abort(429, "Too many requests to [$route]. Please slow down.");
                }

                RateLimiter::hit($key, 60); // النافذة الزمنية: 60 ثانية
                break;
            }
        }

        return $next($request);
    }
}
