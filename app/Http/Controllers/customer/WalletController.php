<?php

namespace App\Http\Controllers\customer;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
  public function index()
  {
    $data = Wallet::where('customer_id', Auth::user()->id)->first();



    return view('customers.wallet.index', compact('data'));
  }

  public function getData(Request $request)
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


    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');
    $search = $request->input('search');
    $type = $request->input('status');

    $wallet = Wallet::where('customer_id', Auth::user()->id)->first();



    if (!$wallet) {
      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'code'            => 200,
        'data'            => [],
      ]);
    }

    $totalData = Wallet_Transaction::where('wallet_id', $wallet->id)->count();
    $totalFiltered = $totalData;


    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';

    $query = Wallet_Transaction::query();
    $query->where('wallet_id', $wallet->id);

    if ($fromDate && $toDate) {
      $query->whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
      ]);
    }





    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('description', 'LIKE', "%{$search}%")
          ->orWhere('amount', 'LIKE', "%{$search}%")
          ->orWhere('transaction_type', 'LIKE', "%{$search}%");
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
        'maturity'    => $val->maturity_time ?? '',
        'user'    => $val->user->name ?? 'automatic',
        'task'    => $val->task_id ?? '',
        'clearance'    => $val->clearance_id ?? '',
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
}
