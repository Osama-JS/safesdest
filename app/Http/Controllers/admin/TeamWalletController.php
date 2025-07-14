<?php

namespace App\Http\Controllers\admin;

use Exception;
use Carbon\Carbon;
use App\Models\Team;
use App\Models\Team_Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Team_Wallet_Transaction;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FunctionsController;
use App\Models\Teams;

class TeamWalletController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:wallet_teams', ['only' => ['index']]);
    $this->middleware('permission:wallet_mange_teams', ['only' => ['editTransaction', 'storeTransaction', 'destroy']]);
  }

  public function index($id, $name)
  {
    $team = Teams::find($id);
    if (!$team) {
      abort(404);
    }
    $data = Team_Wallet::where('team_id', $team->id)->first();
    if (!$data) {
      $data = new Team_Wallet();
      $data->team_id = $id;
      $data->save();
    }
    return view('admin.teams.wallets.index', compact('data'));
  }

  public function getDataTransactions(Request $request)
  {

    $columns = [
      1 => 'id',
      2 => 'amount',
      3 => 'description',
      4 => 'maturity',
      5 => 'task',
      6 => 'user',
      7 => 'created_at',
    ];

    $wallet = $request->input('wallet');
    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');
    $search = $request->input('search');
    $type = $request->input('status');


    $totalData = Team_Wallet_Transaction::where('team_wallet_id', $wallet)->count();
    $totalFiltered = $totalData;

    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';


    $query = Team_Wallet_Transaction::query();
    $query->where('team_wallet_id', $wallet);

    if ($fromDate && $toDate) {
      $query->whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
      ]);
    }


    if (!empty($search->value)) {
      $query->where(function ($q) use ($search) {
        $q->where('sequence', 'LIKE', "%" . $search . "%")->orWhere('description', 'LIKE', "%" . $search . "%");
        $q->orWhere('amount', 'LIKE', "%" . $search . "%");
      });
    }

    if (!empty($type) && $type != 'all') {
      $query->where('transaction_type', $type);
    }

    $totalFiltered = $query->count();
    $wallets = $query
      ->with(['user', 'task']) // Eager load relationships
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
        'amount'     => (float) $val->amount,
        'type'       => $val->transaction_type,
        'description' => $val->description ?? '',
        'maturity'    => $val->maturity_time ? $val->maturity_time : '',
        'user'        => $val->user ? $val->user->name : 'automatic',
        'task'        => $val->task_id ? $val->task_id : '',
        'image'       => $val->image ?? '',
        'sequence'    => $val->sequence,
        'status'      => (int) $val->status, // Ensure it's integer
        'created_at'  => $val->created_at->format('Y-m-d H:i'),
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

  public function editTransaction($id)
  {
    $data = Team_Wallet_Transaction::findOrFail($id);
    if (!$data) {
      return response()->json(['status' => 2, 'error' => __('Can not find the selected Transaction')]);
    }
    return response()->json(['status' => 1, 'data' => $data]);
  }

  public function storeTransaction(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'amount' => 'required|numeric|min:0.01|gt:0',
      'description' => 'required|string|max:255',
      'type' => 'required|in:credit,debit',
      'wallet' => 'required|exists:team_wallet,id',
      'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
      'maturity' => 'nullable|date',
      'task_id' => 'nullable|exists:tasks,id',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }

    try {
      $wallet = Team_Wallet::findOrFail($req->wallet);
      $adjustedBalance = $wallet->balance;
      $existingTransaction = null;

      // التعديل على معاملة سابقة
      if ($req->filled('id')) {
        $existingTransaction = Team_Wallet_Transaction::findOrFail($req->id);

        // إرجاع المبلغ القديم للحساب
        if ($existingTransaction->transaction_type === 'credit') {
          $adjustedBalance -= $existingTransaction->amount;
        } elseif ($existingTransaction->transaction_type === 'debit') {
          $adjustedBalance += $existingTransaction->amount;
        }
      }



      DB::beginTransaction();

      $data = [
        'amount' => $req->amount,
        'description' => $req->description,
        'transaction_type' => $req->type,
        'maturity_time' => $req->type === 'credit' ? null : $req->maturity,
      ];

      if ($req->hasFile('image')) {
        $data['image'] = (new FunctionsController)->convert($req->image, 'wallets/team/transactions');
      }

      $oldImage = null;

      if ($existingTransaction) {
        $user = Auth::user();

        if ($existingTransaction->task_id && $user->role_id !== 1) {
          DB::rollBack();
          return response()->json([
            'status' => 2,
            'error'  => __('You can not edit this transaction')
          ]);
        }


        if ($req->hasFile('image') && $existingTransaction->image) {
          $oldImage = $existingTransaction->image;
        }

        $existingTransaction->update($data);
      } else {
        $data['team_wallet_id'] = $req->wallet;
        $data['user_id'] = auth()->id();
        $data['task_id'] = $req->task_id;
        Team_Wallet_Transaction::create($data);
      }

      if ($oldImage && file_exists($oldImage)) {
        unlink($oldImage);
      }

      DB::commit();

      return response()->json(['status' => 1, 'success' => __('Transaction Saved successfully')]);
    } catch (\Exception $ex) {
      DB::rollBack();

      return response()->json([
        'status' => 2,
        'error'  => __('Error creating transaction: ') . $ex->getMessage()
      ]);
    }
  }


  public function destroy(Request $req)
  {
    DB::beginTransaction();
    try {
      $find = Team_Wallet_Transaction::findOrFail($req->id);
      if ($find->task_id) {
        return response()->json([
          'status' => 2,
          'error'  => __('You can not delete this transaction')
        ]);
      }
      $oldImage = null;
      if ($find->image) {
        $oldImage = $find->image;
      }
      $done = $find->delete();
      if ($oldImage) {
        unlink($oldImage);
      }

      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Transaction']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Transaction deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
