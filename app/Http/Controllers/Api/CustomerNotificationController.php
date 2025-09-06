<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\Notification_Setting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CustomerNotificationController extends Controller
{
    /**
     * Get customer notifications
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $query = Notification::where('user_type', 'customer')
                                ->where('user_id', $customer->id);

            // Apply filters
            if ($request->filled('type')) {
                $types = is_array($request->type) ? $request->type : [$request->type];
                $query->whereIn('type', $types);
            }

            if ($request->filled('is_read')) {
                $query->where('is_read', $request->boolean('is_read'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            $notifications = $query->paginate($perPage);

            $notificationsData = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'is_read' => $notification->is_read,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'time_ago' => $notification->created_at->diffForHumans(),
                ];
            });

            // Get unread count
            $unreadCount = Notification::where('user_type', 'customer')
                                     ->where('user_id', $customer->id)
                                     ->where('is_read', false)
                                     ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notificationsData,
                    'unread_count' => $unreadCount,
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'from' => $notifications->firstItem(),
                        'to' => $notifications->lastItem(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(Request $request)
    {
        try {
            $customer = $request->user();

            $unreadCount = Notification::where('user_type', 'customer')
                                     ->where('user_id', $customer->id)
                                     ->where('is_read', false)
                                     ->count();

            // Get count by type
            $countByType = Notification::where('user_type', 'customer')
                                     ->where('user_id', $customer->id)
                                     ->where('is_read', false)
                                     ->selectRaw('type, COUNT(*) as count')
                                     ->groupBy('type')
                                     ->pluck('count', 'type')
                                     ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_unread' => $unreadCount,
                    'by_type' => $countByType,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $notification = Notification::where('id', $id)
                                      ->where('user_type', 'customer')
                                      ->where('user_id', $customer->id)
                                      ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            if (!$notification->is_read) {
                $notification->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $customer = $request->user();

            $updatedCount = Notification::where('user_type', 'customer')
                                      ->where('user_id', $customer->id)
                                      ->where('is_read', false)
                                      ->update([
                                          'is_read' => true,
                                          'read_at' => now(),
                                      ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'data' => [
                    'updated_count' => $updatedCount
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function delete(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $notification = Notification::where('id', $id)
                                      ->where('user_type', 'customer')
                                      ->where('user_id', $customer->id)
                                      ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all notifications
     */
    public function clearAll(Request $request)
    {
        try {
            $customer = $request->user();

            $deletedCount = Notification::where('user_type', 'customer')
                                      ->where('user_id', $customer->id)
                                      ->delete();

            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared',
                'data' => [
                    'deleted_count' => $deletedCount
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification settings
     */
    public function getSettings(Request $request)
    {
        try {
            $customer = $request->user();

            $settings = Notification_Setting::where('user_type', 'customer')
                                           ->where('user_id', $customer->id)
                                           ->first();

            if (!$settings) {
                // Create default settings
                $settings = Notification_Setting::create([
                    'user_type' => 'customer',
                    'user_id' => $customer->id,
                    'push_enabled' => true,
                    'email_enabled' => true,
                    'sms_enabled' => false,
                    'task_notifications' => true,
                    'payment_notifications' => true,
                    'clearance_notifications' => true,
                    'offer_notifications' => true,
                    'system_notifications' => true,
                    'marketing_notifications' => false,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'push_enabled' => $settings->push_enabled,
                    'email_enabled' => $settings->email_enabled,
                    'sms_enabled' => $settings->sms_enabled,
                    'notification_types' => [
                        'task_notifications' => $settings->task_notifications,
                        'payment_notifications' => $settings->payment_notifications,
                        'clearance_notifications' => $settings->clearance_notifications,
                        'offer_notifications' => $settings->offer_notifications,
                        'system_notifications' => $settings->system_notifications,
                        'marketing_notifications' => $settings->marketing_notifications,
                    ],
                    'quiet_hours' => [
                        'enabled' => $settings->quiet_hours_enabled ?? false,
                        'start_time' => $settings->quiet_hours_start ?? '22:00',
                        'end_time' => $settings->quiet_hours_end ?? '08:00',
                    ],
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'push_enabled' => 'boolean',
                'email_enabled' => 'boolean',
                'sms_enabled' => 'boolean',
                'task_notifications' => 'boolean',
                'payment_notifications' => 'boolean',
                'clearance_notifications' => 'boolean',
                'offer_notifications' => 'boolean',
                'system_notifications' => 'boolean',
                'marketing_notifications' => 'boolean',
                'quiet_hours_enabled' => 'boolean',
                'quiet_hours_start' => 'nullable|date_format:H:i',
                'quiet_hours_end' => 'nullable|date_format:H:i',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $settings = Notification_Setting::where('user_type', 'customer')
                                           ->where('user_id', $customer->id)
                                           ->first();

            if (!$settings) {
                $settings = new Notification_Setting([
                    'user_type' => 'customer',
                    'user_id' => $customer->id,
                ]);
            }

            // Update settings
            $settings->fill($request->only([
                'push_enabled',
                'email_enabled',
                'sms_enabled',
                'task_notifications',
                'payment_notifications',
                'clearance_notifications',
                'offer_notifications',
                'system_notifications',
                'marketing_notifications',
                'quiet_hours_enabled',
                'quiet_hours_start',
                'quiet_hours_end',
            ]));

            $settings->save();

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update FCM token
     */
    public function updateFcmToken(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'fcm_token' => 'required|string|max:255',
                'device_type' => 'required|in:android,ios',
                'device_id' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update customer FCM token
            $customer->update([
                'fcm_token' => $request->fcm_token,
                'device_type' => $request->device_type,
                'device_id' => $request->device_id,
                'fcm_updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update FCM token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test notification (for development)
     */
    public function testNotification(Request $request)
    {
        try {
            $customer = $request->user();

            $validator = Validator::make($request->all(), [
                'type' => 'required|in:task,payment,clearance,offer,system',
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create test notification
            $notification = Notification::create([
                'user_type' => 'customer',
                'user_id' => $customer->id,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'data' => [
                    'test' => true,
                    'timestamp' => now()->toISOString(),
                ],
                'is_read' => false,
            ]);

            // Here you would typically send the push notification
            // $this->sendPushNotification($customer, $notification);

            return response()->json([
                'success' => true,
                'message' => 'Test notification created successfully',
                'data' => [
                    'notification_id' => $notification->id,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $customer = $request->user();

            $totalNotifications = Notification::where('user_type', 'customer')
                                            ->where('user_id', $customer->id)
                                            ->count();

            $unreadNotifications = Notification::where('user_type', 'customer')
                                             ->where('user_id', $customer->id)
                                             ->where('is_read', false)
                                             ->count();

            $readNotifications = $totalNotifications - $unreadNotifications;

            // Get notifications by type
            $notificationsByType = Notification::where('user_type', 'customer')
                                             ->where('user_id', $customer->id)
                                             ->selectRaw('type, COUNT(*) as count')
                                             ->groupBy('type')
                                             ->pluck('count', 'type')
                                             ->toArray();

            // Get recent activity (last 7 days)
            $recentActivity = Notification::where('user_type', 'customer')
                                        ->where('user_id', $customer->id)
                                        ->where('created_at', '>=', Carbon::now()->subDays(7))
                                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                        ->groupBy('date')
                                        ->orderBy('date', 'desc')
                                        ->get()
                                        ->map(function ($item) {
                                            return [
                                                'date' => $item->date,
                                                'count' => $item->count,
                                            ];
                                        });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_notifications' => $totalNotifications,
                    'unread_notifications' => $unreadNotifications,
                    'read_notifications' => $readNotifications,
                    'read_percentage' => $totalNotifications > 0 ? round(($readNotifications / $totalNotifications) * 100, 1) : 0,
                    'by_type' => $notificationsByType,
                    'recent_activity' => $recentActivity,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send push notification (helper method)
     */
    private function sendPushNotification($customer, $notification)
    {
        // This would integrate with Firebase Cloud Messaging
        // Implementation depends on your FCM setup

        if (!$customer->fcm_token) {
            return false;
        }

        // Check notification settings
        $settings = Notification_Setting::where('user_type', 'customer')
                                       ->where('user_id', $customer->id)
                                       ->first();

        if (!$settings || !$settings->push_enabled) {
            return false;
        }

        // Check if notification type is enabled
        $typeField = $notification->type . '_notifications';
        if (isset($settings->$typeField) && !$settings->$typeField) {
            return false;
        }

        // Check quiet hours
        if ($settings->quiet_hours_enabled) {
            $now = Carbon::now()->format('H:i');
            $start = $settings->quiet_hours_start ?? '22:00';
            $end = $settings->quiet_hours_end ?? '08:00';

            if ($start > $end) {
                // Quiet hours span midnight
                if ($now >= $start || $now <= $end) {
                    return false;
                }
            } else {
                // Normal quiet hours
                if ($now >= $start && $now <= $end) {
                    return false;
                }
            }
        }

        // Here you would send the actual push notification
        // using Firebase Cloud Messaging or your preferred service

        return true;
    }
}
