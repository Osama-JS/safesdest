<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ActivityLogController extends Controller
{
    public function index()
    {
        Log::info('Activity logs index accessed by user ID: ' . (auth()->id() ?? 'guest'));
        $totalLogs = ActivityLog::count();
        return view('admin.activity-logs.index', compact('totalLogs'));
    }

    public function getData(Request $request)
    {
        $columns = [
            0 => 'id',
            1 => 'user_id',
            2 => 'action',
            3 => 'table_name',
            4 => 'ip_address',
            5 => 'created_at',
            6 => 'actions'
        ];

        $totalData = ActivityLog::count();
        $totalFiltered = $totalData;

        $limit = $request->input('length') ?? 10;
        $start = $request->input('start') ?? 0;
        
        $orderColumnIndex = $request->input('order.0.column', 0);
        $order = $columns[$orderColumnIndex] ?? 'id';
        $dir = $request->input('order.0.dir', 'desc');

        $query = ActivityLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date')) {
            $dates = explode(' to ', $request->input('date'));
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
            } else {
                $query->whereDate('created_at', $dates[0]);
            }
        }

        if ($request->filled('action_type')) {
            $query->where('action', $request->input('action_type'));
        }

        if ($request->filled('table_name')) {
            $query->where('table_name', $request->input('table_name'));
        }

        if (!empty($request->input('search.value')) || $request->filled('general_search')) {
            $search = $request->input('search.value') ?: $request->input('general_search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                  ->orWhere('table_name', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $totalFiltered = $query->count();

        $logs = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];

        foreach ($logs as $log) {
            $nestedData = [];
            
            $nestedData['id'] = $log->id;
            $nestedData['user'] = $log->user ? $log->user->name : '<span class="text-muted">مستخدم محذوف</span>';
            
            // شارة نوع الحركة
            $actionBadge = '';
            if ($log->action == 'إنشاء') {
                $actionBadge = '<span class="badge bg-label-success">' . $log->action . '</span>';
            } elseif ($log->action == 'تحديث') {
                $actionBadge = '<span class="badge bg-label-warning">' . $log->action . '</span>';
            } elseif ($log->action == 'حذف') {
                $actionBadge = '<span class="badge bg-label-danger">' . $log->action . '</span>';
            } else {
                $actionBadge = '<span class="badge bg-label-primary">' . $log->action . '</span>';
            }
            $nestedData['action'] = $actionBadge;
            
            $nestedData['table_name'] = '<span class="fw-medium">' . $log->table_name . '</span>';
            $nestedData['ip_address'] = $log->ip_address ?? '-';
            $nestedData['created_at'] = Carbon::parse($log->created_at)->format('Y-m-d H:i');
            
            // زر عرض التفاصيل
            $nestedData['actions'] = '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill view-record" data-id="' . $log->id . '"><i class="ti ti-eye"></i></button>';

            $data[] = $nestedData;
        }

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ]);
    }

    public function show($id)
    {
        $log = ActivityLog::with('user')->findOrFail($id);
        
        $html = view('admin.activity-logs.show', compact('log'))->render();

        return response()->json(['html' => $html]);
    }
}
