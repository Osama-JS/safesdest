<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RecaptchaMiddleware
{
    /**
     * Handle an incoming request for mobile APIs with reCAPTCHA v3
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }

        // Get reCAPTCHA token from request
        $recaptchaToken = $request->input('recaptcha_token');
        
        if (!$recaptchaToken) {
            return response()->json([
                'success' => false,
                'message' => 'reCAPTCHA token is required',
                'error_code' => 'RECAPTCHA_REQUIRED'
            ], 422);
        }

        // Verify reCAPTCHA token with Google
        $secretKey = config('services.recaptcha.secret_key');
        
        if (!$secretKey) {
            Log::error('reCAPTCHA secret key not configured');
            return response()->json([
                'success' => false,
                'message' => 'reCAPTCHA configuration error'
            ], 500);
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $recaptchaToken,
                'remoteip' => $request->ip()
            ]);

            $result = $response->json();

            if (!$result['success']) {
                Log::warning('reCAPTCHA verification failed', [
                    'ip' => $request->ip(),
                    'errors' => $result['error-codes'] ?? []
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'reCAPTCHA verification failed',
                    'error_code' => 'RECAPTCHA_FAILED'
                ], 422);
            }

            // Check score for reCAPTCHA v3 (optional)
            $minScore = config('services.recaptcha.min_score', 0.5);
            if (isset($result['score']) && $result['score'] < $minScore) {
                Log::warning('reCAPTCHA score too low', [
                    'ip' => $request->ip(),
                    'score' => $result['score'],
                    'min_score' => $minScore
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Security verification failed',
                    'error_code' => 'RECAPTCHA_LOW_SCORE'
                ], 422);
            }

            // Log successful verification
            Log::info('reCAPTCHA verification successful', [
                'ip' => $request->ip(),
                'score' => $result['score'] ?? 'N/A'
            ]);

        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Security verification error'
            ], 500);
        }

        return $next($request);
    }
}
