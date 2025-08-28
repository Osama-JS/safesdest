<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestFirebaseController extends Controller
{
    protected $firebaseService;
    protected $notificationService;

    public function __construct()
    {
        $this->firebaseService = new FirebaseService();
        $this->notificationService = new NotificationService();
    }

    /**
     * Test Firebase connection
     */
    public function testConnection()
    {
        try {
            // Try to validate a dummy token to test Firebase connection
            $result = $this->firebaseService->validateToken('dummy-token');
            
            return response()->json([
                'success' => true,
                'message' => 'Firebase service is working',
                'firebase_test' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Firebase connection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test notification to a driver
     */
    public function sendTestNotification(Request $request)
    {
        try {
            $request->validate([
                'driver_id' => 'required|exists:drivers,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500'
            ]);

            $driver = Driver::find($request->driver_id);
            
            if (!$driver->fcm_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver does not have FCM token'
                ], 400);
            }

            $result = $this->firebaseService->sendToDevice(
                $driver->fcm_token,
                $request->title,
                $request->body,
                [
                    'type' => 'test',
                    'timestamp' => now()->toISOString()
                ]
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Test notification error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->driver_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test notification to all drivers
     */
    public function sendTestNotificationToAll(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500'
            ]);

            $drivers = Driver::whereNotNull('fcm_token')->get();
            
            if ($drivers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No drivers with FCM tokens found'
                ], 400);
            }

            $result = $this->notificationService->sendNotificationToMultipleDrivers(
                $drivers->toArray(),
                $request->title,
                $request->body,
                [
                    'type' => 'broadcast_test',
                    'timestamp' => now()->toISOString()
                ]
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Broadcast test notification error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send broadcast test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get drivers with FCM tokens
     */
    public function getDriversWithTokens()
    {
        try {
            $drivers = Driver::whereNotNull('fcm_token')
                ->select('id', 'name', 'email', 'fcm_token', 'last_activity_at')
                ->get();

            return response()->json([
                'success' => true,
                'drivers' => $drivers,
                'count' => $drivers->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get drivers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test new task notification
     */
    public function testNewTaskNotification(Request $request)
    {
        try {
            $request->validate([
                'driver_id' => 'required|exists:drivers,id'
            ]);

            $driver = Driver::find($request->driver_id);
            
            // Create a mock task object for testing
            $mockTask = (object) [
                'id' => 999,
                'pickup' => (object) ['address' => 'الرياض - حي النخيل'],
                'delivery' => (object) ['address' => 'الرياض - حي الملز'],
                'price' => 50
            ];

            $result = $this->notificationService->sendNewTaskNotificationToDriver($driver, $mockTask);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send new task notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test payment notification
     */
    public function testPaymentNotification(Request $request)
    {
        try {
            $request->validate([
                'driver_id' => 'required|exists:drivers,id',
                'amount' => 'required|numeric|min:1'
            ]);

            $driver = Driver::find($request->driver_id);
            $amount = $request->amount;

            $result = $this->notificationService->sendPaymentNotificationToDriver($driver, $amount);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send payment notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate FCM token
     */
    public function validateToken(Request $request)
    {
        try {
            $request->validate([
                'fcm_token' => 'required|string'
            ]);

            $result = $this->firebaseService->validateToken($request->fcm_token);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
