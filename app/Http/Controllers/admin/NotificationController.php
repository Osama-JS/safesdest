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
            $notif = $notificationUser->notification;
            return [
                'id'              => $notificationUser->id,
                'notification_id' => $notificationUser->notification_id,
                'title'           => $notif?->title ?? 'إشعار جديد',
                'message'         => $notif?->message ?? '',
                'action_url'      => $notif?->action_url ?: null,
                'icon'            => $notif?->icon ?: 'ti-bell text-primary',
                'event_key'       => $notif?->event_key ?: 'general',
                'is_read'         => $notificationUser->status,
                'created_at'      => $notificationUser->created_at->diffForHumans(),
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
     * Get unshown notifications for real-time modal popup and audio chime.
     * Automatically marks them as shown.
     */
    public function latestUnshown()
    {
        $user = Auth::user();

        $unshown = Notification_Users::where('user_id', $user->id)
            ->where('status', false)
            ->where('is_shown', false)
            ->with('notification')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($unshown->isEmpty()) {
            return response()->json([
                'success' => true,
                'has_new' => false,
                'data'    => [],
            ]);
        }

        // Mark as shown so chime and modal trigger only once
        Notification_Users::whereIn('id', $unshown->pluck('id'))
            ->update([
                'is_shown' => true,
                'shown_at' => now(),
            ]);

        $data = $unshown->map(function ($nu) {
            $notif = $nu->notification;
            return [
                'id'              => $nu->id,
                'notification_id' => $nu->notification_id,
                'title'           => $notif?->title ?? 'إشعار جديد',
                'message'         => $notif?->message ?? '',
                'action_url'      => $notif?->action_url ?: null,
                'icon'            => $notif?->icon ?: 'ti-bell text-primary',
                'event_key'       => $notif?->event_key ?: 'general',
                'created_at'      => $nu->created_at?->diffForHumans() ?? 'الآن',
            ];
        });

        return response()->json([
            'success' => true,
            'has_new' => true,
            'data'    => $data,
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

    /**
     * Display all notifications page for the current user.
     */
    public function allNotificationsView(Request $request)
    {
        $user = Auth::user();

        $query = Notification_Users::where('user_id', $user->id)
            ->with('notification');

        // Filter: Status (all, unread, read)
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->where('status', false);
            } elseif ($request->status === 'read') {
                $query->where('status', true);
            }
        }

        // Filter: Category
        if ($request->filled('category')) {
            $category = $request->category;
            $query->whereHas('notification', function ($q) use ($category) {
                if ($category === 'financial') {
                    $q->whereIn('event_key', ['driver_withdrawal_requested', 'payout_approval_required', 'payout_status_updated']);
                } elseif ($category === 'tasks') {
                    $q->whereIn('event_key', ['task_created', 'task_cancellation_requested', 'task_status_changed', 'task_offer_created', 'task_offer_accepted']);
                } elseif ($category === 'users') {
                    $q->whereIn('event_key', ['customer_registered', 'driver_registered', 'team_created']);
                } elseif ($category === 'system') {
                    $q->whereIn('event_key', ['file_expired']);
                }
            });
        }

        // Search: Keyword
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('notification', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Statistics
        $totalCount = Notification_Users::where('user_id', $user->id)->count();
        $unreadCount = Notification_Users::where('user_id', $user->id)->where('status', false)->count();
        $todayCount = Notification_Users::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();
        $financialCount = Notification_Users::where('user_id', $user->id)
            ->whereHas('notification', fn($q) => $q->whereIn('event_key', ['driver_withdrawal_requested', 'payout_approval_required', 'payout_status_updated']))
            ->count();

        return view('admin.notifications.index', compact(
            'notifications',
            'totalCount',
            'unreadCount',
            'todayCount',
            'financialCount'
        ));
    }

    /**
     * Toggle read / unread status for a notification.
     */
    public function toggleRead($id)
    {
        $user = Auth::user();

        $notificationUser = Notification_Users::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notificationUser) {
            return response()->json([
                'success' => false,
                'message' => __('الإشعار غير موجود')
            ], 404);
        }

        $notificationUser->status = !$notificationUser->status;
        $notificationUser->save();

        return response()->json([
            'success' => true,
            'is_read' => $notificationUser->status,
            'message' => $notificationUser->status ? __('تم تحديد الإشعار كمقروء') : __('تم تحديد الإشعار كغير مقروء')
        ]);
    }

    /**
     * Delete a single notification for the user.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $notificationUser = Notification_Users::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notificationUser) {
            return response()->json([
                'success' => false,
                'message' => __('الإشعار غير موجود')
            ], 404);
        }

        $notificationUser->delete();

        return response()->json([
            'success' => true,
            'message' => __('تم حذف الإشعار بنجاح')
        ]);
    }

    /**
     * Delete all read notifications for the current user.
     */
    public function deleteAllRead()
    {
        $user = Auth::user();

        Notification_Users::where('user_id', $user->id)
            ->where('status', true)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => __('تم حذف جميع الإشعارات المقروءة بنجاح')
        ]);
    }
}
