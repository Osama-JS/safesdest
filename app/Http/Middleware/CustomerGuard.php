<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if the authenticated user is a customer
        if (!($request->user() instanceof Customer)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Customer access required.'
            ], 403);
        }

        // Check if customer account is active
        $customer = $request->user();
        if ($customer->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Customer account is not active'
            ], 403);
        }

        // Check if email is verified for sensitive operations
        $sensitiveRoutes = [
            'api.customer.tasks.store',
            'api.customer.wallet.deposit',
            'api.customer.wallet.withdraw',
            'api.customer.wallet.transfer',
            'api.customer.payments.initiate',
            'api.customer.customs-clearances.store',
        ];

        if (in_array($request->route()->getName(), $sensitiveRoutes) && !$customer->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification required for this operation',
                'requires_verification' => true
            ], 403);
        }

        return $next($request);
    }
}
