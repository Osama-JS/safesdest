<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Task;
use App\Models\MtahdDealLog;
use App\Services\MtahdService;
use Illuminate\Support\Facades\Log;
use Exception;

class MtahdWebhookController extends Controller
{
    protected MtahdService $mtahdService;

    public function __construct(MtahdService $mtahdService)
    {
        $this->mtahdService = $mtahdService;
    }

    /**
     * استقبال ومعالجة إشعارات الـ Webhook من منصة متعهد (أمن)
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Signature') 
                  ?? $request->header('X-Amnn-Signature') 
                  ?? $request->header('X-Webhook-Token');

        // 1. التحقق الأمني من توقيع الـ Webhook
        $isValidSignature = $this->mtahdService->verifyWebhookSignature($rawPayload, $signature);

        if (!$isValidSignature) {
            Log::warning('Mtahd Webhook: Invalid Signature rejected', [
                'ip'        => $request->ip(),
                'signature' => $signature,
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Invalid webhook signature'
            ], 401);
        }

        $data = $request->all();
        $event = $data['event'] ?? ($data['action'] ?? ($data['type'] ?? 'unknown'));
        $dealNumber = $data['deal_number'] ?? ($data['data']['deal_number'] ?? null);
        $dealId = $data['deal_id'] ?? ($data['data']['id'] ?? null);
        $customId = $data['custom_id'] ?? ($data['data']['custom_id'] ?? null);

        Log::info('Mtahd Webhook Received', [
            'event'       => $event,
            'deal_number' => $dealNumber,
            'custom_id'   => $customId,
        ]);

        // 2. تحديد المهمة المرتبطة بالصفقة
        $task = null;
        if ($dealNumber) {
            $task = Task::where('amnn_deal_number', $dealNumber)->first();
        }
        if (!$task && $customId && str_starts_with($customId, 'TASK_')) {
            $taskId = intval(substr($customId, 5));
            $task = Task::find($taskId);
        }

        // 3. توثيق استلام الـ Webhook في سجل العمليات
        $this->mtahdService->logDealOperation([
            'task_id'          => $task?->id,
            'deal_number'      => $dealNumber,
            'deal_id'          => $dealId ? (string)$dealId : null,
            'action'           => 'webhook_received',
            'status'           => 'success',
            'amount'           => $data['amount'] ?? ($data['data']['amount'] ?? null),
            'request_payload'  => $data,
            'http_status'      => 200,
            'notes'            => "استلام حدث الـ Webhook: {$event}",
        ]);

        // 4. معالجة أحداث السداد والضمان
        if (in_array($event, ['deal.paid', 'payment.completed', 'deal_paid', 'paid', 'deal.confirmed'])) {
            if ($task) {
                $task->update([
                    'payment_status'   => 'completed',
                    'amnn_deal_status' => 'paid',
                ]);

                Log::info("Task #{$task->id} payment confirmed via Mtahd Webhook");
            }
        } elseif (in_array($event, ['deal.released', 'released', 'deal.disbursed'])) {
            if ($task) {
                $task->update([
                    'amnn_deal_status' => 'released',
                ]);
            }
        } elseif (in_array($event, ['deal.cancelled', 'deal.canceled', 'cancelled', 'canceled'])) {
            if ($task) {
                $task->update([
                    'amnn_deal_status' => 'cancelled',
                ]);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Webhook processed successfully'
        ], 200);
    }
}
