<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify the webhook via GET request (used by Meta to verify webhook url)
     */
    public function verify(Request $request)
    {
        $verifyToken = env('WHATSAPP_VERIFY_TOKEN');

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhook events via POST request
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        
        Log::info('WhatsApp Webhook Received:', $data);

        // Process incoming messages or status updates here
        // if (isset($data['entry'][0]['changes'][0]['value']['messages'])) { ... }

        return response('EVENT_RECEIVED', 200);
    }
}
