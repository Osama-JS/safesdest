<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailNotificationLog;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailNotificationController extends Controller
{
    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Display notification logs
     */
    public function index(Request $request)
    {
        $query = EmailNotificationLog::query();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('to_email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $notifications = $query->orderBy('created_at', 'desc')
                              ->paginate(50);

        // Get statistics
        $stats = [
            'total' => EmailNotificationLog::count(),
            'sent' => EmailNotificationLog::where('status', 'sent')->count(),
            'failed' => EmailNotificationLog::where('status', 'failed')->count(),
            'pending' => EmailNotificationLog::where('status', 'pending')->count(),
            'today' => EmailNotificationLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.notifications.index', compact('notifications', 'stats'));
    }

    /**
     * Show notification details
     */
    public function show($id)
    {
        $notification = EmailNotificationLog::findOrFail($id);
        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Send test notification
     */
    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'template' => 'nullable|string',
            'priority' => 'nullable|in:high,normal,low'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->emailService->send([
            'to' => $request->to,
            'subject' => $request->subject,
            'content' => $request->content,
            'template' => $request->template ?? 'emails.notification',
            'priority' => $request->priority ?? 'normal',
            'type' => 'test',
            'user_name' => 'مدير النظام'
        ]);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'تم إرسال الإشعار التجريبي بنجاح' : 'فشل في إرسال الإشعار التجريبي'
        ]);
    }

    /**
     * Get notification statistics
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', 'week'); // day, week, month, year

        $query = EmailNotificationLog::query();

        switch ($period) {
            case 'day':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $stats = [
            'total' => $query->count(),
            'sent' => $query->where('status', 'sent')->count(),
            'failed' => $query->where('status', 'failed')->count(),
            'pending' => $query->where('status', 'pending')->count(),
        ];

        // Calculate success rate
        $stats['success_rate'] = $stats['total'] > 0 
            ? round(($stats['sent'] / $stats['total']) * 100, 2) 
            : 0;

        // Get hourly distribution for charts
        if ($period === 'day') {
            $hourlyStats = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $hourlyStats[$hour] = EmailNotificationLog::whereDate('created_at', today())
                    ->whereHour('created_at', $hour)
                    ->count();
            }
            $stats['hourly'] = $hourlyStats;
        }

        return response()->json($stats);
    }

    /**
     * Retry failed notification
     */
    public function retry($id)
    {
        $notification = EmailNotificationLog::findOrFail($id);

        if ($notification->status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'يمكن إعادة المحاولة فقط للإشعارات الفاشلة'
            ], 400);
        }

        $emailData = [
            'to' => $notification->to_email,
            'from_email' => $notification->from_email,
            'subject' => $notification->subject,
            'content' => $notification->content,
            'template' => $notification->template,
            'type' => $notification->type,
            'priority' => $notification->priority,
            'additional_data' => $notification->additional_data
        ];

        $result = $this->emailService->send($emailData);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'تم إعادة إرسال الإشعار بنجاح' : 'فشل في إعادة إرسال الإشعار'
        ]);
    }

    /**
     * Delete notification log
     */
    public function destroy($id)
    {
        $notification = EmailNotificationLog::findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف سجل الإشعار بنجاح'
        ]);
    }
}
