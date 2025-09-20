<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DriverNotificationController extends Controller
{
    /**
     * Get driver notifications
     */
    public function index(Request $request)
    {
        try {
            $driver = $request->user();

            Log::alert('Get driver notifications attempt', [
                'driver_id' => $driver->id,
                'unread_only' => $request->get('unread_only')
            ]);
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'unread_only' => 'nullable|in:true,false'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $perPage = $request->get('per_page', 20);
            $unreadOnly = $request->get('unread_only', false);

            // Get notifications using the custom notifications system
            $query = DB::table('notifications_drivers')
                ->join('notifications', 'notifications_drivers.notification_id', '=', 'notifications.id')
                ->where('notifications_drivers.driver_id', $driver->id)
                ->select([
                    'notifications.id',
                    'notifications.title',
                    'notifications.message as body',
                    'notifications.type',
                    'notifications.group',
                    'notifications_drivers.status as is_read',
                    'notifications.created_at',
                    'notifications.updated_at'
                ]);

            if ($unreadOnly) {
                $query->where('notifications_drivers.status', false);
            }

            $notifications = $query->orderBy('notifications.created_at', 'desc')->paginate($perPage);

            // Format notifications
            $formattedNotifications = $notifications->getCollection()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type ?? 'general',
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'data' => [
                        'type' => $notification->type ?? 'general',
                        'group' => $notification->group
                    ],
                    'read_at' => $notification->is_read ? $notification->updated_at : null,
                    'created_at' => $notification->created_at ? date('c', strtotime($notification->created_at)) : date('c')
                ];
            });

            // Get unread count
            $unreadCount = DB::table('notifications_drivers')
                ->where('driver_id', $driver->id)
                ->where('status', false)
                ->count();

            Log::info('Driver notifications retrieved', [
                'driver_id' => $driver->id,
                'total_notifications' => $notifications->total(),
                'unread_count' => $unreadCount,
                'current_page' => $notifications->currentPage(),
                'Notifications' => $formattedNotifications
            ]);



            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $formattedNotifications,
                    'unread_count' => $unreadCount,
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'from' => $notifications->firstItem(),
                        'to' => $notifications->lastItem()
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get driver notifications error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get notifications'
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $notificationId)
    {
        try {
            $driver = $request->user();

            // Check if notification exists for this driver
            $notificationDriver = DB::table('notifications_drivers')
                ->where('driver_id', $driver->id)
                ->where('notification_id', $notificationId)
                ->first();

            if (!$notificationDriver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            // Mark as read if not already read
            if (!$notificationDriver->status) {
                DB::table('notifications_drivers')
                    ->where('driver_id', $driver->id)
                    ->where('notification_id', $notificationId)
                    ->update([
                        'status' => true,
                        'updated_at' => now()
                    ]);

                Log::info('Notification marked as read', [
                    'driver_id' => $driver->id,
                    'notification_id' => $notificationId
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Mark notification as read error', [
                'error' => $e->getMessage(),
                'notification_id' => $notificationId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $driver = $request->user();

            // Mark all unread notifications as read
            $updatedCount = DB::table('notifications_drivers')
                ->where('driver_id', $driver->id)
                ->where('status', false)
                ->update([
                    'status' => true,
                    'updated_at' => now()
                ]);

            Log::info('All notifications marked as read', [
                'driver_id' => $driver->id,
                'updated_count' => $updatedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'updated_count' => $updatedCount
            ], 200);

        } catch (\Exception $e) {
            Log::error('Mark all notifications as read error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read'
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function delete(Request $request, $notificationId)
    {
        try {
            $driver = $request->user();

            // Check if notification exists for this driver
            $notificationDriver = DB::table('notifications_drivers')
                ->where('driver_id', $driver->id)
                ->where('notification_id', $notificationId)
                ->first();

            if (!$notificationDriver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            // Delete the notification relationship (not the notification itself)
            DB::table('notifications_drivers')
                ->where('driver_id', $driver->id)
                ->where('notification_id', $notificationId)
                ->delete();

            Log::info('Notification deleted for driver', [
                'driver_id' => $driver->id,
                'notification_id' => $notificationId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Delete notification error', [
                'error' => $e->getMessage(),
                'notification_id' => $notificationId,
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification'
            ], 500);
        }
    }

    /**
     * Get notification settings
     */
    public function getSettings(Request $request)
    {
        try {
            $driver = $request->user();

            // You can store notification preferences in driver's additional_data or create a separate table
            $settings = $driver->additional_data['notification_settings'] ?? [
                'new_tasks' => true,
                'task_updates' => true,
                'payment_notifications' => true,
                'system_announcements' => true,
                'marketing' => false
            ];

            return response()->json([
                'success' => true,
                'settings' => $settings
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get notification settings error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification settings'
            ], 500);
        }
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request)
    {
        try {
            $driver = $request->user();

            $validator = Validator::make($request->all(), [
                'new_tasks' => 'nullable|boolean',
                'task_updates' => 'nullable|boolean',
                'payment_notifications' => 'nullable|boolean',
                'system_announcements' => 'nullable|boolean',
                'marketing' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get current additional data
            $additionalData = $driver->additional_data ?? [];

            // Update notification settings
            $additionalData['notification_settings'] = array_merge(
                $additionalData['notification_settings'] ?? [],
                $request->only(['new_tasks', 'task_updates', 'payment_notifications', 'system_announcements', 'marketing'])
            );

            $driver->update(['additional_data' => $additionalData]);

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully',
                'settings' => $additionalData['notification_settings']
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update notification settings error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings'
            ], 500);
        }
    }
}
