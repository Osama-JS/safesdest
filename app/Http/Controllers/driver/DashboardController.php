<?php

namespace App\Http\Controllers\driver;

use Exception;
use App\Models\Task;
use App\Models\Driver;
use App\Helpers\IpHelper;
use App\Jobs\DistributeTask;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use App\Models\Task_History;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function index()
    {
        $data = Task::where('driver_id', auth()->user()->id)
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

            $driver = Driver::findOrFail(Auth::user()->id);


            Task_History::create([
              'task_id' => $req->task,
              'description' => $req->description,
              'file_path' => $filePath,
              'file_type' => $fileType,
              'driver_id' => $driver->id,
              'action_type' => 'added',
            ]);

            $task = Task::find($req->task);
            if ($task) {
                // رسائل مخصصة لكل نوع
                $notifications = [
                    'user' => [
                        'title' => 'إضافة ملاحظة للمهمة',
                        'msg'   => "تمت إضافة ملاحظة إلى مهمتك رقم #{$task->id}"
                    ],
                    'customer' => [
                        'title' => 'تحديث على المهمة الخاصة بك',
                        'msg'   => "تمت إضافة ملاحظة على مهمتك رقم #{$task->id} من قِبل فريق العمل"
                    ],
                ];

                // قائمة المستلمين: [نوع => ID]
                $recipients = [
                    'user'     => $task->user_id,
                    'customer' => $task->customer_id,
                    'driver'   => $task->driver_id,
                ];

                foreach ($recipients as $type => $id) {
                    if ($id && isset($notifications[$type])) {
                        $noti = $notifications[$type];

                        app(\App\Services\NotificationService::class)->send(
                            $type,
                            [$id], // IDs المستلمين
                            $noti['title'],
                            $noti['msg'],
                            '/images/admin-icon.png',
                            '/images/banner.png',
                            "/tasks/{$task->id}",
                            'task_note' // نوع الإشعار
                        );
                    }
                }

            }


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

    public function getCurrentTaskHistory($id)
    {
        try {
            $task = Task::findOrFail($id);
            if (auth()->user()->id !== $task->driver_id) {
                return response()->json([
                  'status' => 2,
                  'error'  => __('you do not have the permission to preview this data')
                ]);
            }

            $taskHistory = Task_History::where('task_id', $id)
              ->orderByDesc('id') // استخدم orderByDesc بدلاً من sortByDesc
              ->get()
              ->map(function ($val) {
                  return [
                    'type' => $val->action_type,
                    'description' => $val->description,
                    'date' => $val->created_at->format('F, Y-d H:i'),
                    'user' => optional($val->user)->name,
                    'driver' => optional($val->driver)->name,
                    'file' => $val->file_path
                      ? [
                        'url' => asset('storage/' . $val->file_path),
                        'type' => pathinfo($val->file_path, PATHINFO_EXTENSION),
                        'name' => basename($val->file_path),
                      ]
                      : null,
                    'color' => match ($val->action_type) {
                        'added' => 'success',
                        'updated' => 'info',
                        'assign' => 'primary',
                        'canceld' => 'danger',
                        default => 'secundary',
                    }
                  ];
              })
              ->values();

            return response()->json([
              'status' => 1,
              'data'   => $taskHistory
            ]);
        } catch (Exception $ex) {
            return response()->json([
              'status' => 2,
              'error'  => $ex->getMessage()
            ]);
        }
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
          'task_id' => 'required|exists:tasks,id',
          'status' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $task = Task::findOrFail($request->task_id);

            // ترتيب الحالات
            $statuses = [
              'started',
              'in pickup point',
              'loading',
              'in the way',
              'in delivery point',
              'unloading',
              'completed',
            ];

            $currentIndex = array_search($task->status, $statuses);
            $requestedIndex = array_search($request->status, $statuses);

            // تحقق من الصلاحية
            if ($requestedIndex === false || $requestedIndex !== $currentIndex + 1) {
                return back()->with('error', 'Invalid status change.');
            }
            if ($task->closed) {
                return back()->with('error', 'Error: ' .  'This Task is already closed');
            }


            $notiMessages = [
                     'user' => [
                         'title' => '📌 تحديث حالة المهمة الخاصة بك',
                         'msg'   => "تم تحديث حالة المهمة رقم #{$task->id} من '{$task->status}' إلى '{$request->status}'."
                     ],
                     'customer' => [
                         'title' => 'تحديث حالة طلبك',
                         'msg'   => "تم تغيير حالة المهمة رقم #{$task->id} من '{$task->status}' إلى '{$request->status}'."
                     ],
                 ];


            $task->status = $request->status;
            if ($request->status == 'completed') {
                $task->completed_at = now();
            }
            $task->save();

            // إضافة إلى سجل التاريخ إن لزم
            Task_History::create([
              'task_id' => $task->id,
              'action_type' => $request->status,
              'description' => "Driver changed status to '{$request->status}'",
              'driver_id' => auth()->user()->id,
            ]);

            // قائمة المستلمين: [نوع => ID]
            $recipients = [
                'user'     => $task->user_id,
                'customer' => $task->customer_id,
            ];

            foreach ($recipients as $type => $id) {
                if ($id && isset($notiMessages[$type])) { // تأكد من وجود ID ورسالة
                    app(\App\Services\NotificationService::class)->send(
                        $type,
                        [$id], // IDs المستلمين
                        $notiMessages[$type]['title'],
                        $notiMessages[$type]['msg'],
                        '/images/admin-icon.png',
                        '/images/banner.png',
                        "/tasks/{$task->id}",
                        'task_status'
                    );
                }
            }



            DB::commit();
            return back()->with('success', 'Task status updated successfully.');
        } catch (Exception $ex) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $ex->getMessage());
        }
    }

    public function profile()
    {
        $data = Driver::find(Auth::user()->id);
        return view('drivers.profile.index', compact('data'));
    }

    public function updateProfile(Request $req)
    {

        $validator = Validator::make($req->all(), [
          'name'         => 'required|string',
          'username'         => 'required|unique:users,phone,' . Auth::id(),
          'address'      => 'required|string',
          'phone'        => 'required|unique:users,phone,' . Auth::id(),
          'phone_code'   => 'required|string',
          'password'     => 'nullable|same:confirm-password',
        ], [
          'name.required'        => __('The name field is required.'),
          'username.required'       => __('The username field is required.'),
          'username.unique'         => __('The username has already been taken.'),
          'address.required'       => __('The Address field is required.'),
          'address.string'         => __('The Address field is required.'),
          'phone.required'       => __('The phone field is required.'),
          'phone.unique'         => __('The phone has already been taken.'),
          'phone_code.required'  => __('The phone code is required.'),
          'phone_code.string'    => __('The phone code must be a string.'),
          'password.same'        => __('The password and confirmation must match.'),
        ]);



        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }
        try {
            $find = Driver::findOrFail(Auth::user()->id);
            $password =  $find->password;
            if ($req->filled('password')) {
                $password = Hash::make($req->password);
            }
            $image = $find->image;
            $oldImage = null;

            if ($req->hasFile('image')) {
                $image = (new FunctionsController())->convert($req->image, 'drivers');
                $oldImage = $find->image;
            }

            $done = $find->update([
              'name' => $req->name,
              'username' => $req->username,
              'image'   => $image,
              'address' => $req->address,
              'password' => $password,
              'phone' => $req->phone,
              'phone_code' => $req->phone_code,
            ]);

            if (!$done) {
                return response()->json(['status' => 2, 'error' => __('Error to Update Profile')]);
            }
            if ($oldImage && $req->hasFile('image')) {
                unlink($oldImage);
            }
            return response()->json(['status' => 1, 'success' => __('Profile Updated Successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    public function deleteAccount(Request $req)
    {
        DB::beginTransaction();
        try {
            $find = Driver::findOrFail($req->id);
            $find->delete(); // soft delete
            DB::commit();
            return response()->json(['status' => 1, 'success' => __('Your Account Deleted Successfully')]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }
}
