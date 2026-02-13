<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskShareController extends Controller
{
    public function share($id)
    {
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
