<?php

namespace App\Http\Controllers\driver;

use Exception;
use App\Models\Task;
use App\Models\Driver;
use App\Helpers\IpHelper;
use App\Jobs\DistributeTask;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Task_History;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class DashboardController extends Controller
{
  public function index()
  {
    $data = Task::where('driver_id', auth()->user()->id)
      ->where('status', '!=', 'completed')
      ->where('closed', 0)
      ->orderBy('created_at', 'desc')
      ->get();
    return view('drivers.index', compact('data'));
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
    $validator = Validator::make($request->all(), [
      'task_id' => 'required|exists:tasks,id',
      'response' => 'required|in:accept,reject',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }

    DB::beginTransaction();
    try {

      $task = Task::findOrFail($request->task_id);

      $driver = Driver::findOrFail(Auth::user()->id);

      // التحقق من أن السائق هو من تم إرسال المهمة له
      if (auth('driver')->id() !== $task->pending_driver_id) {
        return response()->json(['status' => 2, 'error' => 'Error: Unauthorized']);
      }

      if ($request->response === 'accept') {
        $userIp = IpHelper::getUserIpAddress();
        $history = [
          [
            'action_type' => 'assign',
            'description' => 'assign task Automatic',
            'ip' => $userIp,
            'driver_id' => $driver->id
          ]
        ];

        $task->update([
          'driver_id' => auth('driver')->id(),
          'status' => 'assign',
          'commission' =>  $task->total_price - $driver->calculateCommission($task->total_price),
          'pending_driver_id' => null,
        ]);
        $task->history()->createMany($history);
      }
      $task->update([
        'pending_driver_id' => null,
      ]);

      DB::commit();
      return response()->json([
        'status'  => 1,
        'success' => 'you ' . $request->response . ' This Task Successfully',
      ]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error'  => $ex->getMessage()
      ]);
    }
  }

  public function taskAddToHistories(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'description' => 'nullable|string|required_without:file',
      'file' => 'nullable|file|max:10240|required_without:description',
      'task' => 'required|exists:tasks,id',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }

    DB::beginTransaction();
    try {

      $filePath = null;
      $fileType = null;

      if ($req->hasFile('file')) {
        $file = $req->file('file');

        // إنشاء بادئة عشوائية مكونة من أرقام فقط (مثلاً: 4 أرقام)
        $prefix = rand(1000, 9999);

        // الحصول على الاسم الأصلي للملف
        $originalName = $file->getClientOriginalName();

        // اسم الملف النهائي: بادئة-الاسم_الأصلي
        $fileName = $prefix . '-' . $originalName;

        // حفظ الملف في مجلد 'task_histories' داخل التخزين العام
        $filePath = $file->storeAs('task_histories', $fileName, 'public');

        // استخراج نوع الملف (الامتداد)
        $fileType = $file->getClientOriginalExtension();
      }


      Task_History::create([
        'task_id' => $req->task,
        'description' => $req->description,
        'file_path' => $filePath,
        'file_type' => $fileType,
        'driver_id' => auth()->user()->type === 'driver' ? auth()->id() : null,
        'action_type' => 'added',
      ]);

      DB::commit();
      return response()->json([
        'status' => 1,
        'success' => 'Task Note Added Successfully',
      ]);
    } catch (Exception $ex) {
      DB::rollBack();
      if ($req->hasFile('file')) {
        unlink($filePath);
      }
      return response()->json([
        'status' => 2,
        'error'  => $ex->getMessage()
      ]);
    }
  }
}
