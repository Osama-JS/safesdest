<?php

namespace App\Http\Controllers\driver;

use App\Http\Controllers\Controller;
use App\Jobs\DistributeTask;
use App\Models\Driver;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
  public function index()
  {
    return view('drivers.index');
  }

  public function updateLocation(Request $request)
  {
    $driver = Driver::findOrFail(Auth::user()->id);

    $driver->update([
      'longitude' => $request->longitude,
      'altitude' => $request->altitude,
      'last_seen_at' => now(),
      'online' => true
    ]);

    return response()->json(['status' => true]);
  }

  public function respondToTask(Request $request)
  {
    $request->validate([
      'task_id' => 'required|exists:tasks,id',
      'response' => 'required|in:accept,reject',
    ]);

    $task = Task::findOrFail($request->task_id);

    // التحقق من أن السائق هو من تم إرسال المهمة له
    if (auth('driver')->id() !== $task->pending_driver_id) {
      return response()->json(['message' => 'Unauthorized'], 403);
    }

    if ($request->response === 'accept') {
      $task->update([
        'driver_id' => auth('driver')->id(),
        'status' => 'assign',
        'pending_driver_id' => null,
      ]);

      return response()->json(['message' => 'Task accepted']);
    }

    // في حالة الرفض
    $task->update([
      'pending_driver_id' => null,
    ]);

    // إعادة المحاولة بعد 10 ثوانٍ
    dispatch(new DistributeTask($task))->delay(now()->addSeconds(10));

    return response()->json(['message' => 'Task rejected. Will be redistributed.']);
  }
}
