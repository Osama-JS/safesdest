<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Notification_Drivers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DriverNotificationTestController extends Controller
{
    /**
     * Create test notifications for a driver
     */
    public function createTestNotifications(Request $request)
    {
        try {
            $driver = $request->user();

            // Create sample notifications
            $notifications = [
                [
                    'title' => 'مهمة جديدة متاحة',
                    'message' => 'لديك مهمة جديدة من الرياض إلى جدة. اضغط لعرض التفاصيل.',
                    'type' => 'new_task',
                    'group' => 'drivers'
                ],
                [
                    'title' => 'تم قبول المهمة',
                    'message' => 'تم قبول مهمتك رقم #12345 بنجاح. ابدأ التوجه إلى نقطة الاستلام.',
                    'type' => 'task_accepted',
                    'group' => 'drivers'
                ],
                [
                    'title' => 'دفعة مالية جديدة',
                    'message' => 'تم إضافة 250 ريال إلى محفظتك من المهمة #12344.',
                    'type' => 'payment',
                    'group' => 'drivers'
                ],
                [
                    'title' => 'تحديث النظام',
                    'message' => 'يتوفر تحديث جديد للتطبيق. يرجى التحديث للحصول على أحدث المميزات.',
                    'type' => 'system_announcement',
                    'group' => 'drivers'
                ],
                [
                    'title' => 'عرض خاص',
                    'message' => 'احصل على عمولة إضافية 10% على جميع المهام هذا الأسبوع!',
                    'type' => 'marketing',
                    'group' => 'drivers'
                ],
                [
                    'title' => 'تم إكمال المهمة',
                    'message' => 'تم إكمال المهمة #12343 بنجاح. شكراً لك على الخدمة الممتازة.',
                    'type' => 'task_completed',
                    'group' => 'drivers'
                ]
            ];

            $createdNotifications = [];

            DB::beginTransaction();

            foreach ($notifications as $index => $notificationData) {
                // Create notification
                $notification = Notification::create($notificationData);

                // Create driver notification relationship
                $driverNotification = Notification_Drivers::create([
                    'notification_id' => $notification->id,
                    'driver_id' => $driver->id,
                    'status' => $index < 3 ? false : true, // First 3 are unread
                ]);

                $createdNotifications[] = [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'is_read' => $driverNotification->status,
                    'created_at' => $notification->created_at
                ];
            }

            DB::commit();

            Log::info('Test notifications created for driver', [
                'driver_id' => $driver->id,
                'notifications_count' => count($createdNotifications)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test notifications created successfully',
                'notifications' => $createdNotifications,
                'total_created' => count($createdNotifications)
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Create test notifications error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create test notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all notifications for a driver
     */
    public function clearAllNotifications(Request $request)
    {
        try {
            $driver = $request->user();

            // Delete all driver notification relationships
            $deletedCount = DB::table('notifications_drivers')
                ->where('driver_id', $driver->id)
                ->delete();

            Log::info('All notifications cleared for driver', [
                'driver_id' => $driver->id,
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared successfully',
                'deleted_count' => $deletedCount
            ], 200);

        } catch (\Exception $e) {
            Log::error('Clear all notifications error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics for a driver
     */
    public function getNotificationStats(Request $request)
    {
        Log::alert('Get notification stats attempt 1', [
            'driver_id' => $request->user()->id ?? 'unknown'
        ]);
        try {
            $driver = $request->user();

            $stats = DB::table('notifications_drivers')
                ->join('notifications', 'notifications_drivers.notification_id', '=', 'notifications.id')
                ->where('notifications_drivers.driver_id', $driver->id)
                ->selectRaw('
                    COUNT(*) as total_notifications,
                    SUM(CASE WHEN notifications_drivers.status = 0 THEN 1 ELSE 0 END) as unread_count,
                    SUM(CASE WHEN notifications_drivers.status = 1 THEN 1 ELSE 0 END) as read_count,
                    COUNT(CASE WHEN notifications.type = "new_task" THEN 1 END) as new_task_count,
                    COUNT(CASE WHEN notifications.type = "payment" THEN 1 END) as payment_count,
                    COUNT(CASE WHEN notifications.type = "system_announcement" THEN 1 END) as system_count
                ')
                ->first();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_notifications' => $stats->total_notifications ?? 0,
                    'unread_count' => $stats->unread_count ?? 0,
                    'read_count' => $stats->read_count ?? 0,
                    'new_task_count' => $stats->new_task_count ?? 0,
                    'payment_count' => $stats->payment_count ?? 0,
                    'system_count' => $stats->system_count ?? 0,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get notification stats error', [
                'error' => $e->getMessage(),
                'driver_id' => $request->user()->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification stats: ' . $e->getMessage()
            ], 500);
        }
    }
}
