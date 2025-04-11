<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\Wallet;
use Mockery\Expectation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Wallet_Transaction;
use Illuminate\Support\Facades\Validator;

class WalletsController extends Controller
{
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
      4 => 'status',
      5 => 'preview',
      6 => 'last_transaction',
      7 => 'created_at',
      8 => 'type'

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
        'status'     => $val->status,
        'preview'    => $val->preview,
        'created_at' => $val->created_at->format('Y-m-d H:i'),
        'last_transaction'    => $val->status,
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


  public function change_state(Request $req)
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
      $wallet->balance = 0;
      $wallet->status = $status;
      $wallet->preview = 0;
      $wallet->save();
      return true;
    } catch (Exception $ex) {
      return false;
    }
  }

  public function show($id)
  {
    $data = Wallet::findOrFail($id);
    return view('admin.wallets.show', compact('data'));
  }

  public function getDataTransactions(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'amount',
      3 => 'type',
      4 => 'description',
      5 => 'maturity',
      6 => 'task',
      7 => 'user',
      8 => 'created_at',
    ];

    $totalData = Wallet_Transaction::count();
    $totalFiltered = $totalData;

    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';

    $search = $request->input('search');
    $type = $request->input('status');
    $wallet = $request->input('wallet');


    $query = Wallet_Transaction::query();
    $query->where('wallet_id', $wallet);


    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%");
      });
    }
    if (!empty($type)) {
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
        'maturity'    => $val->maturity_time,
        'user'    => $val->user->name,
        'task'    => $val->user->id,
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
}
