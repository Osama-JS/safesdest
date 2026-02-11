<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Models\Wallet_Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\FileHelper;
use Carbon\Carbon;

class WithdrawalRequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_wallets');
    }

    /**
     * Display a listing of withdrawal requests.
     */
    public function index()
    {
        return view('admin.wallets.withdrawals.index');
    }

    /**
     * Get data for DataTables.
     */
    public function getData(Request $request)
    {
        $query = WithdrawalRequest::with(['driver', 'wallet', 'transaction', 'processor']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $totalData = $query->count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = 'created_at';
        $dir = 'desc';

        $withdrawals = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        foreach ($withdrawals as $withdrawal) {
            $data[] = [
                'id' => $withdrawal->id,
                'driver_name' => $withdrawal->driver->name ?? 'N/A',
                'wallet_id' => $withdrawal->wallet_id,
                'amount_requested' => $withdrawal->amount_requested,
                'amount_paid' => $withdrawal->amount_paid,
                'status' => $withdrawal->status,
                'payment_method' => $withdrawal->payment_method,
                'created_at' => $withdrawal->created_at->format('Y-m-d H:i'),
                'processed_at' => $withdrawal->processed_at ? $withdrawal->processed_at->format('Y-m-d H:i') : null,
                'actions' => '' // Will be handled by JS
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Process a withdrawal request (Accept/Reject).
     */
    public function process(Request $request, $id)
    {
        $withdrawal = WithdrawalRequest::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => __('Request already processed')], 422);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'amount_paid' => 'required_if:action,approve|numeric|min:0',
            'payment_method' => 'required_if:action,approve|string',
            'admin_notes' => 'nullable|string|max:1000',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            if ($request->action === 'approve') {
                $amountPaid = $request->amount_paid;
                $wallet = Wallet::findOrFail($withdrawal->wallet_id);

                // Check if wallet balance is still enough
                if ($wallet->balance < $amountPaid) {
                    throw new \Exception(__('Insufficient wallet balance for this amount'));
                }

                $receiptPath = null;
                if ($request->hasFile('receipt')) {
                    $receiptPath = FileHelper::uploadFile($request->file('receipt'), 'wallets/receipts');
                }

                // 1. Create Wallet Transaction
                $transaction = Wallet_Transaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => auth()->id(),
                    'amount' => $amountPaid,
                    'transaction_type' => 'debit',
                    'description' => "سحب نقدي - طلب رقم #{$withdrawal->id}. " . $request->admin_notes,
                    'status' => 1,
                    'image' => $receiptPath,
                    'maturity_time' => now(),
                ]);

                // 2. Update Withdrawal Request
                $withdrawal->update([
                    'status' => 'completed',
                    'amount_paid' => $amountPaid,
                    'payment_method' => $request->payment_method,
                    'admin_notes' => $request->admin_notes,
                    'receipt_image' => $receiptPath,
                    'wallet_transaction_id' => $transaction->id,
                    'processed_by' => auth()->id(),
                    'processed_at' => now(),
                ]);

                // Notify Driver
                app(\App\Services\NotificationService::class)->send(
                    'driver',
                    [$withdrawal->driver_id],
                    '✅ تمت الموافقة على طلب السحب',
                    "تم صرف مبلغ {$amountPaid} ريال من طلبك رقم #{$withdrawal->id}.",
                    '/images/admin-icon.png',
                    null,
                    "/wallet",
                    'withdrawal_approved'
                );

            } else {
                // Reject
                $withdrawal->update([
                    'status' => 'rejected',
                    'admin_notes' => $request->admin_notes,
                    'processed_by' => auth()->id(),
                    'processed_at' => now(),
                ]);

                // Notify Driver
                app(\App\Services\NotificationService::class)->send(
                    'driver',
                    [$withdrawal->driver_id],
                    '❌ تم رفض طلب السحب',
                    "تم رفض طلب السحب رقم #{$withdrawal->id}. الملاحظات: " . $request->admin_notes,
                    '/images/admin-icon.png',
                    null,
                    "/wallet",
                    'withdrawal_rejected'
                );
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => __('Processed successfully')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
