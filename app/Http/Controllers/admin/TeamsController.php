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
    $this->middleware('permission:details_teams', ['only' => ['']]);
  }


  public function index()
  {
    return view('admin.teams.index');
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
    return redirect()->route('teams.dashboard.index', $id);
  }

  /**
   * Team Dashboard - Main Overview Page
   */
  public function dashboard($id)
  {
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
    $team = Teams::with('tasks')->findOrFail($id);

    return view('admin.teams.dashboard.tasks', compact('team'));
  }

  /**
   * Team Wallet Management Page
   */
  public function walletPage($id)
  {
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
    $team = Teams::with(['drivers', 'tasks'])->findOrFail($id);

    // Get analytics data
    $analytics = [
      'monthly_tasks' => $team->tasks()
        ->selectRaw('MONTH(tasks.created_at) as month, COUNT(*) as count')
        ->whereYear('tasks.created_at', date('Y'))
        ->groupBy('month')
        ->pluck('count', 'month'),
      'task_status_distribution' => $team->tasks()
        ->selectRaw('tasks.status, COUNT(*) as count')
        ->groupBy('tasks.status')
        ->pluck('count', 'status'),
      'driver_performance' => $team->drivers()
        ->withCount(['tasks as completed_tasks' => function ($query) {
          $query->where('tasks.status', 'completed');
        }])
        ->get(),
    ];

    return view('admin.teams.dashboard.analytics', compact('team', 'analytics'));
  }

  /**
   * Get team wallet transactions data for DataTables
   */
  public function getWalletTransactions(Request $request)
  {
    $wallet = Team_Wallet::where('team_id', $request->wallet)->first();

    if (!$wallet) {
      return response()->json(['data' => []]);
    }

    $query = Wallet_Transaction::where('wallet_id', $wallet->id)
      ->with(['task', 'user']);

    // Apply filters
    if ($request->filled('status')) {
      $query->where('transaction_type', $request->status);
    }

    if ($request->filled('from_date') && $request->filled('to_date')) {
      $query->whereBetween('wallet_transactions.created_at', [$request->from_date, $request->to_date]);
    }

    if ($request->filled('min_amount')) {
      $query->where('wallet_transactions.amount', '>=', $request->min_amount);
    }

    if ($request->filled('max_amount')) {
      $query->where('wallet_transactions.amount', '<=', $request->max_amount);
    }

    $transactions = $query->orderBy('wallet_transactions.created_at', 'desc')->get();

    $data = $transactions->map(function ($transaction) {
      return [
        'id' => $transaction->id,
        'sequence' => $transaction->id,
        'amount' => number_format($transaction->amount, 2),
        'transaction_type' => $transaction->transaction_type,
        'description' => $transaction->description,
        'maturity_time' => $transaction->maturity_time ? $transaction->maturity_time->format('Y-m-d H:i') : null,
        'task' => $transaction->task ? [
          'id' => $transaction->task->id,
          'title' => $transaction->task->title ?? 'Task #' . $transaction->task->id
        ] : null,
        'task_id' => $transaction->task_id,
        'user' => $transaction->user ? [
          'name' => $transaction->user->name
        ] : null,
        'image' => $transaction->image,
        'created_at' => $transaction->created_at->format('Y-m-d H:i'),
        'checkbox' => $transaction->transaction_type === 'debit' && !$transaction->task_id
      ];
    });

    return response()->json(['data' => $data]);
  }

  /**
   * Store wallet transaction
   */
  public function storeWalletTransaction(Request $request)
  {
    try {
      $validatedData = $request->validate([
        'wallet' => 'required|exists:team_wallets,id',
        'amount' => 'required|numeric|min:0',
        'type' => 'required|in:credit,debit',
        'description' => 'required|string|max:255',
        'maturity' => 'nullable|date',
        'image' => 'nullable|image|max:2048'
      ]);

      $imagePath = null;
      if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('wallet_transactions', 'public');
      }

      $transaction = Wallet_Transaction::create([
        'wallet_id' => $validatedData['wallet'],
        'amount' => $validatedData['amount'],
        'transaction_type' => $validatedData['type'],
        'description' => $validatedData['description'],
        'maturity_time' => $validatedData['maturity'],
        'image' => $imagePath,
        'user_id' => auth()->id(),
        'status' => 1
      ]);

      return response()->json([
        'status' => 1,
        'success' => 'Transaction created successfully',
        'data' => $transaction
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 0,
        'error' => 'Failed to create transaction: ' . $e->getMessage()
      ]);
    }
  }

  /**
   * Edit wallet transaction
   */
  public function editWalletTransaction($id)
  {
    try {
      $transaction = Wallet_Transaction::findOrFail($id);

      return response()->json([
        'status' => 1,
        'data' => [
          'id' => $transaction->id,
          'amount' => $transaction->amount,
          'transaction_type' => $transaction->transaction_type,
          'description' => $transaction->description,
          'maturity_time' => $transaction->maturity_time ? $transaction->maturity_time->format('Y-m-d\TH:i') : null,
          'image' => $transaction->image ? asset('storage/' . $transaction->image) : null
        ]
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 0,
        'error' => 'Transaction not found'
      ]);
    }
  }

  /**
   * Delete wallet transaction
   */
  public function deleteWalletTransaction($id)
  {
    try {
      $transaction = Wallet_Transaction::findOrFail($id);

      // Don't allow deletion of transactions linked to tasks
      if ($transaction->task_id) {
        return response()->json([
          'status' => 0,
          'error' => 'Cannot delete transaction linked to a task'
        ]);
      }

      // Delete image if exists
      if ($transaction->image && Storage::disk('public')->exists($transaction->image)) {
        Storage::disk('public')->delete($transaction->image);
      }

      $transaction->delete();

      return response()->json([
        'status' => 1,
        'success' => 'Transaction deleted successfully'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 0,
        'error' => 'Failed to delete transaction'
      ]);
    }
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
    if (!$user || !$user->checkDriver($team)) {
      return [];
    }


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
          'note' =>  $req->note,
          'is_public' => $req->is_public ?? false
        ]);
      } else {

        $done = Teams::create([
          'name' => $req->name,
          'address' => $req->address,
          'team_commission_type' =>   $req->commission_type,
          'team_commission_value' =>  $req->commission,
          'location_update_interval' => $req->location_update ?? 30,
          'note' =>  $req->note,
          'is_public' => $req->is_public ?? false
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
}
