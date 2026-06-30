<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $driver = $request->user();

        if ($driver && $driver->is_guest) {
            return response()->json([
                'success' => false,
                'is_guest' => true,
                'message' => 'Please complete your profile first.'
            ], 403);
        }

        return $next($request);
    }
}
