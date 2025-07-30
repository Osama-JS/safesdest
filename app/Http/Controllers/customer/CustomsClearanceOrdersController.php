<?php

namespace App\Http\Controllers\customer;

use Exception;
use Illuminate\Http\Request;
use App\Models\Customs_Clearance;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Customs_Clearance_History;
use Illuminate\Support\Facades\Validator;

class CustomsClearanceOrdersController extends Controller
{
  public function index()
  {
    return view('customers.customs-clearance.orders');
  }

  public function data(Request $request)
  {
    $query = Customs_Clearance::where('clearance_agent_id', auth()->user()->id)->with(['customer', 'user', 'formTemplate', 'clearanceAgent', 'offers']);

    // Filter by form search
    if ($request->filled('search')) {
      $query->Orwhere('id', 'like', "%{$request->search}%")
        ->Orwhere('total_price', 'like', "%{$request->search}%");
    }

    // Filter by status
    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    // Filter by closed
    if ($request->filled('closed')) {
      $query->where('closed', $request->closed == 'true' ? 1 : 0);
    }

    // Filter by date range
    if ($request->filled('date_from') && $request->filled('date_to')) {
      $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
    }

    $totalData = $query->count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);

    // Define sortable columns
    $columns = ['id', 'id', 'owner', 'clearance_agent', 'status', 'total_price', 'created_at'];
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'desc');



    $clearances = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];

    foreach ($clearances as $clearance) {
      // Determine owner info
      $owner = $clearance->customer ? $clearance->customer : $clearance->user;
      $ownerType = $clearance->customer ? 'Customer' : 'Admin';

      // Status badge
      $statusBadge = $this->getStatusBadge($clearance->status);


      $totalPrice = $clearance->total_price - $clearance->commission;
      $pricing = $clearance->pricing;

      // Price info
      $priceInfo = '<span class="border border-primary rounded text-primary px-2"><b>' . number_format($totalPrice,  $pricing->decimal_places ?? 2) . ' SAR </b></span>';

      // Offers count
      $offersCount = $clearance->offers->count();

      // Actions based on status and permissions
      $actions = $this->generateActions($clearance);

      $data[] = [
        'id' => $clearance->id,
        'owner' => [
          'name' => $owner ? $owner->name : __('Not specified'),
          'type' => $ownerType,
          'badge' => $ownerType === 'Customer' ?
            '<span class="">(customer)</span>' :
            '<span class="">(administrator)</span>'
        ],
        'status' => $statusBadge,
        'closed' => $clearance->closed,
        'price_info' => $priceInfo,
        'included' => $clearance->included,
        'offers_count' => $offersCount > 0 ?
          '<span class="badge bg-info">' . $offersCount . '</span>' :
          '<span class="text-muted">0</span>',
        'created_at' => $clearance->created_at->format('Y-m-d H:i'),
        'actions' => $actions,

      ];
    }

    return response()->json([
      'draw' => intval($request->input('draw')),
      'recordsTotal' => intval($totalData),
      'recordsFiltered' => intval($totalFiltered),
      'data' => $data,
      'summary' => $this->getStatistics()
    ]);
  }

  private function getStatusBadge($status)
  {
    $badges = [
      'in_progress' => `<span class="badge bg-warning">__('in_progress')</span>`,
      'assign' => `<span class="badge bg-info">__('assign')</span>`,
      'accepted' => `<span class="badge bg-primary">__('accepted')</span>`,
      'start' => `<span class="badge bg-secondary">__('start')</span>`,
      'completed' => `<span class="badge bg-success">__('completed')</span>`,
      'canceled' => `<span class="badge bg-danger">__('canceled')</span>`,
    ];

    return $badges[$status] ?? '<span class="badge bg-light text-dark">' . $status . '</span>';
  }

  private function generateActions($clearance)
  {
    $actions = '<div class="d-flex align-items-center gap-1">';

    // View action
    $actions .= '<a href="' . route('customer.customs-clearances.orders.show', $clearance->id) . '"
                   class="btn btn-sm btn-icon  rounded-pill waves-effect"
                   data-bs-toggle="tooltip" title="View details">
                   <i class="ti ti-eye ti-md"></i>
                 </a>';

    $actions .= '</div>';



    return $actions;
  }

  private function getStatistics()
  {
    return [
      'total' => Customs_Clearance::count(),
      'in_progress' => Customs_Clearance::where('status', 'in_progress')->count(),
      'completed' => Customs_Clearance::where('status', 'completed')->count(),
      'canceled' => Customs_Clearance::where('status', 'canceled')->count(),
      'total_value' => Customs_Clearance::sum('total_price'),
    ];
  }

  public function show($id)
  {
    $data = Customs_Clearance::findOrFail($id);
    return view('customers.customs-clearance.orders-show', compact('data'));
  }

  public function updateStatus(Request $request)
  {
    $request->validate([
      'id' => 'required|exists:customs_clearance,id',
      'status' => 'required|string',
    ]);

    DB::beginTransaction();
    try {
      $task = Customs_Clearance::findOrFail($request->id);

      // ترتيب الحالات
      $statuses = [
        'start',
        'completed',
      ];

      $currentIndex = array_search($task->status, $statuses);
      $requestedIndex = array_search($request->status, $statuses);

      // تحقق من الصلاحية
      if ($requestedIndex === false || $requestedIndex !== $currentIndex + 1) {
        return back()->with('error', 'Invalid status change.');
      }
      if ($task->closed) {
        return back()->with('error', 'Error: ' .  'This Customs Clearance is already closed');
      }

      $task->status = $request->status;
      if ($request->status == 'completed') {
        $task->completed_at = now();
      }
      $task->save();

      // إضافة إلى سجل التاريخ إن لزم
      Customs_Clearance_History::create([
        'customs_clearance_id' => $task->id,
        'action_type' => $request->status,
        'description' => "Customs Clearance Agent changed status to '{$request->status}'",
        'clearance_agent_id' => auth()->user()->id,
      ]);

      DB::commit();
      return back()->with('success', 'Task status updated successfully.');
    } catch (Exception $ex) {
      DB::rollBack();
      dd($ex->getMessage());
      return back()->with('error', 'Error: ' . $ex->getMessage());
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
        $filePath = $file->storeAs('clearance_histories', $fileName, 'public');

        // استخراج نوع الملف (الامتداد)
        $fileType = $file->getClientOriginalExtension();
      }

      $broker = Customer::findOrFail(auth()->user()->id);

      if ($broker->is_customs_clearance_agent != 1) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('You are not a Customs Clearance Agent')]);
      }

      Customs_Clearance_History::create([
        'customs_clearance_id' => $req->task,
        'description' => $req->description,
        'file_path' => $filePath,
        'file_type' => $fileType,
        'clearance_agent_id' => $broker->id,
        'action_type' => 'added',
      ]);

      DB::commit();
      return response()->json([
        'status' => 1,
        'success' => 'Customs Clearance Note Added Successfully',
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
