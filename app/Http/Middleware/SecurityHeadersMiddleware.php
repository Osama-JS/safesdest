<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach enterprise-grade security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Prevent Clickjacking (X-Frame-Options)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 2. Prevent MIME-Type Sniffing (X-Content-Type-Options)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 3. Legacy XSS Protection Header
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 4. Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Permissions Policy (Restrict unneeded browser hardware capabilities)
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=()');

        // 6. Strict Transport Security (HSTS) on HTTPS connections
        if ($request->isSecure() || $request->header('X-Forwarded-Proto') === 'https') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // 7. Strip sensitive server identification headers
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
