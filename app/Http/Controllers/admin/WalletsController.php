<?php

namespace App\Http\Controllers\admin;

use Exception;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\Wallet;
use Mockery\Expectation;
use Illuminate\Http\Request;
use App\Models\Wallet_Transaction;
use App\Models\WalletPaymentRequestLog;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\FileHelper;
use App\Helpers\IpHelper;
use Illuminate\Support\Str;
use App\Services\PdfService;
use App\Services\HyperPayPayoutService;

class WalletsController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService = null)
    {
        $this->pdfService = $pdfService;
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
            $query->where(function ($q) use ($search, $type) {
                $q->where('id', 'LIKE', "%{$search}%");

                if ($type === 'customer') {
                    $q->orWhereHas('customer', function ($sub) use ($search) {
                        $sub->where('name', 'LIKE', "%{$search}%");
                    });
                } elseif ($type === 'driver') {
                    $q->orWhereHas('driver', function ($sub) use ($search) {
                        $sub->where('name', 'LIKE', "%{$search}%");
                    });
                }
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
              'name'       => "[ " . $val->id . " ] " . ($val->customer_id ? $val->customer?->name : ($val->driver_id ? $val->driver?->name : 'N/A')),
              'team'       => $val->customer_id ? '' : ($val->driver?->team?->name ?? ''),
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

    public function getStatistics(Request $request)
    {
        $type = $request->input('status') ?? 'customer';
        $search = $request->input('search');

        // بناء استعلام المحافظ مع الفلاتر
        $walletQuery = Wallet::query();

        if (!empty($search)) {
            $walletQuery->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%");
            });
        }
        if (!empty($type)) {
            $walletQuery->where('user_type', $type);
        }

        // الحصول على IDs المحافظ المفلترة
        $walletIds = $walletQuery->pluck('id')->toArray();

        // حساب إجمالي Credit من جميع المعاملات
        $creditTotal = Wallet_Transaction::whereIn('wallet_id', $walletIds)
          ->where('transaction_type', 'credit')
          ->sum('amount');

        // حساب إجمالي Debit من جميع المعاملات
        $debitTotal = Wallet_Transaction::whereIn('wallet_id', $walletIds)
          ->where('transaction_type', 'debit')
          ->sum('amount');

        // حساب الصافي
        $netTotal = $creditTotal - $debitTotal;

        // حساب عدد المحافظ التي لها معاملات Credit
        $creditWalletsCount = Wallet_Transaction::whereIn('wallet_id', $walletIds)
          ->where('transaction_type', 'credit')
          ->distinct('wallet_id')
          ->count('wallet_id');

        // حساب عدد المحافظ التي لها معاملات Debit
        $debitWalletsCount = Wallet_Transaction::whereIn('wallet_id', $walletIds)
          ->where('transaction_type', 'debit')
          ->distinct('wallet_id')
          ->count('wallet_id');

        // إجمالي عدد المحافظ المفلترة
        $totalWalletsCount = count($walletIds);

        return response()->json([
          'credit_total' => round($creditTotal, 2),
          'debit_total' => round($debitTotal, 2),
          'net_total' => round($netTotal, 2),
          'credit_count' => $creditWalletsCount,
          'debit_count' => $debitWalletsCount,
          'total_count' => $totalWalletsCount
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

        $user = auth()->user();

        if ($data->user_type === 'driver') {
            if (!$user || !$user->checkDriver($data->driver_id)) {
                abort(403);
            }
        } else {
            if (!$user || !$user->checkCustomer($data->customer_id)) {
                abort(403);
            }
        }

        return view('admin.wallets.show', compact('data'));
    }

    public function driverShow($id)
    {
        $data = Wallet::where('user_type', 'driver')->findOrFail($id);
        return view('admin.wallets.driver-show', compact('data'));
    }

    public function processDriverPayment(Request $request)
    {
        $request->validate([
          'wallet_id' => 'required|exists:wallets,id',
          'total_amount' => 'required|numeric|min:0.01',
          'transactions' => 'required|array|min:1',
          'transactions.*.id' => 'required|exists:wallet_transactions,id',
          'transactions.*.payment_amount' => 'required|numeric|min:0',
          'notes' => 'nullable|string|max:500',
          'payment_method' => 'nullable|string|in:manual,hyperpay'
        ]);

        DB::beginTransaction();
        try {
            $wallet = Wallet::where('user_type', 'driver')->with('driver')->findOrFail($request->wallet_id);
            $driver = $wallet->driver;

            // --- HyperPay Payout Logic ---
            $hyperPayNotes = '';
            if ($request->payment_method === 'hyperpay') {
                if (!$driver->iban_number || !$driver->bic_code || !$driver->beneficiary_name) {
                    throw new \Exception(__('Driver bank details are incomplete for HyperPay Payout. Please update driver profile.'));
                }

                $payoutService = app(HyperPayPayoutService::class);
                $payoutResponse = $payoutService->sendPayout([
                    'amount' => $request->total_amount,
                    'currency' => 'SAR',
                    'externalId' => 'WP-' . $wallet->id . '-' . time(),
                    'beneficiary_name' => $driver->beneficiary_name,
                    'address1' => $driver->bank_address1 ?? $driver->address,
                    'address2' => $driver->bank_address2 ?? '.',
                    'city' => $driver->bank_city ?? 'Riyadh',
                    'country' => $driver->bank_country ?? 'SA',
                    'iban' => str_replace(' ', '', $driver->iban_number),
                    'bic' => $driver->bic_code,
                    'description' => "Wallet Payment for Driver #{$driver->id}"
                ]);

                if (!$payoutResponse['status']) {
                    throw new \Exception(__('HyperPay Error: ') . $payoutResponse['message']);
                }

                $payoutId = $payoutResponse['data']['payoutId'] ?? 'N/A';
                $bulkId = $payoutResponse['data']['bulkId'] ?? 'N/A';
                $hyperPayNotes = " | HyperPay PayoutId: {$payoutId} | BulkId: {$bulkId}";
            }
            // --- End HyperPay Logic ---

            $transactionIds = collect($request->transactions)->pluck('id')->toArray();

            // Get wallet transactions for this specific driver wallet
            $walletTransactions = Wallet_Transaction::whereIn('id', $transactionIds)
              ->where('wallet_id', $wallet->id)
              ->where('transaction_type', 'credit') // Credit transactions (money owed TO driver)
              ->where('status', 0) // Only unpaid transactions
              ->get();

            if ($walletTransactions->count() !== count($transactionIds)) {
                throw new \Exception('Some transactions are invalid or already paid');
            }

            // Apply sequential distribution like frontend
            $remainingAmount = $request->total_amount;
            $processedTransactions = [];

            // Sort transactions by the order they were sent
            $sortedTransactions = collect($request->transactions)->sortBy(function ($item, $key) {
                return $key;
            });

            foreach ($sortedTransactions as $transactionData) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $walletTransaction = $walletTransactions->where('id', $transactionData['id'])->first();

                if (!$walletTransaction) {
                    throw new \Exception("Transaction {$transactionData['id']} not found");
                }

                $originalAmount = $walletTransaction->amount;
                $paymentAmount = 0;
                $paymentStatus = 'unpaid';

                // Sequential allocation logic
                if ($remainingAmount >= $originalAmount) {
                    // Full payment
                    $paymentAmount = $originalAmount;
                    $remainingAmount -= $originalAmount;
                    $paymentStatus = 'full';

                    $walletTransaction->update([
                      'status' => 1,
                      'user_id' => auth()->id()
                    ]);

                    $paymentDescription = "دفع مستحقات سائق (كامل) للمعاملة رقم #{$walletTransaction->sequence}";
                } elseif ($remainingAmount > 0) {
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

                    // Create new transaction for remaining amount
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

                    $paymentDescription = "دفع مستحقات سائق (جزئي: {$paymentAmount} من {$originalAmount}) للمعاملة رقم #{$walletTransaction->sequence}";
                }

                // Only create payment record if there's an actual payment
                if ($paymentAmount > 0) {
                    // Create debit transaction (payment made to driver - reduces company balance)
                    Wallet_Transaction::create([
                      'wallet_id' => $walletTransaction->wallet_id,
                      'amount' => $paymentAmount,
                      'transaction_type' => 'debit',
                      'description' => $paymentDescription . ($request->notes ? " - ملاحظات: {$request->notes}" : "") . $hyperPayNotes,
                      'status' => 1, // Debit transactions are immediately processed
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

            // Notify Driver
            app(\App\Services\NotificationService::class)->send(
                'driver',
                [$wallet->driver_id],
                '✅ تم إيداع مستحقاتك!',
                "تمت معالجة مبلغ {$request->total_amount} ريال وتحويله إلى حسابك بنجاح.",
                '/images/admin-icon.png',
                '/images/banner.png',
                "/wallet",
                'payment_processed'
            );

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

    public function getDataTransactions(Request $request, $id = null)
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

        // Get wallet ID from URL parameter or request input
        $wallet = $id ?? $request->input('wallet');
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
              'task'        => $val->task_id ?? '',
              'clearance'        => $val->clearance_id ?? '',
              'image' => $val->image ? (Str::startsWith($val->image, 'storage/') ? $val->image : 'storage/' . $val->image) : '',
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
          'image' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx|max:4096',
          'maturity' => 'nullable|date',
          'task_id' => 'nullable|exists:tasks,id',
          'payment_method' => 'nullable|string|in:manual,hyperpay'
        ]);

        if ($validator->fails()) {
            return response()->json([
              'status' => 0,
              'error'  => $validator->errors()
            ]);
        }

        try {
            $wallet = Wallet::findOrFail($req->wallet);
            $adjustedBalance = $wallet->balance;
            $existingTransaction = null;

            // التعديل على معاملة سابقة
            if ($req->filled('id')) {
                $existingTransaction = Wallet_Transaction::findOrFail($req->id);

                // إرجاع المبلغ القديم للحساب
                if ($existingTransaction->transaction_type === 'credit') {
                    $adjustedBalance -= $existingTransaction->amount;
                } elseif ($existingTransaction->transaction_type === 'debit') {
                    $adjustedBalance += $existingTransaction->amount;
                }
            }

            // تطبيق المعاملة الجديدة
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
            
            // --- HyperPay Payout Logic for Manual Debit ---
            $hyperPayNotes = '';
            if ($req->type === 'debit' && $req->payment_method === 'hyperpay') {
                if ($wallet->user_type !== 'driver' || !$wallet->driver) {
                    return response()->json(['status' => 2, 'error' => __('HyperPay Payout is only available for driver wallets')]);
                }

                $driver = $wallet->driver;
                if (!$driver->iban_number || !$driver->bic_code || !$driver->beneficiary_name) {
                    return response()->json(['status' => 2, 'error' => __('Driver bank details are incomplete for HyperPay Payout')]);
                }

                $countryMapping = [
                    'السعودية' => 'SA',
                    'الإمارات' => 'AE',
                    'الكويت' => 'KW',
                    'عمان' => 'OM',
                    'البحرين' => 'BH',
                    'قطر' => 'QA',
                    'مصر' => 'EG',
                    'الأردن' => 'JO',
                ];
                $countryCode = $countryMapping[$driver->bank_country] ?? ($driver->bank_country ?: 'SA');

                $payoutService = app(HyperPayPayoutService::class);
                $payoutResponse = $payoutService->sendPayout([
                    'amount' => $req->amount,
                    'currency' => 'SAR',
                    'externalId' => 'MT-' . $wallet->id . '-' . time(),
                    'beneficiary_name' => $driver->beneficiary_name,
                    'address1' => $driver->bank_address1 ?? $driver->address,
                    'address2' => $driver->bank_address2 ?? '.',
                    'city' => $driver->bank_city ?? 'Riyadh',
                    'country' => $countryCode,
                    'iban' => str_replace(' ', '', $driver->iban_number),
                    'bic' => $driver->bic_code,
                    'description' => "Manual Payout for Driver #{$driver->id}"
                ]);

                if (!$payoutResponse['status']) {
                    return response()->json(['status' => 2, 'error' => __('HyperPay Error: ') . $payoutResponse['message']]);
                }

                $payoutId = $payoutResponse['data']['payoutId'] ?? 'N/A';
                $bulkId = $payoutResponse['data']['bulkId'] ?? 'N/A';
                $hyperPayNotes = " | HyperPay PayoutId: {$payoutId} | BulkId: {$bulkId}";
            }
            // --- End HyperPay Logic ---

            DB::beginTransaction();

            $data = [
              'amount' => $req->amount,
              'description' => $req->description . $hyperPayNotes,
              'transaction_type' => $req->type,
              'maturity_time' => $req->type === 'credit' ? null : $req->maturity,
            ];

            if ($req->hasFile('image')) {
                // $data['image'] = (new FunctionsController())->convert($req->image, 'wallets/transactions');
                $data['image'] = FileHelper::uploadFile($req->file("image"), 'wallets/transactions');
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
                $data['wallet_id'] = $req->wallet;
                $data['user_id'] = auth()->id();
                $data['task_id'] = $req->task_id;
                Wallet_Transaction::create($data);
            }

            if ($oldImage && file_exists($oldImage)) {
                // unlink($oldImage);
                FileHelper::deleteFileIfExists($oldImage);

            }

            DB::commit();

            // Notify Driver if wallet owner is driver
            if ($wallet->user_type === 'driver' && $wallet->driver_id) {
                $typeText = $req->type === 'credit' ? 'إيداع' : 'خصم';
                app(\App\Services\NotificationService::class)->send(
                    'driver',
                    [$wallet->driver_id],
                    "تحديث في المحفظة: {$typeText}",
                    "تمت إضافة عملية {$typeText} بقيمة {$req->amount} ريال في محفظتك. السبب: {$req->description}",
                    '/images/admin-icon.png',
                    '/images/banner.png',
                    "/wallet",
                    'wallet_adjustment'
                );
            }
return response()->json([
    'status'  => 1,
    'success' => __('Transaction saved successfully'),
]);

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
                FileHelper::deleteFileIfExists($oldImage);

                // unlink($oldImage);
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


    public function paymentRequest($id)
    {
        try {
            $wallet = Wallet::findOrFail($id);

            if ($wallet->user_type != 'driver') {
                return response()->json([
                  'status' => 0,
                  'error' => __('Wallet not found')
                ]);
            }
            // Calculate driver amount (total_price - commission)
            $driverAmount = $wallet->balance;

            // Get driver info with bank details
            $driverName = $wallet->driver->name ;
            $driverPhone = $wallet->driver->name ? $wallet->driver->phone_code .  $wallet->driver->phone : "";
            $driverEmail = $wallet->driver->name ? $wallet->driver->email : "";
            $driverBankName = $wallet->driver->bank_name ?? null;
            $driverAccountNumber = $wallet->driver->account_number ?? null;
            $driverIbanNumber = $wallet->driver->iban_number ?? null;
            $userId = Auth::user()->id;
            $userName = Auth::user()->name;

            return response()->json([
                'status' => 1,
                'wallet' => [
                    'id' => $wallet->id,
                    'balance' => $wallet->balance,
                    'driver_id' => $wallet->driver_id,
                    'driver_name' => $driverName,
                    'driver_phone' => $driverPhone,
                    'driver_email' => $driverEmail,
                    'driver_bank_name' => $driverBankName,
                    'driver_account_number' => $driverAccountNumber,
                    'driver_iban_number' => $driverIbanNumber,
                    'user_id' =>  $userId ,
                    'user_name' =>  $userName
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'error' => __('Wallet not found') . $e->getMessage()
            ]);
        }
    }

    /**
     * Log wallet payment request when printed
     */
    public function logPaymentRequest(Request $request, $walletId)
    {
        try {
            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'payment_request_number' => 'required|string|max:50',
                'payment_method' => 'required|in:bank_transfer,other',
                'bank_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'iban_number' => 'nullable|string|max:34',
                'other_payment_method' => 'nullable|string|max:1000',
                'notes' => 'nullable|string|max:1000',
                'selected_tasks' => 'nullable|array',
                'selected_tasks.*' => 'integer|exists:tasks,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'error' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ]);
            }

            // جلب المحفظة والتحقق من وجودها
            $wallet = Wallet::with('driver')->findOrFail($walletId);

            // التحقق من أن المحفظة خاصة بسائق
            if ($wallet->user_type !== 'driver') {
                return response()->json([
                    'status' => 0,
                    'error' => 'هذه المحفظة ليست خاصة بسائق'
                ]);
            }

            // التحقق من صلاحية المستخدم
            $user = Auth::user();
            if (!$user->checkDriver($wallet->driver_id)) {
                return response()->json([
                    'status' => 0,
                    'error' => 'غير مصرح لك بالوصول لهذه المحفظة'
                ]);
            }

            // معالجة طريقة الدفع والملاحظات
            $finalNotes = $request->notes ?? '';

            // إضافة معلومات طريقة الدفع
            if ($request->payment_method === 'other' && $request->other_payment_method) {
                if (!empty($finalNotes)) {
                    $finalNotes .= "\n\n";
                }
                $finalNotes .= "طريقة الدفع: " . $request->other_payment_method;
            } elseif ($request->payment_method === 'bank_transfer') {
                $bankInfo = [];
                if ($request->bank_name) {
                    $bankInfo[] = "البنك: " . $request->bank_name;
                }
                if ($request->account_number) {
                    $bankInfo[] = "رقم الحساب: " . $request->account_number;
                }
                if ($request->iban_number) {
                    $bankInfo[] = "الآيبان: " . $request->iban_number;
                }

                if (!empty($bankInfo)) {
                    if (!empty($finalNotes)) {
                        $finalNotes .= "\n\n";
                    }
                    $finalNotes .= "معلومات التحويل البنكي:\n" . implode("\n", $bankInfo);
                }
            }

            // معالجة المهام المحددة
            if ($request->selected_tasks && count($request->selected_tasks) > 0) {
                // التحقق من أن المهام تنتمي للسائق
                $tasks = Task::whereIn('id', $request->selected_tasks)
                    ->where('driver_id', $wallet->driver_id)
                    ->with(['pickup', 'delivery'])
                    ->get();

                if ($tasks->count() !== count($request->selected_tasks)) {
                    return response()->json([
                        'status' => 0,
                        'error' => 'بعض المهام المحددة لا تنتمي لهذا السائق'
                    ]);
                }

                // إضافة معلومات المهام للملاحظات
                if (!empty($finalNotes)) {
                    $finalNotes .= "\n\n";
                }

                $finalNotes .= "المهام المحددة:\n";
                foreach ($tasks as $task) {
                    $pickupAddress = $task->pickup->address ?? 'عنوان غير محدد';
                    $finalNotes .= "- مهمة #{$task->id}: {$pickupAddress} - {$task->total_price} ريال - {$task->status}\n";
                }
            }

            // إنشاء سجل طلب السداد
            $log = WalletPaymentRequestLog::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'driver_id' => $wallet->driver_id,
                'amount' => $request->amount,
                'payment_request_number' => $request->payment_request_number,
                'notes' => $finalNotes,
                'ip_address' => IpHelper::getUserIpAddress(),
                'printed_at' => now(),
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'تم تسجيل طلب السداد بنجاح',
                'log_id' => $log->id
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'error' => 'حدث خطأ أثناء تسجيل طلب السداد: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get payment request logs for a wallet
     */
    public function getPaymentRequestLogs($walletId)
    {
        try {
            $wallet = Wallet::findOrFail($walletId);

            // التحقق من صلاحية المستخدم
            $user = Auth::user();
            if ($wallet->user_type === 'driver' && !$user->checkDriver($wallet->driver_id)) {
                return response()->json([
                    'status' => 0,
                    'error' => 'You have no accesss to this wallet'
                ]);
            }

            // جلب سجلات طلبات السداد
            $logs = WalletPaymentRequestLog::with(['user', 'driver'])
                ->forWallet($walletId)
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 1,
                'logs' => $logs
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'error' => 'حدث خطأ أثناء جلب السجلات: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get tasks for a specific driver
     */
    public function getDriverTasks($driverId)
    {
        try {
            // التحقق من صلاحية المستخدم
            // $user = Auth::user();
            // if (!$user->checkDriver($driverId)) {
            //     return response()->json([
            //         'status' => 0,
            //         'error' => 'غير مصرح لك بالوصول لمهام هذا السائق'
            //     ]);
            // }

            // جلب المهام المرتبطة بالسائق
            $tasks = Task::with(['pickup', 'delivery'])
                ->where('driver_id', $driverId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'text' => "مهمة #{$task->id} - " . ($task->pickup->address ?? 'عنوان غير محدد') . " - {$task->total_price} ريال - {$task->status}",
                        'status' => $task->status,
                        'total_price' => $task->total_price - $task->commission,
                        'pickup_address' => $task->pickup->address ?? 'عنوان غير محدد',
                        'delivery_address' => $task->delivery->address ?? 'عنوان غير محدد'
                    ];
                });

            return response()->json([
                'status' => 1,
                'tasks' => $tasks
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'error' => 'حدث خطأ أثناء جلب المهام: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download credit receipt for a wallet transaction
     */
    public function downloadCreditReceipt($transactionId)
    {
        try {
            $transaction = Wallet_Transaction::with(['wallet.customer', 'wallet.driver', 'task'])
                ->findOrFail($transactionId);

            // Verify transaction type is credit
            if ($transaction->transaction_type !== 'credit') {
                return redirect()->back()->with('error', __('Receipt is only available for credit transactions.'));
            }

            $wallet = $transaction->wallet;

            // Check user permission
            $user = Auth::user();
            if ($wallet->user_type === 'driver') {
                if (!$user || !$user->checkDriver($wallet->driver_id)) {
                    abort(403);
                }
            } else {
                if (!$user || !$user->checkCustomer($wallet->customer_id)) {
                    abort(403);
                }
            }

            // Convert amount to Arabic words
            $amountInWords = $this->convertNumberToArabicWords($transaction->amount);

            $file_name = "receipt_{$wallet->id}_{$transaction->sequence}";

            return $this->pdfService->generate('admin.wallets.receipt_pdf', [
                'transaction' => $transaction,
                'wallet' => $wallet,
                'amountInWords' => $amountInWords
            ], "{$file_name}.pdf", true);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', __('Transaction not found.'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', __('Failed to generate receipt: ') . $e->getMessage());
        }
    }

    /**
     * Convert number to Arabic words
     */
    private function convertNumberToArabicWords($number)
    {
        $number = floatval($number);
        $integerPart = floor($number);
        $decimalPart = round(($number - $integerPart) * 100);

        $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $tens = ['', 'عشرة', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];
        $teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];

        $result = '';

        if ($integerPart == 0) {
            $result = 'صفر';
        } else {
            // Thousands
            $thousands = floor($integerPart / 1000);
            if ($thousands > 0) {
                if ($thousands == 1) {
                    $result .= 'ألف ';
                } elseif ($thousands == 2) {
                    $result .= 'ألفان ';
                } elseif ($thousands >= 3 && $thousands <= 10) {
                    $result .= $ones[$thousands] . ' آلاف ';
                } else {
                    $result .= $this->convertNumberToArabicWords($thousands) . ' ألف ';
                }
            }

            // Hundreds
            $remainder = $integerPart % 1000;
            $hundredsDigit = floor($remainder / 100);
            if ($hundredsDigit > 0) {
                $result .= $hundreds[$hundredsDigit] . ' ';
            }

            // Tens and ones
            $remainder = $remainder % 100;
            if ($remainder >= 10 && $remainder < 20) {
                $result .= $teens[$remainder - 10] . ' ';
            } else {
                $tensDigit = floor($remainder / 10);
                $onesDigit = $remainder % 10;

                if ($tensDigit > 0) {
                    $result .= $tens[$tensDigit] . ' ';
                }
                if ($onesDigit > 0) {
                    $result .= ($tensDigit > 0 ? 'و' : '') . $ones[$onesDigit] . ' ';
                }
            }
        }

        $result = trim($result) . ' ريال';

        // Add decimal part (halalas)
        if ($decimalPart > 0) {
            $result .= ' و';
            if ($decimalPart >= 10 && $decimalPart < 20) {
                $result .= ' ' . $teens[$decimalPart - 10];
            } else {
                $tensDigit = floor($decimalPart / 10);
                $onesDigit = $decimalPart % 10;

                if ($tensDigit > 0) {
                    $result .= ' ' . $tens[$tensDigit];
                }
                if ($onesDigit > 0) {
                    $result .= ($tensDigit > 0 ? ' و' : ' ') . $ones[$onesDigit];
                }
            }
            $result .= ' هللة';
        }

        return $result;
    }

}
