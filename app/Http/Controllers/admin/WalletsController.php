<?php

namespace App\Http\Controllers\admin;

use Exception;
use Carbon\Carbon;
use App\Models\Wallet;
use Mockery\Expectation;
use Illuminate\Http\Request;
use App\Models\Wallet_Transaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Validator;

class WalletsController extends Controller
{


  public function __construct()
  {
    $this->middleware('permission:view_wallets', ['only' => ['index', 'getData']]);
    $this->middleware('permission:save_wallets', ['only' => ['update']]);
    $this->middleware('permission:details_wallets', ['only' => ['show', 'getDataTransactions']]);
    $this->middleware('permission:transaction_wallets', ['only' => ['storeTransaction', 'editTransaction', 'destroy']]);
  }

  public function index()
  {
    return view('admin.wallets.index');
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'balance',
      4 => 'debt_ceiling',
      5 => 'status',
      6 => 'preview',
      7 => 'last_transaction',
      8 => 'created_at',
      9 => 'type'

    ];

    $totalData = Wallet::count();
    $totalFiltered = $totalData;

    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';

    $search = $request->input('search');
    $type = $request->input('status') ?? 'customer';

    $query = Wallet::query();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%");
      });
    }
    if (!empty($type)) {

      $query->where('user_type', $type);
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
        'name'       => "[ " . $val->id . " ] " . ($val->customer_id ? $val->customer->name : ($val->driver_id ? $val->driver->name : 'N/A')),
        'type'       => $val->user_type,
        'balance'       => $val->balance,
        'debt_ceiling'       => $val->debt_ceiling,
        'status'     => $val->status,
        'preview'    => $val->preview,
        'created_at' => $val->created_at->format('Y-m-d H:i'),
        'last_transaction'    => $val->last_transaction  ?? __('No Transaction'),
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


  public function chang_status(Request $req)
  {
    $find = Wallet::findOrFail($req->id);
    if (!$find) {
      return response()->json(['status' => 2, 'error' => __('Wallet not found')]);
    }
    $status = $find->status == 1 ? 0 : 1;
    $done = $find->update([
      'status' => $status,
    ]);
    if (!$done) {
      return response()->json(['status' => 2, 'error' => __('Error to change Wallet status')]);
    }
    return response()->json(['status' => 1, 'success' => $status]);
  }

  public function change_preview(Request $req)
  {
    $find = Wallet::findOrFail($req->id);
    if (!$find) {
      return response()->json(['status' => 2, 'error' => __('Wallet not found')]);
    }
    $preview = $find->preview == 1 ? 0 : 1;
    $done = $find->update([
      'preview' => $preview,
    ]);
    if (!$done) {
      return response()->json(['status' => 2, 'error' => __('Error to change Wallet preview')]);
    }
    return response()->json(['status' => 1, 'success' => $preview]);
  }

  public function store($type, $id, $status = true)
  {
    try {
      $type = strtolower($type);
      $wallet = Wallet::where('user_type', $type)->where('customer_id', $id)->orWhere('driver_id', $id)->first();
      if ($wallet) {
        return false;
      }
      $wallet = new Wallet();
      $wallet->user_type = $type;
      $wallet->customer_id = $type == 'customer' ? $id : null;
      $wallet->driver_id = $type == 'driver' ? $id : null;
      $wallet->status = $status;
      $wallet->preview = 0;
      $wallet->save();
      return true;
    } catch (Exception $ex) {
      return  false;
    }
  }


  public function update(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:wallets,id',
      'debt' => 'required|numeric',

    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'type' => 'error', 'message' => $req->id]);
    }

    try {
      $done = Wallet::find($req->id)->update([
        'debt_ceiling' => $req->debt,
      ]);

      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => __('error to Update Debt Ceiling')]);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => __('Debt Ceiling Updated')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
    }
  }

  public function show($id, $name)
  {
    $data = Wallet::findOrFail($id);
    return view('admin.wallets.show', compact('data'));
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

    $totalData = Wallet_Transaction::where('wallet_id', $wallet)->count();
    $totalFiltered = $totalData;

    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';


    $query = Wallet_Transaction::query();
    $query->where('wallet_id', $wallet);

    if ($fromDate && $toDate) {
      $query->whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
      ]);
    }

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
        'type'       => $val->transaction_type,
        'description'     => $val->description,
        'maturity'    => $val->maturity_time ?? '',
        'user'    => $val->user->name ?? 'automatic',
        'task'    => $val->task_id ?? '',
        'image'   => $val->image,
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

  public function editTransaction($id)
  {
    $data = Wallet_Transaction::findOrFail($id);
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
      'wallet' => 'required|exists:wallets,id',
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
      $done = null;
      $wallet = Wallet::findOrFail($req->wallet);
      $existingTransaction = null;
      $adjustedBalance = $wallet->balance;

      if ($req->filled('id')) {
        $existingTransaction = Wallet_Transaction::findOrFail($req->id);
        if ($existingTransaction->transaction_type === 'credit') {
          $adjustedBalance -= $existingTransaction->amount;
        } elseif ($existingTransaction->transaction_type === 'debit') {
          $adjustedBalance += $existingTransaction->amount;
        }
      }

      if ($req->type === 'credit') {
        $adjustedBalance += $req->amount;
      } elseif ($req->type === 'debit') {
        $adjustedBalance -= $req->amount;
      }

      if ($adjustedBalance < -$wallet->debt_ceiling) {
        return response()->json([
          'status' => 2,
          'error'  => __('The amount exceeds the debt ceiling')
        ]);
      }



      DB::transaction(function () use ($req, &$done) {
        $data = [
          'amount'              => $req->amount,
          'description'         => $req->description,
          'transaction_type'    => $req->type,
          'maturity_time'       => $req->maturity,
        ];

        if ($req->hasFile('image')) {
          $data['image'] = (new FunctionsController)->convert($req->image, 'wallets/transactions');
        }
        $oldImage = null;

        if ($req->filled('id')) {
          $find = Wallet_Transaction::findOrFail($req->id);
          if ($req->type === 'credit') {
            $data['maturity_time'] = null;
          }
          if ($find->task_id) {
            return response()->json([
              'status' => 2,
              'error'  => __('You can not edit this transaction')
            ]);
          }
          if ($req->hasFile('image') && $find->image) {
            $oldImage = $find->image;
          }
          $done = $find->update($data);
        } else {
          $data['wallet_id'] = $req->wallet;
          $data['user_id'] = auth()->id();
          $data['task_id'] = $req->task_id;
          $done = Wallet_Transaction::create($data);
        }
        if ($oldImage) {
          unlink($oldImage);
        }
      });


      return response()->json(['status' => 1, 'success' => __('Transaction created successfully')]);
    } catch (Exception $ex) {
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
      $find = Wallet_Transaction::findOrFail($req->id);
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
