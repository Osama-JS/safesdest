<?php

namespace App\Http\Controllers\admin;

use Exception;

use Carbon\Carbon;
use App\Models\Teams;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Settings;
use App\Models\Team_Wallet;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Models\Wallet_Transaction;
use Illuminate\Http\JsonResponse;
use App\Models\Task;
use App\Models\Geofence_Team;
use App\Models\Team_Wallet_Transaction;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TeamsController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:view_teams', ['only' => ['index', 'getData', 'edit']]);
    $this->middleware('permission:save_teams', ['only' => ['store']]);
    $this->middleware('permission:delete_teams', ['only' => ['destroy']]);
    $this->middleware('permission:details_teams', ['only' => ['dashboard', 'driversPage', 'tasksPage', 'walletPage', 'taskDistributionPage', 'analyticsPage', 'getTeamDrivers']]);
  }


  public function index()
  {
    return view('admin.teams.index');
  }


  public function getData(Request $request)
  {
    $user = auth()->user();
    if (!$user) {
      abort(403);
    }

    // بناء الاستعلام الأساسي
    if ($user->checkTeams()) {
      $query = Teams::with('drivers');
    } else {
      $query = Teams::whereHas('users', function ($q) use ($user) {
        $q->where('user_id', $user->id);
      })->with('drivers');
    }

    // البحث
    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('name', 'ILIKE', '%' . $search . '%')
          ->orWhere('id', 'ILIKE', '%' . $search . '%');
      });
    }

    $query->orderBy('id', 'DESC');

    // تنفيذ pagination
    $teams = $query->paginate(9);

    // تعديل العناصر داخل الـ pagination باستخدام map
    $teams->getCollection()->transform(function ($team) {
      // إضافة البيانات المحقونة
      $team->driver_count = $team->drivers->count();
      $team->custom_label = "Team #" . $team->id;
      $team->can_wallet = auth()->user()->can('wallet_teams');
      $team->can_edit = auth()->user()->can('save_teams');
      $team->can_delete = auth()->user()->can('delete_teams');
      $team->can_view = auth()->user()->can('details_teams');

      // يمكنك إضافة أي بيانات أخرى هنا حسب الحاجة
      return $team;
    });

    return response()->json([
      'data' => $teams
    ]);
  }



  public function show($id)
  {
    return redirect()->route('teams.dashboard.index', $id);
  }

  /**
   * Team Dashboard - Main Overview Page
   */
  public function dashboard($id)
  {
    $user = auth()->user();
    if (!$user || !$user->checkTeam($id)) {
      abort(403);
    }
    $team = Teams::with(['drivers', 'tasks'])->findOrFail($id);

    $teamWallet = Team_Wallet::where('team_id', $team->id)->first();
    if (!$teamWallet) {
      $teamWallet = Team_Wallet::create(['team_id' => $team->id]);
    }

    // Calculate wallet totals
    $walletTotals = $team->walletTransactions()
      ->select('transaction_type', DB::raw('SUM(amount) as total_amount'))
      ->groupBy('transaction_type')
      ->pluck('total_amount', 'transaction_type');

    // Get statistics
    $stats = [
      'drivers_count' => $team->drivers()->count(),
      'active_drivers' => $team->drivers()->where('drivers.status', 'active')->count(),
      'tasks_count' => $team->tasks()->count(),
      'ongoing_tasks' => $team->tasks()->whereIn('tasks.status', ['assign', 'in_progress', 'started', 'in pickup point', 'loading', 'in the way', 'in delivery point', 'unloading', 'completed'])->where('tasks.closed', false)->count(),
      'completed_tasks' => $team->tasks()->where('tasks.closed', true)->count(),
      'wallet_balance' => $team->teamWalletTransactions->balance,
      'wallet_credit' => $team->teamWalletTransactions->credit ?? 0,
      'wallet_debit' => $team->teamWalletTransactions->debit ?? 0,
    ];

    return view('admin.teams.dashboard.index', compact('team', 'teamWallet', 'stats'));
  }

  /**
   * Team Drivers Management Page
   */
  public function driversPage($id)
  {
    $user = auth()->user();
    if (!$user || !$user->checkTeam($id)) {
      abort(403);
    }
    $team = Teams::with('drivers')->findOrFail($id);
    $templates = Form_Template::all();
    $roles = Role::where('guard_name', 'driver')->get();
    $vehicles = Vehicle::all();
    $driver_template = Settings::where('key', 'driver_template')->first();

    // Calculate drivers wallet statistics
    $driverIds = $team->drivers->pluck('id');

    $walletStats = [
      'total_credit' => 0,
      'total_debit' => 0,
      'net_balance' => 0
    ];

    if ($driverIds->isNotEmpty()) {
      // Get all driver wallets for this team using DB query
      $driverWallets = Wallet::whereIn('driver_id', $driverIds)->pluck('id');

      if ($driverWallets->isNotEmpty()) {
        // Calculate total credit
        $walletStats['total_credit'] = DB::table('wallet_transactions')
          ->whereIn('wallet_id', $driverWallets)
          ->where('transaction_type', 'credit')
          ->sum('amount');

        // Calculate total debit
        $walletStats['total_debit'] = DB::table('wallet_transactions')
          ->whereIn('wallet_id', $driverWallets)
          ->where('transaction_type', 'debit')
          ->sum('amount');

        // Calculate net balance (debit - credit)
        $walletStats['net_balance'] =  $walletStats['total_credit'] - $walletStats['total_debit'];
      }
    }

    return view('admin.teams.dashboard.drivers', compact('team', 'templates', 'roles', 'vehicles', 'driver_template', 'walletStats'));
  }

  /**
   * Team Tasks Management Page
   */
  public function tasksPage($id)
  {
    $user = auth()->user();
    if (!$user || !$user->checkTeam($id)) {
      abort(403);
    }
    $team = Teams::with('tasks')->findOrFail($id);

    return view('admin.teams.dashboard.tasks', compact('team'));
  }

  /**
   * Team Wallet Management Page
   */
  public function walletPage($id)
  {
    $user = auth()->user();
    if (!$user || !$user->checkTeam($id)) {
      abort(403);
    }
    $team = Teams::findOrFail($id);

    // Get or create team wallet
    $teamWallet = Team_Wallet::where('team_id', $team->id)->first();

    return view('admin.teams.dashboard.wallet', compact('team', 'teamWallet'));
  }

  /**
   * Team Task Distribution Page
   */
  public function taskDistributionPage($id)
  {
    $user = auth()->user();
    if (!$user || !$user->checkTeam($id)) {
      abort(403);
    }
    $team = Teams::with(['drivers' => function ($query) {
      $query->where('drivers.status', 'active');
    }])->findOrFail($id);

    return view('admin.teams.dashboard.task-distribution', compact('team'));
  }

  /**
   * Team Analytics Page
   */
  public function analyticsPage($id)
  {
    $user = auth()->user();
    if (!$user || !$user->checkTeam($id)) {
      abort(403);
    }
    $team = Teams::with(['drivers', 'tasks', 'geofences'])->findOrFail($id);

    // Get analytics data with PostgreSQL-compatible syntax
    $analytics = [
      'monthly_tasks' => $team->tasks()
        ->selectRaw('EXTRACT(MONTH FROM tasks.created_at) as month, COUNT(*) as count')
        ->whereRaw('EXTRACT(YEAR FROM tasks.created_at) = ?', [date('Y')])
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('count', 'month'),
      'task_status_distribution' => $team->tasks()
        ->selectRaw('tasks.status, COUNT(*) as count')
        ->groupBy('tasks.status')
        ->pluck('count', 'status'),
      'driver_performance' => $team->drivers()
        ->withCount(['tasks as completed_tasks' => function ($query) {
          $query->where('tasks.status', 'completed');
        }])
        ->withCount(['tasks as total_tasks'])
        ->get(),
      'revenue_data' => $this->getTeamWalletMonthlyRevenue($team),
      'daily_tasks' => $team->tasks()
        ->selectRaw('DATE(tasks.created_at) as date, COUNT(*) as count')
        ->where('tasks.created_at', '>=', now()->subDays(30))
        ->groupBy('date')
        ->orderBy('date')
        ->pluck('count', 'date'),
      'kpis' => [
        'total_tasks_this_month' => $team->tasks()
          ->whereRaw('EXTRACT(MONTH FROM tasks.created_at) = ?', [date('n')])
          ->whereRaw('EXTRACT(YEAR FROM tasks.created_at) = ?', [date('Y')])
          ->count(),
        'completed_tasks_this_month' => $team->tasks()
          ->where('tasks.status', 'completed')
          ->whereRaw('EXTRACT(MONTH FROM tasks.created_at) = ?', [date('n')])
          ->whereRaw('EXTRACT(YEAR FROM tasks.created_at) = ?', [date('Y')])
          ->count(),
        'total_revenue_this_month' => $this->getTeamWalletCurrentMonthRevenue($team),
        'avg_completion_time' => $team->tasks()
          ->where('tasks.status', 'completed')
          ->whereNotNull('tasks.completed_at')
          ->whereRaw('EXTRACT(MONTH FROM tasks.created_at) = ?', [date('n')])
          ->selectRaw('AVG(EXTRACT(EPOCH FROM (tasks.completed_at - tasks.created_at))/3600) as avg_hours')
          ->value('avg_hours') ?: 0,
      ],
      'previous_period_data' => [
        'total_tasks_last_month' => $team->tasks()
          ->whereRaw('EXTRACT(MONTH FROM tasks.created_at) = ?', [date('n') == 1 ? 12 : date('n') - 1])
          ->whereRaw('EXTRACT(YEAR FROM tasks.created_at) = ?', [date('n') == 1 ? date('Y') - 1 : date('Y')])
          ->count(),
        'completed_tasks_last_month' => $team->tasks()
          ->where('tasks.status', 'completed')
          ->whereRaw('EXTRACT(MONTH FROM tasks.created_at) = ?', [date('n') == 1 ? 12 : date('n') - 1])
          ->whereRaw('EXTRACT(YEAR FROM tasks.created_at) = ?', [date('n') == 1 ? date('Y') - 1 : date('Y')])
          ->count(),
        'active_drivers_last_month' => $team->drivers()
          ->where('status', 'active')
          ->whereRaw('EXTRACT(MONTH FROM drivers.created_at) <= ?', [date('n') == 1 ? 12 : date('n') - 1])
          ->whereRaw('EXTRACT(YEAR FROM drivers.created_at) <= ?', [date('n') == 1 ? date('Y') - 1 : date('Y')])
          ->count(),
        'total_revenue_last_month' => $this->getTeamWalletPreviousMonthRevenue($team),
      ]
    ];

    return view('admin.teams.dashboard.analytics', compact('team', 'analytics'));
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

    // $user = auth()->user();
    // if (!$user || !$user->checkDriver($team)) {
    //   return [];
    // }


    $totalData = Driver::where('team_id', $team)->count();
    $totalFiltered = $totalData;

    $query = Driver::where('team_id', $team);

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('drivers.id', 'LIKE', "%{$search}%")
          ->orWhere('drivers.name', 'LIKE', "%{$search}%")
          ->orWhere('drivers.username', 'LIKE', "%{$search}%")
          ->orWhere('drivers.email', 'LIKE', "%{$search}%")
          ->orWhere('drivers.phone', 'LIKE', "%{$search}%");
      });
    }
    if (!empty($statusFilter)) {
      $query->where('drivers.status', $statusFilter);
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
        'wallet'     => $val->wallet,
        'balance'     => $val->wallet->balance,
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
      $query->whereBetween('tasks.created_at', [
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
        $q->where('wallet_transactions.sequence', 'LIKE', "%{$search}%")->orWhere('wallet_transactions.description', 'LIKE', "%{$search}%");
        $q->orWhere('wallet_transactions.amount', 'LIKE', "%{$search}%");
      });
    }

    if (!empty($type) && $type != 'all') {
      $query->where('wallet_transactions.transaction_type', $type);
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
      'is_public' => 'nullable|boolean',
      'bank_name' => 'nullable|string|max:255',
      'custom_bank_name' => 'nullable|string|max:255',
      'account_number' => 'nullable|string|max:255',
      'iban_number' => 'nullable|string|max:255',
      'bic_code' => 'nullable|string|max:255',
      'beneficiary_name' => 'nullable|string|max:255',
      'bank_address1' => 'nullable|string|max:255',
      'bank_address2' => 'nullable|string|max:255',
      'bank_city' => 'nullable|string|max:255',
      'bank_country' => 'nullable|string|max:255',

    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    DB::beginTransaction();
    try {
      if (isset($req->id) && !empty($req->id)) {
        $user = auth()->user();
        if (!$user || !$user->checkTeam($req->id)) {
          return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
        }
        $done = Teams::find($req->id)->update([
          'name' => $req->name,
          'address' => $req->address,
          'team_commission_type' =>   $req->commission_type,
          'team_commission_value' =>  $req->commission,
          'location_update_interval' => $req->location_update,
          'note' =>  $req->note,
          'is_public' => $req->is_public ?? false,
          'bank_name' => $req->bank_name === 'other' ? $req->custom_bank_name : $req->bank_name,
          'account_number' => $req->account_number,
          'iban_number' => $req->iban_number,
          'bic_code' => $req->bic_code,
          'beneficiary_name' => $req->beneficiary_name,
          'bank_address1' => $req->bank_address1,
          'bank_address2' => $req->bank_address2,
          'bank_city' => $req->bank_city,
          'bank_country' => $req->bank_country,
        ]);
      } else {

        $done = Teams::create([
          'name' => $req->name,
          'address' => $req->address,
          'team_commission_type' =>   $req->commission_type,
          'team_commission_value' =>  $req->commission,
          'location_update_interval' => $req->location_update ?? 30,
          'note' =>  $req->note,
          'is_public' => $req->is_public ?? false,
          'bank_name' => $req->bank_name === 'other' ? $req->custom_bank_name : $req->bank_name,
          'account_number' => $req->account_number,
          'iban_number' => $req->iban_number,
          'bic_code' => $req->bic_code,
          'beneficiary_name' => $req->beneficiary_name,
          'bank_address1' => $req->bank_address1,
          'bank_address2' => $req->bank_address2,
          'bank_city' => $req->bank_city,
          'bank_country' => $req->bank_country,
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

    // تأكد من إرجاع is_public كـ boolean صريح
    $teamData = $team->toArray();
    $teamData['is_public'] = (bool) $team->is_public;

    return response()->json($teamData);
  }


  public function destroy(Request $req)
  {
    $user = auth()->user();
    if (!$user || !$user->checkTeam($req->id)) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
    }
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

  public function processTeamPayment(Request $request, $teamId)
  {
    $validator = Validator::make($request->all(), [
      'team_id' => 'required|exists:teams,id',
      'total_amount' => 'required|numeric|min:0.01',
      'transactions' => 'required|array|min:1',
      'transactions.*.id' => 'required|exists:wallet_transactions,id',
      'transactions.*.original_amount' => 'required|numeric|min:0',
      'transactions.*.payment_amount' => 'required|numeric|min:0',
      'notes' => 'nullable|string|max:1000'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $team = Teams::find($teamId);
    if (!$team) {
      return response()->json([
        'success' => false,
        'message' => 'Team not found'
      ], 404);
    }

    DB::beginTransaction();
    try {
      $transactionIds = collect($request->transactions)->pluck('id')->toArray();

      // Verify all transactions belong to this team and are unpaid
      $walletTransactions = Wallet_Transaction::whereIn('id', $transactionIds)
        ->whereIn('wallet_id', function ($query) use ($teamId) {
          $query->select('id')
            ->from('wallets')
            ->whereIn('driver_id', function ($query) use ($teamId) {
              $query->select('id')
                ->from('drivers')
                ->where('team_id', $teamId);
            });
        })
        ->where('wallet_transactions.status', 0) // Only unpaid transactions
        ->get();

      if ($walletTransactions->count() !== count($transactionIds)) {
        throw new \Exception('Some transactions are invalid or already paid');
      }

      // Apply sequential distribution like frontend
      $remainingAmount = $request->total_amount;
      $processedTransactions = [];

      // Sort transactions by the order they were sent (to match frontend logic)
      $sortedTransactions = collect($request->transactions)->sortBy(function ($item, $key) {
        return $key; // Maintain original order
      });

      foreach ($sortedTransactions as $transactionData) {
        if ($remainingAmount <= 0) {
          break; // No more money to distribute
        }

        $walletTransaction = $walletTransactions->where('id', $transactionData['id'])->first();

        if (!$walletTransaction) {
          throw new \Exception("Transaction {$transactionData['id']} not found");
        }

        $originalAmount = $walletTransaction->amount;
        $paymentAmount = 0;
        $paymentStatus = 'unpaid';

        // Sequential allocation logic (same as frontend)
        if ($remainingAmount >= $originalAmount) {
          // Full payment
          $paymentAmount = $originalAmount;
          $remainingAmount -= $originalAmount;
          $paymentStatus = 'full';

          $walletTransaction->update([
            'status' => 1,
            'user_id' => auth()->id()
          ]);

          $paymentDescription = "دفعة فريق (كاملة) للمعاملة رقم #{$walletTransaction->sequence}";
        } else if ($remainingAmount > 0) {
          // Partial payment
          $paymentAmount = $remainingAmount;
          $remainingTransactionAmount = $originalAmount - $paymentAmount;
          $remainingAmount = 0;
          $paymentStatus = 'partial';

          // Update original transaction to paid amount
          $walletTransaction->update([
            'status' => 1,
            'amount' => $paymentAmount,
            'user_id' => auth()->id()
          ]);

          // Create new transaction for remaining amount with clear description
          Wallet_Transaction::create([
            'wallet_id' => $walletTransaction->wallet_id,
            'amount' => $remainingTransactionAmount,
            'transaction_type' => $walletTransaction->transaction_type,
            'description' => "المبلغ المتبقي من المعاملة #{$walletTransaction->sequence} - تم دفع {$paymentAmount} من أصل {$originalAmount} ريال",
            'status' => 0,
            'user_id' => auth()->id(),
            'maturity_time' => $walletTransaction->maturity_time,
            'task_id' => $walletTransaction->task_id,
            'image' => $walletTransaction->image
          ]);

          $paymentDescription = "دفعة فريق (جزئية: {$paymentAmount} من {$originalAmount}) للمعاملة رقم #{$walletTransaction->sequence}";
        }

        // Only create payment record if there's an actual payment
        if ($paymentAmount > 0) {
          // Create debit transaction (money owed by driver, not to driver)
          Wallet_Transaction::create([
            'wallet_id' => $walletTransaction->wallet_id,
            'amount' => $paymentAmount,
            'transaction_type' => 'debit', // Changed from 'credit' to 'debit'
            'description' => $paymentDescription . ($request->notes ? " - ملاحظات: {$request->notes}" : ""),
            'status' => 1,
            'user_id' => auth()->id(),
            'maturity_time' => now()
          ]);

          $processedTransactions[] = [
            'id' => $walletTransaction->id,
            'original_amount' => $originalAmount,
            'payment_amount' => $paymentAmount,
            'status' => $paymentStatus
          ];
        }
      }

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Payment processed successfully',
        'data' => [
          'processed_transactions' => $processedTransactions,
          'total_amount' => $request->total_amount,
          'transactions_count' => count($processedTransactions)
        ]
      ]);
    } catch (\Exception $e) {
      DB::rollback();

      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get filtered tasks for a team based on specific requirements
   *
   * @param int $teamId
   * @return JsonResponse
   */
  public function getFilteredTasks($teamId): JsonResponse
  {
    try {
      $team = Teams::with(['drivers', 'geofences.geofence'])->findOrFail($teamId);

      $vehicleSizeIds = $team->drivers->pluck('vehicle_size_id')->filter()->unique()->toArray();

      if (empty($vehicleSizeIds)) {
        return response()->json([
          'success' => true,
          'data' => [],
          'message' => 'No drivers with vehicle sizes found for this team'
        ]);
      }

      $tasksQuery = Task::with(['pickup', 'vehicle_size'])
        ->where('status', 'in_progress')
        ->whereIn('vehicle_size_id', $vehicleSizeIds)
        ->orderBy('created_at', 'desc');

      $tasks = $tasksQuery->get();

      $teamGeofences = $team->geofences;

      if ($teamGeofences->isNotEmpty()) {
        $geofences = $teamGeofences->pluck('geofence')->filter();

        // دالة لمساعدة التحقق من وجود نقطة داخل أي geofence
        $isPointInGeofence = function ($latitude, $longitude) use ($geofences) {
          foreach ($geofences as $geofence) {
            // هنا يفترض أنك تستخدم مكتبة الجغرافيا للتحقق (مثل grimzy/laravel-mysql-spatial أو غيرها)
            // أو تستدعي الدالة المناسبة لـ PostGIS أو PHP
            if ($geofence->containsPoint($latitude, $longitude)) {
              return true;
            }
          }
          return false;
        };

        // فرز المهام بحيث تكون المهام التي نقطتها داخل geofence في الأعلى
        $tasks = $tasks->sortByDesc(function ($task) use ($isPointInGeofence) {
          $pickup = $task->pickup;
          if (!$pickup || !$pickup->latitude || !$pickup->longitude) {
            return false;
          }
          return $isPointInGeofence($pickup->latitude, $pickup->longitude);
        });
      }

      $transformedTasks = $tasks->map(function ($task) {
        return [
          'id' => $task->id,
          'status' => $task->status,
          'vehicle_size_id' => $task->vehicle_size_id,
          'vehicle_size_name' => ($task->vehicle_size->type->vehicle->name . ' - ' . $task->vehicle_size->type->name . ' - (' . $task->vehicle_size->name . ')') ?? null,
          'pickup_location' => [
            'latitude' => $task->pickup->latitude ?? null,
            'longitude' => $task->pickup->longitude ?? null,
            'address' => $task->pickup->address ?? null,
          ],
          'delivery_location' => [
            'latitude' => $task->delivery->latitude ?? null,
            'longitude' => $task->delivery->longitude ?? null,
            'address' => $task->delivery->address ?? null,
          ],
          'total_price' => $task->total_price,
          'created_at' => $task->created_at,
          'additional_data' => $task->additional_data,
        ];
      });

      return response()->json([
        'success' => true,
        'data' => $transformedTasks->values(),
        'team_info' => [
          'id' => $team->id,
          'name' => $team->name,
          'has_geofences' => $teamGeofences->isNotEmpty(),
          'geofence_count' => $teamGeofences->count(),
          'driver_vehicle_sizes' => $vehicleSizeIds,
        ],
        'total_tasks' => $transformedTasks->count(),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error retrieving filtered tasks: ' . $e->getMessage()
      ], 500);
    }
  }


  /**
   * Get team revenue monthly data for charts (total_price - commission)
   */
  private function getTeamWalletMonthlyRevenue($team)
  {
    // Calculate revenue from completed tasks (total_price - commission)
    return $team->tasks()
      ->selectRaw('EXTRACT(MONTH FROM tasks.created_at) as month, SUM(COALESCE(tasks.total_price, 0) - COALESCE(tasks.commission, 0)) as revenue')
      ->whereRaw('EXTRACT(YEAR FROM tasks.created_at) = ?', [date('Y')])
      ->where('tasks.status', 'completed')
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('revenue', 'month');
  }

  /**
   * Get team current month revenue (total_price - commission)
   */
  private function getTeamWalletCurrentMonthRevenue($team)
  {
    // Calculate current month revenue from completed tasks (total_price - commission)
    return $team->tasks()
      ->where('tasks.status', 'completed')
      ->whereRaw('EXTRACT(MONTH FROM tasks.created_at) = ?', [date('n')])
      ->whereRaw('EXTRACT(YEAR FROM tasks.created_at) = ?', [date('Y')])
      ->selectRaw('SUM(COALESCE(tasks.total_price, 0) - COALESCE(tasks.commission, 0)) as net_revenue')
      ->value('net_revenue') ?: 0;
  }

  /**
   * Get team previous month revenue (total_price - commission)
   */
  private function getTeamWalletPreviousMonthRevenue($team)
  {
    // Calculate previous month and year
    $previousMonth = date('n') == 1 ? 12 : date('n') - 1;
    $previousYear = date('n') == 1 ? date('Y') - 1 : date('Y');

    // Calculate previous month revenue from completed tasks (total_price - commission)
    return $team->tasks()
      ->where('tasks.status', 'completed')
      ->whereRaw('EXTRACT(MONTH FROM tasks.created_at) = ?', [$previousMonth])
      ->whereRaw('EXTRACT(YEAR FROM tasks.created_at) = ?', [$previousYear])
      ->selectRaw('SUM(COALESCE(tasks.total_price, 0) - COALESCE(tasks.commission, 0)) as net_revenue')
      ->value('net_revenue') ?: 0;
  }

  /**
   * Get analytics data with date range filter
   */
  public function getAnalyticsData(Request $request, $teamId)
  {
    $team = Teams::with(['drivers', 'tasks', 'geofences'])->findOrFail($teamId);

    // Get date range from request or default to current month
    $startDate = $request->input('start_date', date('Y-m-01'));
    $endDate = $request->input('end_date', date('Y-m-t'));
    $metricType = $request->input('metric_type', 'monthly');
    $groupBy = $request->input('group_by', 'month');

    // Build analytics data based on filters
    $analytics = $this->buildFilteredAnalytics($team, $startDate, $endDate, $metricType, $groupBy);

    return response()->json([
      'success' => true,
      'data' => $analytics
    ]);
  }

  /**
   * Build filtered analytics data
   */
  private function buildFilteredAnalytics($team, $startDate, $endDate, $metricType, $groupBy)
  {
    $analytics = [];

    // Tasks data based on grouping
    if ($groupBy === 'month') {
      $analytics['monthly_tasks'] = $team->tasks()
        ->selectRaw('EXTRACT(MONTH FROM tasks.created_at) as month, COUNT(*) as count')
        ->whereBetween('tasks.created_at', [$startDate, $endDate])
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('count', 'month');
    } else {
      $analytics['daily_tasks'] = $team->tasks()
        ->selectRaw('DATE(tasks.created_at) as date, COUNT(*) as count')
        ->whereBetween('tasks.created_at', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date')
        ->pluck('count', 'date');
    }

    // Task status distribution
    $analytics['task_status_distribution'] = $team->tasks()
      ->selectRaw('tasks.status, COUNT(*) as count')
      ->whereBetween('tasks.created_at', [$startDate, $endDate])
      ->groupBy('tasks.status')
      ->pluck('count', 'status');

    // Revenue data (total_price - commission)
    if ($groupBy === 'month') {
      $analytics['revenue_data'] = $team->tasks()
        ->selectRaw('EXTRACT(MONTH FROM tasks.created_at) as month, SUM(COALESCE(tasks.total_price, 0) - COALESCE(tasks.commission, 0)) as revenue')
        ->where('tasks.status', 'completed')
        ->whereBetween('tasks.created_at', [$startDate, $endDate])
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('revenue', 'month');
    } else {
      $analytics['revenue_data'] = $team->tasks()
        ->selectRaw('DATE(tasks.created_at) as date, SUM(COALESCE(tasks.total_price, 0) - COALESCE(tasks.commission, 0)) as revenue')
        ->where('tasks.status', 'completed')
        ->whereBetween('tasks.created_at', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date')
        ->pluck('revenue', 'date');
    }

    // Driver performance
    $analytics['driver_performance'] = $team->drivers()
      ->withCount(['tasks as completed_tasks' => function ($query) use ($startDate, $endDate) {
        $query->where('tasks.status', 'completed')
          ->whereBetween('tasks.created_at', [$startDate, $endDate]);
      }])
      ->withCount(['tasks as total_tasks' => function ($query) use ($startDate, $endDate) {
        $query->whereBetween('tasks.created_at', [$startDate, $endDate]);
      }])
      ->get();

    // KPIs for the filtered period
    $analytics['kpis'] = [
      'total_tasks' => $team->tasks()
        ->whereBetween('tasks.created_at', [$startDate, $endDate])
        ->count(),
      'completed_tasks' => $team->tasks()
        ->where('tasks.status', 'completed')
        ->whereBetween('tasks.created_at', [$startDate, $endDate])
        ->count(),
      'total_revenue' => $team->tasks()
        ->where('tasks.status', 'completed')
        ->whereBetween('tasks.created_at', [$startDate, $endDate])
        ->selectRaw('SUM(COALESCE(tasks.total_price, 0) - COALESCE(tasks.commission, 0)) as net_revenue')
        ->value('net_revenue') ?: 0,
      'active_drivers' => $team->drivers->where('status', 'active')->count(),
    ];

    return $analytics;
  }
}
