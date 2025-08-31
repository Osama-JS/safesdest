<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function driversIndex()
    {
        return view('admin.index-drivers');
    }


    public function getTasksData(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        $query = Task::with('points', 'customer', 'user', 'driver');

        // ⛔️ فلترة بناءً على الصلاحيات
        if (!$user->can('manage_tasks')) {
            $customerIds = $user->customers()->pluck('customers.id')->toArray();
            $teamIds = $user->teams()->pluck('teams.id')->toArray();

            $query->where(function ($q) use ($user, $customerIds, $teamIds) {
                $q->where('user_id', $user->id);

                if (!empty($customerIds)) {
                    $q->orWhereIn('customer_id', $customerIds);
                }

                if (!empty($teamIds)) {
                    $q->orWhereIn('team_id', $teamIds);
                }
            });
        }

        // 🔍 البحث
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'ILIKE', '%' . $search . '%');
            });
        }

        // 📦 الترتيب وجلب البيانات
        $query->orderBy('id', 'DESC');
        $tasks = $query->get();

        // ✅ التصنيف حسب الحالة
        $runningStatuses = [
          'in_progress',
          'assign',
          'started',
          'in pickup point',
          'loading',
          'in the way',
          'in delivery point',
          'unloading',
          'invoiced'
        ];

        $grouped = [
          'running' => [],
          'completeUnClosed' => [],
        ];

        foreach ($tasks as $task) {
            $customer = $task->customer;
            $userOwner = $task->user;
            $driver = $task->driver;

            $avatar = $customer && $customer->avatar
              ? asset('storage/' . $customer->avatar)
              : asset('assets/img/person.png');

            $item = [
              'id' => $task->id,
              'name' => $customer ? $customer->name : ($userOwner->name ?? 'غير معروف'),
              'owner' => $customer ? 'customer' : 'admin',
              'status' => $task->status,
              'complete_at' => $task->completed_at,
              'avatar' => $avatar,
              'point' => $task->point()->where('type', 'pickup')->first(),
              'closed' => $task->closed,
            ];

            if ($driver) {
                $item['driver'] = [
                  'id' => $driver->id,
                  'name' => $driver->name,
                  'phone' => $driver->phone,
                  'phone_code' => $driver->phone_code,
                  'avatar' => $driver->image
                    ? asset('storage/' . $driver->image)
                    : asset('assets/img/person.png'),
                  'team' => $driver->team ? $driver->team->name : null,
                ];
            }

            if (in_array($task->status, $runningStatuses)) {
                $grouped['running'][] = $item;
            } elseif ($task->status === 'completed' && !$task->closed) {
                $grouped['completeUnClosed'][] = $item;
            }
        }

        return response()->json(['data' => $grouped]);
    }


    public function getDriversData(Request $request)
    {
        $runningStatuses = [
          'in_progress',
          'assign',
          'started',
          'in pickup point',
          'loading',
          'in the way',
          'in delivery point',
          'unloading',
          'completed'
        ];

        $user = auth()->user();

        // بدء الاستعلام الأساسي
        $driversQuery = Driver::with(['tasks' => function ($query) use ($runningStatuses) {
            $query->whereIn('status', $runningStatuses)->where('closed', false);
        }])->where('status', 'active');

        // ✅ فقط إن لم يكن لديه صلاحية إدارة السائقين
        if (!$user->can('mange_drivers')) {
            $teamIds = $user->teams->pluck('id')->toArray();

            // جلب السائقين المرتبطين بفرق يديرها المستخدم فقط
            $driversQuery->whereIn('team_id', $teamIds);
        }

        $drivers = $driversQuery->get();

        $grouped = [
          'online' => [],
          'offline' => [],
          'busy' => [],
        ];

        foreach ($drivers as $driver) {
            $avatar = $driver->image ? asset($driver->image) : asset('assets/img/person.png');

            $item = [
              'id' => $driver->id,
              'name' => $driver->name,
              'phone' => $driver->phone,
              'phone_code' => $driver->phone_code,
              'whatsapp' => $driver->full_whatsapp_number,
              'whatsapp_display' => $driver->whatsapp_display,
              'online' => $driver->online,
              'avatar' => $avatar,
              'last_seen_at' => $driver->last_seen_at,
              'location' => [
                'longitude' => $driver->longitude,
                'altitude' => $driver->altitude,
              ],
            ];

            if ($driver->tasks?->isNotEmpty()) {
                $grouped['busy'][] = $item;
            } elseif ($driver->online) {
                $grouped['online'][] = $item;
            } else {
                $grouped['offline'][] = $item;
            }
        }

        return response()->json(['data' => $grouped]);
    }
}
