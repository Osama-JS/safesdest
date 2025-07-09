<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EmailNotificationRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('email_notifications.rate_limiting.enabled', true)) {
            return $next($request);
        }

        $email = $request->input('to') ?? $request->input('email');
        
        if (!$email) {
            return $next($request);
        }

        $key = 'email_notification_rate_limit:' . $email;
        
        // Check per minute limit
        $perMinuteLimit = config('email_notifications.rate_limiting.max_per_minute', 60);
        $perMinuteKey = $key . ':minute:' . now()->format('Y-m-d-H-i');
        
        if (Cache::get($perMinuteKey, 0) >= $perMinuteLimit) {
            return response()->json([
                'error' => 'Rate limit exceeded. Too many notifications per minute.',
                'retry_after' => 60
            ], 429);
        }

        // Check per hour limit
        $perHourLimit = config('email_notifications.rate_limiting.max_per_hour', 1000);
        $perHourKey = $key . ':hour:' . now()->format('Y-m-d-H');
        
        if (Cache::get($perHourKey, 0) >= $perHourLimit) {
            return response()->json([
                'error' => 'Rate limit exceeded. Too many notifications per hour.',
                'retry_after' => 3600
            ], 429);
        }

        // Check per day limit
        $perDayLimit = config('email_notifications.rate_limiting.max_per_day', 10000);
        $perDayKey = $key . ':day:' . now()->format('Y-m-d');
        
        if (Cache::get($perDayKey, 0) >= $perDayLimit) {
            return response()->json([
                'error' => 'Rate limit exceeded. Too many notifications per day.',
                'retry_after' => 86400
            ], 429);
        }

        // Increment counters
        Cache::increment($perMinuteKey, 1);
        Cache::put($perMinuteKey, Cache::get($perMinuteKey), 60);

        Cache::increment($perHourKey, 1);
        Cache::put($perHourKey, Cache::get($perHourKey), 3600);

        Cache::increment($perDayKey, 1);
        Cache::put($perDayKey, Cache::get($perDayKey), 86400);

        return $next($request);
    }
}
