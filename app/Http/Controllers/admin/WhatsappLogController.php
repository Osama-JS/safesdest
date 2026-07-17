<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhatsappLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_whatsapp_logs')->only(['index']);
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = \App\Models\WhatsappMessage::with('conversation');

            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }
            if ($request->has('direction') && $request->direction != '') {
                $query->where('direction', $request->direction);
            }

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('content', 'like', "%{$search}%")
                      ->orWhereHas('conversation', function($q) use ($search) {
                          $q->where('phone_number', 'like', "%{$search}%");
                      });
                });
                $totalFiltered = $query->count();
            }

            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);
            $orderDir = $request->input('order.0.dir', 'desc');
            
            $query->orderBy('id', $orderDir);
            
            if ($limit > 0) {
                $query->offset($start)->limit($limit);
            }

            $data = [];
            foreach ($query->get() as $row) {
                $directionBadge = $row->direction == 'outbound' ? '<span class="badge bg-label-primary">صادرة</span>' : '<span class="badge bg-label-success">واردة</span>';
                
                $badges = [
                    'pending' => '<span class="badge bg-label-warning">قيد الانتظار</span>',
                    'sent' => '<span class="badge bg-label-info">تم الإرسال</span>',
                    'delivered' => '<span class="badge bg-label-primary">تم التسليم</span>',
                    'read' => '<span class="badge bg-label-success">مقروءة</span>',
                    'failed' => '<span class="badge bg-label-danger">فشلت</span>',
                ];
                $statusBadge = $badges[$row->status] ?? $row->status;

                $data[] = [
                    'id' => $row->id,
                    'phone_number' => $row->conversation ? $row->conversation->phone_number : '-',
                    'direction_badge' => $directionBadge,
                    'message_type' => $row->message_type,
                    'content' => '<div style="max-width: 250px; white-space: normal;">' . \Illuminate\Support\Str::limit($row->content, 100) . '</div>',
                    'status_badge' => $statusBadge,
                    'created_at' => $row->created_at->format('Y-m-d H:i:s'),
                    'error_log' => '<div style="max-width: 250px; white-space: normal;" class="text-danger">' . $row->error_log . '</div>',
                ];
            }

            return response()->json([
                "draw" => intval($request->input('draw')),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            ]);
        }

        $stats = [
            'total_messages' => \App\Models\WhatsappMessage::count(),
            'failed_messages' => \App\Models\WhatsappMessage::where('status', 'failed')->count(),
            'delivered_messages' => \App\Models\WhatsappMessage::where('status', 'delivered')->count(),
            'read_messages' => \App\Models\WhatsappMessage::where('status', 'read')->count(),
        ];

        return view('admin.whatsapp-logs.index', compact('stats'));
    }
}
