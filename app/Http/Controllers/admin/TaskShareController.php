<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskShareController extends Controller
{
    public function share($id)
    {
        // إذا كان المعرف غير رقمي، نحاول فك التشفير (Base64)
        if (!is_numeric($id)) {
            try {
                $decoded = base64_decode($id);
                if (strpos($decoded, 'TASK-') === 0) {
                    $id = str_replace('TASK-', '', $decoded);
                }
            } catch (\Exception $e) {
                // في حال فشل فك التشفير، نستمر بالمعرف الأصلي أو نخرج خطأ
            }
        }

        $task = Task::findOrFail($id);

        // Prepare data for the view
        $data = [
            'task' => $task,
            'app_scheme' => 'safedestdriver://open',
            'android_package' => 'com.safedest.driver',
            'ios_id' => '', // Placeholder if needed
        ];

        return view('admin.tasks.share', compact('data'));
    }
}
