<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Task_History;
use App\Services\GoTechService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * مراقب استقبال أحداث الـ Webhook الواردة من منصة Go-Tech
 * Go-Tech Logistics Webhook Receiver
 */
class GoTechWebhookController extends Controller
{
    public function __construct(
        protected GoTechService $goTechService
    ) {}

    /**
     * معالجة أحداث الويب هوك الواردة
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-GoTech-Signature');
        $timestamp = $request->header('X-GoTech-Timestamp');

        Log::info('GoTech Webhook Received:', [
            'event'     => $request->input('event'),
            'timestamp' => $timestamp,
            'ip'        => $request->ip(),
        ]);

        // 1. التحقق من التوقيع الرقمي المشفر
        if (!$this->goTechService->verifyWebhookSignature($rawPayload, $signature, $timestamp)) {
            Log::warning('GoTech Webhook: Invalid HMAC Signature.', [
                'provided_signature' => $signature,
                'timestamp'          => $timestamp,
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Unauthorized: Invalid or missing cryptographic signature.',
            ], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);

        // 2. التحقق من حدث اختبار الاتصال (Ping Handshake)
        if ($event === 'webhook.ping') {
            Log::info('GoTech Webhook: Verification Ping successful.');
            return response()->json([
                'success' => true,
                'message' => 'Go-Tech Webhook handshake verified successfully by SafeDests platform.',
                'received_at' => now()->toIso8601String(),
            ]);
        }

        // 3. التحقق من حدث تمويل المهمة اللوجستية (task.funded)
        if ($event === 'task.funded') {
            return $this->handleTaskFunded($data);
        }

        // 4. أحداث إضافية (task.completed, task.cancelled)
        if ($event === 'task.completed') {
            return $this->handleTaskCompleted($data);
        }

        if ($event === 'task.cancelled') {
            return $this->handleTaskCancelled($data);
        }

        return response()->json([
            'success' => true,
            'message' => "Event '{$event}' acknowledged.",
        ]);
    }

    /**
     * معالجة حدث تمويل المهمة من قبل مستثمر في Go-Tech
     */
    protected function handleTaskFunded(array $data): JsonResponse
    {
        $externalTaskId = $data['external_task_id'] ?? null;
        $goTechTaskNumber = $data['task_number'] ?? null;
        $fundingAmount = (float) ($data['funding_amount'] ?? 0);
        $fundedAt = $data['funded_at'] ?? now()->toIso8601String();

        if (empty($externalTaskId)) {
            return response()->json(['success' => false, 'error' => 'Missing external_task_id in payload'], 400);
        }

        // استخراج معرف المهمة في SafeDests
        $taskId = str_starts_with((string) $externalTaskId, 'TASK-')
            ? substr((string) $externalTaskId, 5)
            : $externalTaskId;

        $task = Task::find($taskId);

        if (!$task) {
            Log::warning("GoTech Webhook: Task not found with external_task_id: {$externalTaskId}");
            return response()->json([
                'success' => false,
                'error'   => "Task not found in SafeDests with ID: {$externalTaskId}",
            ], 404);
        }

        // تجنب تكرار المعالجة إذا تم التمويل مسبقاً (Idempotency)
        if ($task->gotech_status === 'funded' && !empty($task->gotech_funded_at)) {
            Log::info("GoTech Webhook: Task #{$task->id} was already marked as funded.");
            return response()->json([
                'success' => true,
                'message' => 'Task already marked as funded in SafeDests (Idempotent response).',
                'task_id' => $task->id,
            ]);
        }

        // تحديث سجل المهمة
        $currentMetadata = $task->gotech_metadata ?? [];
        $currentMetadata['last_funded_event'] = $data;
        $currentMetadata['webhook_received_at'] = now()->toIso8601String();

        $task->update([
            'gotech_status'         => 'funded',
            'gotech_task_number'    => $goTechTaskNumber ?: $task->gotech_task_number,
            'gotech_funding_amount' => $fundingAmount > 0 ? $fundingAmount : $task->gotech_funding_amount,
            'gotech_funded_at'      => $fundedAt,
            'gotech_metadata'       => $currentMetadata,
        ]);

        // إضافة سجل تاريخي للمهمة (Task History)
        try {
            Task_History::create([
                'task_id'     => $task->id,
                'action_type' => 'gotech_funded',
                'description' => "تم تمويل المهمة بنجاح عبر منصة Go-Tech بمبلغ {$fundingAmount} ر.س (رقم Go-Tech: {$goTechTaskNumber})",
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            // non-fatal
        }

        Log::info("GoTech Webhook: Task #{$task->id} successfully updated to funded! Amount: {$fundingAmount} SAR");

        return response()->json([
            'success'          => true,
            'message'          => 'Task successfully updated to funded in SafeDests.',
            'task_id'          => $task->id,
            'gotech_task_num'  => $goTechTaskNumber,
            'funding_amount'   => $fundingAmount,
        ]);
    }

    /**
     * معالجة اكتمال المهمة من Go-Tech
     */
    protected function handleTaskCompleted(array $data): JsonResponse
    {
        $externalTaskId = $data['external_task_id'] ?? null;
        if ($externalTaskId) {
            $taskId = str_starts_with((string) $externalTaskId, 'TASK-') ? substr((string) $externalTaskId, 5) : $externalTaskId;
            $task = Task::find($taskId);
            if ($task && $task->gotech_status !== 'completed') {
                $task->update(['gotech_status' => 'completed']);
            }
        }

        return response()->json(['success' => true, 'message' => 'Task completion acknowledged.']);
    }

    /**
     * معالجة إلغاء المهمة من Go-Tech
     */
    protected function handleTaskCancelled(array $data): JsonResponse
    {
        $externalTaskId = $data['external_task_id'] ?? null;
        $goTechTaskNumber = $data['task_number'] ?? null;

        if ($externalTaskId) {
            $taskId = str_starts_with((string) $externalTaskId, 'TASK-') ? substr((string) $externalTaskId, 5) : $externalTaskId;
            $task = Task::find($taskId);
            if ($task && $task->gotech_status !== 'cancelled') {
                $task->update([
                    'gotech_status' => 'cancelled',
                    'gotech_metadata' => array_merge($task->gotech_metadata ?? [], [
                        'cancelled_via_webhook_at' => now()->toIso8601String(),
                        'cancel_event_data'        => $data,
                    ]),
                ]);

                try {
                    Task_History::create([
                        'task_id'     => $task->id,
                        'action_type' => 'gotech_cancelled',
                        'description' => "تم استلام إشعار ويب هوك من Go-Tech: تم إلغاء المهمة وسحبها من سوق التمويل، وأصبحت متاحة للمستثمرين الداخليين مجدداً.",
                        'created_at'  => now(),
                    ]);
                } catch (\Throwable $e) {}

                Log::info("GoTech Webhook: Task #{$task->id} status updated to 'cancelled' via webhook.");
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Task cancellation acknowledged and processed successfully in SafeDests.',
        ]);
    }
}

