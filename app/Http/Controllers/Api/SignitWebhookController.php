<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Task;

class SignitWebhookController extends Controller
{
    /**
     * معالجة طلبات الـ Webhook القادمة من منصة Signit
     *
     * الآلية: يقوم Signit بإرسال طلب POST عند تغيير حالة التوقيع.
     * يحتوي الجسم عادةً على معلومات الطلب والحالة الجديدة.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        Log::info('Signit Webhook Recieved:', $payload);

        // استخراج معرف الطلب والحالة
        // بناءً على هيكلية API Signit، نتوقع وجود المعرف والحالة في الجسم
        // ملاحظة: قد تختلف التسميات حسب وثائق الـ Webhook الخاصة بـ Signit
        // لكننا سنعتمد على الهيكلية الأكثر شيوعاً أو التي ظهرت في استجابة الـ API

        $requestId = $payload['id'] ?? ($payload['signature_request']['id'] ?? null);
        $newStatus = $payload['status'] ?? ($payload['signature_request']['status'] ?? null);

        if (!$requestId || !$newStatus) {
            Log::warning('Signit Webhook: Missing required data (ID or Status)', $payload);
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // البحث عن المهمة المرتبطة بهذا المعرف
        $task = Task::where('signature_request_id', $requestId)->first();

        if (!$task) {
            Log::warning("Signit Webhook: Task not found for Request ID: $requestId");
            return response()->json(['message' => 'Task not found'], 404);
        }

        // تحديث حالة المهمة
        try {
            $task->update([
                'signature_status' => $newStatus
            ]);

            Log::info("Signit Webhook: Task #{$task->id} updated to status: $newStatus");

            return response()->json(['message' => 'Webhook processed successfully']);

        } catch (\Exception $e) {
            Log::error('Signit Webhook Error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
