<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user();

        if ($customer && $customer->is_guest) {
            return response()->json([
                'status'   => 403,
                'success'  => false,
                'is_guest' => true,
                'message'  => __('Please complete your profile first.'),
            ], 403);
        }

        return $next($request);
    }
}
