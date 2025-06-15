<?php

namespace App\Http\Controllers\admin;

use Exception;

use Carbon\Carbon;
use App\Models\Team;
use App\Models\Teams;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Settings;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TeamsController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:view_teams', ['only' => ['index', 'getData', 'edit']]);
    $this->middleware('permission:save_teams', ['only' => ['store']]);
    $this->middleware('permission:delete_teams', ['only' => ['destroy']]);
    $this->middleware('permission:details_teams', ['only' => ['']]);
  }


  public function index()
  {
    $teams = Teams::paginate(8);
    return view('admin.teams.index', compact('teams'));
  }


  public function getData(Request $request)
  {
    $query = Teams::with('drivers');

    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('name', 'ILIKE', '%' . $search . '%')
          ->orwhere('id', 'ILIKE', '%' . $search . '%');
      });
    }
    $query->orderBy('id', 'DESC');

    $count = $query->count();

    // الإرجاع مع Pagination
    $products = $query->paginate(9); // 20 منتج لكل صفحة

    return response()->json(['data' => $products, 'count' => $count]);
  }

  public function show($id)
  {
    $data = Teams::find($id);
    if (!$data) {
      return redirect()->back();
    }
    $templates = Form_Template::all();
    $teams = Teams::all();
    $roles = Role::where('guard_name', 'driver')->get();
    $vehicles = Vehicle::all();
    $driver_template = Settings::where('key', 'driver_template')->first();
    $totals = $data->walletTransactions()
      ->select('transaction_type', DB::raw('SUM(amount) as total_amount'))
      ->groupBy('transaction_type')
      ->pluck('total_amount', 'transaction_type');

    return view('admin.teams.show', compact('data', 'templates', 'teams', 'roles', 'vehicles', 'driver_template', 'totals'));
  }


  public function getTeamDrivers(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'username',
      3 => 'name',
      4 => 'email',
      5 => 'phone',
      6 => 'role',
      7 => 'tags',
      8 => 'status',
      9 => 'created_at'
    ];

    $limit = $request->input('length');
    $start = $request->input('start');
    $order = $columns[$request->input('order.0.column')];
    $dir = $request->input('order.0.dir') ?? 'desc';

    $search = $request->input('search');
    $statusFilter = $request->input('status');
    $team = $request->input('team');

    $user = auth()->user();
    if (!$user || !$user->checkCustomer($team)) {
      return [];
    }


    $totalData = Driver::where('team_id', $team)->count();
    $totalFiltered = $totalData;

    $query = Driver::where('team_id', $team);

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%")
          ->orWhere('name', 'LIKE', "%{$search}%")
          ->orWhere('username', 'LIKE', "%{$search}%")
          ->orWhere('email', 'LIKE', "%{$search}%")
          ->orWhere('phone', 'LIKE', "%{$search}%");
      });
    }
    if (!empty($statusFilter)) {
      $query->where('status', $statusFilter);
    }

    $totalFiltered = $query->count();

    $drivers = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();


    $data = [];
    $fakeId = $start;


    foreach ($drivers as $val) {

      $data[] = [
        'id' => $val->id,
        'fake_id' => ++$fakeId,
        'name' => $val->name,
        'image'      => $val->image ? url($val->image) : null,
        'username' => $val->username,
        'email' => $val->email,
        'phone' => $val->phone,
        'tags'       => $val->tags->pluck('tag.name')->implode(', '),
        'role'       => $val->role->name ?? "",
        'created_at' => $val->created_at->format('Y-m-d H:i'),
        'status'     => $val->status,
      ];
    }

    return response()->json([
      'draw'            => intval($request->input('draw')),
      'recordsTotal'    => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code'            => 200,
      'data'            => $data,
    ]);
  }

  public function getTeamTasks(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'address',
      3 => 'driver',
      4 => 'start',
      5 => 'complete',
      6 => 'status',
      7 => 'created_at'
    ];

    $limit     = $request->input('length');
    $start     = $request->input('start');
    $order     = $columns[$request->input('order.0.column')] ?? 'id';
    $dir       = $request->input('order.0.dir') ?? 'desc';

    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');


    $team = Teams::find($request->input('team'));
    if (!$team) {
      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'code'            => 200,
        'data'            => [],
      ]);
    }

    $totalData = $team->tasks->count();
    $query =  $team->tasks();

    // ✅ فلترة بالتاريخ إذا كانت القيم موجودة
    if ($fromDate && $toDate) {
      $query->whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
      ]);
    }

    $totalFiltered = $query->count();

    $tasks = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    foreach ($tasks as $task) {
      $data[] = [
        'id'         => $task->id,
        'driver'     => $task->driver->name,
        'owner_phone'     => $task->owner == "admin" ? $task->user->phone : $task->customer->phone,
        'address'    => $task->pickup->address ?? "-",
        'price'    => $task->total_price ? number_format($task->total_price - $task->commission, 2) : "0.00",
        'closed'     => $task->closed ? "Closed" : "Open",
        'start'      => ($task->pickup && $task->pickup->scheduled_time)
          ? Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i')
          : "",
        'complete'   => ($task->delivery && $task->delivery->scheduled_time)
          ? Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i')
          : "",
        'status'     => $task->status,
        'created_at' => $task->created_at->format('Y-m-d H:i'),
      ];
    }
    return response()->json([
      'draw'            => intval($request->input('draw')),
      'recordsTotal'    => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code'            => 200,
      'data'            => $data,
    ]);
  }

  public function getTeamTransactions(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'amount',
      3 => 'driver',
      4 => 'description',
      5 => 'maturity',
      6 => 'task',
      7 => 'user',
      8 => 'created_at',
    ];

    $search = $request->input('search');
    $type = $request->input('status');

    $team = Teams::find($request->input('team'));

    if (!$team) {
      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'code'            => 200,
        'data'            => [],
      ]);
    }


    $totalData =  $team->walletTransactions()->count();
    $totalFiltered = $totalData;

    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';


    $query = $team->walletTransactions();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('sequence', 'LIKE', "%{$search}%")->orWhere('description', 'LIKE', "%{$search}%");
        $q->orWhere('amount', 'LIKE', "%{$search}%");
      });
    }

    if (!empty($type) && $type != 'all') {
      $query->where('transaction_type', $type);
    }

    $totalFiltered = $query->count();
    $wallets = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    $fakeId = $start;

    foreach ($wallets as $val) {
      $data[] = [
        'id'         => $val->id,
        'fake_id'    => ++$fakeId,
        'amount'     => $val->amount,
        'driver'     => '[' . $val->wallet_id . '] ' .  $val->wallet->driver->name,
        'type'       => $val->transaction_type,
        'description'     => $val->description,
        'maturity'    => $val->maturity_time ?? '',
        'user'    => $val->user->name ?? 'automatic',
        'task'    => $val->task_id ?? '',
        'image'   => $val->image,
        'status'   => $val->status,
        'sequence'    => $val->sequence,
        'created_at' => $val->created_at->format('Y-m-d H:i'),
      ];
    }

    return response()->json([
      'draw'            => intval($request->input('draw')),
      'recordsTotal'    => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code'            => 200,
      'data'            => $data,
    ]);
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'nullable|exists:teams,id',
      'name' => 'required|unique:teams,name,' .  ($req->id ?? 0),
      'address' => 'required',
      'commission_type' => 'nullable|in:fixed,rate,subscription',
      'commission' => 'required_with:commission_type|min:0',

    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    DB::beginTransaction();
    try {
      if (isset($req->id) && !empty($req->id)) {
        $done = Teams::find($req->id)->update([
          'name' => $req->name,
          'address' => $req->address,
          'team_commission_type' =>   $req->commission_type,
          'team_commission_value' =>  $req->commission,
          'location_update_interval' => $req->location_update,
          'note' =>  $req->note
        ]);
      } else {

        $done = Teams::create([
          'name' => $req->name,
          'address' => $req->address,
          'team_commission_type' =>   $req->commission_type,
          'team_commission_value' =>  $req->commission,
          'location_update_interval' => $req->location_update ?? 30,
          'note' =>  $req->note
        ]);
      }

      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('error to save team')]);
      }

      DB::commit();
      return response()->json(['status' => 1, 'success' => __('teams saved')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function edit($id): JsonResponse
  {
    $team = Teams::findOrFail($id);
    return response()->json($team);
  }


  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {

      $team = Teams::findOrFail($req->id);
      if ($team->drivers->count() > 0) {
        return response()->json(['status' => 2, 'error' => 'You cannot delete this team because it has associated drivers']);
      }
      $done = $team->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete team']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('team deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
