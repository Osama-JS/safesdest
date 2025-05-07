<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\Driver;
use App\Notifications\NewTaskNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class DistributeTask implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public Task $task;

  public function __construct(Task $task)
  {
    $this->task = $task;
  }

  public function handle(): void
  {
    Log::info('🔄 Running job work...');

    if ($this->task->status !== 'in_progress' || $this->task->pending_driver_id !== null) {
      return;
    }

    Log::info('📝 Found the task: ' . $this->task->id . '  to work.');

    // لا تحاول توزيع أكثر من 5 مرات
    if ($this->task->distribution_attempts >= 5) {
      return;
    }

    Log::info('there is chance to assign it again');


    // إذا لم تمر 3 دقائق على آخر محاولة، تجاهل
    if ($this->task->last_attempt_at && now()->diffInMinutes($this->task->last_attempt_at) < 3) {
      Log::info('wite:  the is timing for re assign');
      return;
    }

    $excludedIds = array_filter([
      $this->task->driver_id,
      $this->task->pending_driver_id,
    ], fn($id) => !is_null($id));


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


    // تصفية السائقين الذين يتواجدون ضمن مسافة معينة (مثلاً 1 كم)
    $drivers = $drivers->filter(function ($driver) {
      $distance = $driver->distance; // المسافة المحسوبة
      return $distance <= 1000; // التصفية فقط في حال كانت المسافة أقل من 1 كم
    });




    // إذا لا يوجد سائقين
    if ($drivers->isEmpty()) {
      Log::info('👀 No Driver Found');
      return;
    }

    // إرسال للسائق الأول
    $nextDriver = $drivers->first();
    Log::info('✔ we found Driver: ' . $nextDriver->name . ' to do the task');


    // حفظ الحالة الحالية
    $this->task->update([
      'pending_driver_id' => $nextDriver->id,
      'distribution_attempts' => $this->task->distribution_attempts + 1,
      'last_attempt_at' => now(),
    ]);

    // $nextDriver->notify(new NewTaskNotification($this->task));
  }
}
