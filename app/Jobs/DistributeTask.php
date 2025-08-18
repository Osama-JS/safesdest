<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\Driver;
use App\Models\TaskDriverAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Helpers\IpHelper;

class DistributeTask implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle(): void
    {
        Log::info('🔄 Running job work for task ID: ' . $this->task->id);

        if ($this->task->status !== 'in_progress') {
            Log::info('❌ Task is not in progress.');
            return;
        }

        // إذا كان هناك سائق في الانتظار ولم يرد، وأكملت 3 دقائق، قم بإلغاء التخصيص
        if ($this->task->pending_driver_id !== null) {
            if (!$this->task->last_attempt_at || $this->task->last_attempt_at->lte(now()->subMinutes(3))) {
                Log::info('⛔ No response from pending driver. Releasing task for reassignment.');

                $this->task->update([
                    'pending_driver_id' => null
                ]);
            } else {
                Log::info('⏱️ Still waiting for pending driver response. Time diff: ' . now()->diffInMinutes($this->task->last_attempt_at));
                return;
            }
        }

        // لا تحاول توزيع أكثر من 5 مرات
        if ($this->task->distribution_attempts >= 5) {
            Log::info('🚫 Max distribution attempts reached.');
            return;
        }

        // استثناء السائقين الذين سبق تخصيصهم
        $excludedIds = array_filter([
            $this->task->driver_id,
            $this->task->pending_driver_id,
        ], fn ($id) => !is_null($id));

        // استثناء السائقين الذين تم إرسال المهمة لهم مسبقًا
        $previouslyTriedDriverIds = TaskDriverAttempt::where('task_id', $this->task->id)
            ->pluck('driver_id')
            ->toArray();

        $excludedIds = array_unique(array_merge($excludedIds, $previouslyTriedDriverIds));

        $drivers = Driver::where('vehicle_size_id', $this->task->vehicle_size_id)
            ->where('online', true)
            ->when(!empty($excludedIds), function ($query) use ($excludedIds) {
                $query->whereNotIn('id', $excludedIds);
            })
            ->orderByRaw("
                ST_Distance(
                    ST_SetSRID(ST_MakePoint(drivers.longitude, drivers.altitude), 4326)::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                )
            ", [
                $this->task->pickup->longitude,
                $this->task->pickup->altitude,
            ])
            ->get();

        // تصفية السائقين لمسافة أقصاها 1 كم
        $drivers = $drivers->filter(function ($driver) {
            $distance = $driver->distance;
            return $distance <= 1000;
        });

        if ($drivers->isEmpty()) {
            Log::info('❌ No suitable drivers found nearby.');

            // إذا كانت هذه هي المحاولة الخامسة بالضبط
            if ($this->task->distribution_attempts + 1 == 5) {
                $this->task->update([
                    'status' => 'canceled',
                    'distribution_attempts' => $this->task->distribution_attempts + 1,
                    'last_attempt_at' => now(),
                ]);

                // إضافة سجل في الـ history
                $this->task->history()->create([
                    'action_type' => 'canceled',
                    'description' => 'The task was canceled because no driver accepted it after 5 distribution attempts.',
                    'ip' => IpHelper::getUserIpAddress(),
                    'user_id' => null, // تم الإلغاء تلقائيًا
                ]);

                Log::info('🚫 Task canceled on 5th attempt due to no driver acceptance.', [
                    'task_id' => $this->task->id
                ]);

                return;
            }

            // إذا لم نصل بعد للمحاولة الخامسة
            $this->task->update([
                'distribution_attempts' => $this->task->distribution_attempts + 1,
                'last_attempt_at' => now(),
            ]);
            return;
        }

        // إرسال المهمة لأقرب سائق
        $nextDriver = $drivers->first();
        Log::info('✔ Assigned to driver: ' . $nextDriver->name);

        $this->task->update([
            'pending_driver_id' => $nextDriver->id,
            'distribution_attempts' => $this->task->distribution_attempts + 1,
            'last_attempt_at' => now(),
        ]);

        // تسجيل محاولة التوزيع
        TaskDriverAttempt::create([
            'task_id' => $this->task->id,
            'driver_id' => $nextDriver->id,
            'attempted_at' => now(),
            'status' => 'ignored',
        ]);
    }
}
