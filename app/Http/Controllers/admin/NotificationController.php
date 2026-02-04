<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Notification_Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get user's notifications (paginated)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get notifications for this user
        $notifications = Notification_Users::where('user_id', $user->id)
            ->with('notification')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Transform the data
        $data = $notifications->map(function ($notificationUser) {
            return [
                'id' => $notificationUser->id,
                'notification_id' => $notificationUser->notification_id,
                'title' => $notificationUser->notification->title,
                'message' => $notificationUser->notification->message,
                'is_read' => $notificationUser->status,
                'created_at' => $notificationUser->created_at->diffForHumans(),
                'created_at_full' => $notificationUser->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'total' => $notifications->total(),
        ]);
    }

    /**
     * Get count of unread notifications
     */
    public function unreadCount()
    {
        $user = Auth::user();

        $count = Notification_Users::where('user_id', $user->id)
            ->where('status', false)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead($id)
    {
        $user = Auth::user();

        $notificationUser = Notification_Users::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notificationUser) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notificationUser->update(['status' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all user's notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        Notification_Users::where('user_id', $user->id)
            ->where('status', false)
            ->update(['status' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }
}
