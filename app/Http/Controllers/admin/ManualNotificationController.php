<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Models\Driver;
use App\Models\Customer;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ManualNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * إرسال إشعار لسائق واحد
     */
    public function sendToDriver(Request $request)
    {
        try {
            $request->validate([
                'driver_id' => 'required|exists:drivers,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500',
                'type' => 'nullable|string'
            ]);

            $driver = Driver::findOrFail($request->driver_id);

            // التحقق من وجود FCM token
            if (!$driver->fcm_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'السائق لا يملك رمز FCM. تأكد من تسجيل دخوله على التطبيق.'
                ], 400);
            }

            $result = $this->notificationService->send(
                type: 'driver',
                ids: [$request->driver_id],
                title: $request->title,
                body: $request->body,
                notif_type: $request->type ?? 'admin_message'
            );

            // تسجيل النشاط
            Log::info('Manual notification sent to driver', [
                'driver_id' => $request->driver_id,
                'driver_name' => $driver->name,
                'title' => $request->title,
                'sent_by' => auth()->user()->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإشعار بنجاح إلى ' . $driver->name
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending manual notification to driver', [
                'error' => $e->getMessage(),
                'driver_id' => $request->driver_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإشعار: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال إشعار لعدة سائقين
     */
    public function sendToMultipleDrivers(Request $request)
    {
        try {
            $request->validate([
                'driver_ids' => 'required|array|min:1',
                'driver_ids.*' => 'exists:drivers,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500',
                'type' => 'nullable|string'
            ]);

            // الحصول على السائقين الذين لديهم FCM token
            $drivers = Driver::whereIn('id', $request->driver_ids)
                ->whereNotNull('fcm_token')
                ->get();

            if ($drivers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد سائقين بـ FCM token صالح من القائمة المحددة'
                ], 400);
            }

            $result = $this->notificationService->send(
                type: 'driver',
                ids: $drivers->pluck('id')->toArray(),
                title: $request->title,
                body: $request->body,
                notif_type: $request->type ?? 'admin_message'
            );

            // تسجيل النشاط
            Log::info('Manual notification sent to multiple drivers', [
                'driver_count' => $drivers->count(),
                'title' => $request->title,
                'sent_by' => auth()->user()->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإشعارات بنجاح إلى ' . $drivers->count() . ' سائق',
                'count' => $drivers->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending manual notification to multiple drivers', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإشعارات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال إشعار لعميل واحد
     */
    public function sendToCustomer(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500',
                'type' => 'nullable|string'
            ]);

            $customer = Customer::findOrFail($request->customer_id);

            // التحقق من وجود FCM token
            if (!$customer->fcm_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'العميل لا يملك رمز FCM. تأكد من تسجيل دخوله على التطبيق.'
                ], 400);
            }

            $result = $this->notificationService->send(
                type: 'customer',
                ids: [$request->customer_id],
                title: $request->title,
                body: $request->body,
                notif_type: $request->type ?? 'admin_message'
            );

            // تسجيل النشاط
            Log::info('Manual notification sent to customer', [
                'customer_id' => $request->customer_id,
                'customer_name' => $customer->name,
                'title' => $request->title,
                'sent_by' => auth()->user()->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإشعار بنجاح إلى ' . $customer->name
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending manual notification to customer', [
                'error' => $e->getMessage(),
                'customer_id' => $request->customer_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإشعار: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال إشعار للسائق من صفحة المهمة
     */
    public function sendToTaskDriver(Request $request)
    {
        try {
            $request->validate([
                'task_id' => 'required|exists:tasks,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500'
            ]);

            $task = Task::with('driver')->findOrFail($request->task_id);

            if (!$task->driver_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد سائق معين لهذه المهمة'
                ], 400);
            }

            if (!$task->driver->fcm_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'السائق لا يملك رمز FCM. تأكد من تسجيل دخوله على التطبيق.'
                ], 400);
            }

            $result = $this->notificationService->send(
                type: 'driver',
                ids: [$task->driver_id],
                title: $request->title,
                body: $request->body,
                notif_type: 'task_message'
            );

            // تسجيل النشاط
            Log::info('Manual notification sent to task driver', [
                'task_id' => $task->id,
                'driver_id' => $task->driver_id,
                'driver_name' => $task->driver->name,
                'title' => $request->title,
                'sent_by' => auth()->user()->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإشعار للسائق ' . $task->driver->name . ' بنجاح',
                'driver' => $task->driver->name
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending manual notification to task driver', [
                'error' => $e->getMessage(),
                'task_id' => $request->task_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإشعار: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال إشعار لعميل من صفحة المهمة
     */
    public function sendToTaskCustomer(Request $request)
    {
        try {
            $request->validate([
                'task_id' => 'required|exists:tasks,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:500'
            ]);

            $task = Task::with('customer')->findOrFail($request->task_id);

            if (!$task->customer_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد عميل مرتبط بهذه المهمة'
                ], 400);
            }

            if (!$task->customer->fcm_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'العميل لا يملك رمز FCM. تأكد من تسجيل دخوله على التطبيق.'
                ], 400);
            }

            $result = $this->notificationService->send(
                type: 'customer',
                ids: [$task->customer_id],
                title: $request->title,
                body: $request->body,
                notif_type: 'task_message'
            );

            // تسجيل النشاط
            Log::info('Manual notification sent to task customer', [
                'task_id' => $task->id,
                'customer_id' => $task->customer_id,
                'customer_name' => $task->customer->name,
                'title' => $request->title,
                'sent_by' => auth()->user()->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإشعار للعميل ' . $task->customer->name . ' بنجاح',
                'customer' => $task->customer->name
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending manual notification to task customer', [
                'error' => $e->getMessage(),
                'task_id' => $request->task_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الإشعار: ' . $e->getMessage()
            ], 500);
        }
    }
}
