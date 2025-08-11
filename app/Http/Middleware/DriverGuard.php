<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Driver;

class DriverGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if the authenticated user is a Driver
        if (!($request->user() instanceof Driver)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Driver authentication required.'
            ], 403);
        }

        // Check if driver is active
        $driver = $request->user();
        if ($driver->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Driver account is not active'
            ], 403);
        }

        // Update last activity
        $driver->update(['last_activity_at' => now()]);

        return $next($request);
    }
}
