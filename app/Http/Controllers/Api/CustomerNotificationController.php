<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification_Customers;
use Illuminate\Http\Request;
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

            $query = Notification_Customers::where('customer_id', $customer->id);


            if ($request->filled('is_read')) {
                $query->where('is_read', $request->boolean('is_read'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            $notifications = $query->paginate($perPage);

            $notificationsData = $notifications->map(function ($noti) {
                return [
                    'id' => $noti->id,
                    'title' => $noti->notification->title,
                    'message' => $noti->notification->message,
                    'is_read' => $noti->status,
                    'created_at' => $noti->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => 'Notifications retrieved successfully',
                'data' => [
                    'notifications' => $notificationsData,
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
                'status' => 500,
                'message' => 'Failed to get notifications',
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request)
    {
        try {
            $customer = $request->user();

            $notification = Notification_Customers::where('id', $request->id)
                                      ->where('customer_id', $customer->id)
                                      ->first();

            if (!$notification) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Notification not found'
                ]);
            }

            if (!$notification->is_read) {
                $notification->update([
                    'status' => true,
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Notification marked as read'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $customer = $request->user();

            $updatedCount = Notification_Customers::where('customer_id', $customer->id)
                                      ->where('status', false)
                                      ->update([
                                          'status' => true,
                                      ]);

            return response()->json([
                'status' => 200,
                'message' => 'All notifications marked as read',
                'updated_count' => $updatedCount
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage()
            ]);
        }
    }

}
