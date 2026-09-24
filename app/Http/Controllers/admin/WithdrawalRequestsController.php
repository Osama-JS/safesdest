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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Validation\Rule;

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
        // 1. Calculate Statistics
        $stats = [
            'pending_count' => WithdrawalRequest::pending()->count(),
            'pending_amount' => WithdrawalRequest::pending()->sum('amount_requested'),
            'processing_count' => WithdrawalRequest::where('status', 'processing')->count(),
            'processing_amount' => WithdrawalRequest::where('status', 'processing')->sum('amount_paid'),
            'approved_count' => WithdrawalRequest::completed()->count(),
            'approved_amount' => WithdrawalRequest::completed()->sum('amount_paid'),
            'rejected_count' => WithdrawalRequest::where('status', 'rejected')->count(),
        ];

        $drivers = Driver::select('id', 'name')->orderBy('name')->get();

        return view('admin.wallets.withdrawals.index', compact('stats', 'drivers'));
    }

    /**
     * Get data for DataTables.
     */
    public function getData(Request $request)
    {
        $query = WithdrawalRequest::with(['driver.wallet', 'wallet', 'transaction', 'processor']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->driver_id) {
            $query->where('driver_id', $request->driver_id);
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
            $processedBy = $withdrawal->processor ? $withdrawal->processor->name : 'N/A';
            $driver = $withdrawal->driver;

            $data[] = [
                'id' => $withdrawal->id,
                'driver_name' => $driver->name ?? 'N/A',
                'driver_id' => $withdrawal->driver_id,
                'wallet_id' => $withdrawal->wallet_id,
                'wallet_balance' => $driver->wallet ? $driver->wallet->balance : 0,
                'bank_details' => [
                    'iban' => $driver->iban_number ?? 'N/A',
                    'beneficiary' => $driver->beneficiary_name ?? 'N/A',
                    'formatted_beneficiary' => \App\Services\HyperPayPayoutService::formatBeneficiaryName($driver->beneficiary_name ?? ''),
                    'bic' => $driver->bic_code ?? 'N/A',
                    'bank_name' => $driver->bank_name ?? 'N/A',
                    'address1' => $driver->bank_address1 ?? $driver->address ?? 'N/A',
                    'address2' => $driver->bank_address2 ?? '.',
                    'city' => $driver->bank_city ?? 'Riyadh',
                    'country' => $driver->bank_country ?? 'SA',
                ],
                'amount_requested' => $withdrawal->amount_requested,
                'amount_paid' => $withdrawal->amount_paid ?? 0,
                'approved_amount' => $withdrawal->amount_paid ?? 0,
                'status' => $withdrawal->status,
                'payment_method' => $withdrawal->payment_method,
                'created_at' => $withdrawal->created_at->format('Y-m-d H:i'),
                'processed_at' => $withdrawal->processed_at ? $withdrawal->processed_at->format('Y-m-d H:i') : null,
                'processed_by_name' => $processedBy,
                'admin_notes' => $withdrawal->admin_notes,
                'receipt_image' => $withdrawal->receipt_image ? asset('storage/' . $withdrawal->receipt_image) : null,
                'actions' => '' // handled by JS
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
    public function process(Request $request, $id, \App\Services\HyperPayPayoutService $payoutService)
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
            'password' => [
                'nullable',
                'string',
                Rule::requiredIf(function () use ($request) {
                    return $request->action === 'approve' && $request->payment_method === 'hyperpay';
                }),
            ],
            'beneficiary_name' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            if ($request->action === 'approve') {
                $amountPaid = $request->amount_paid;
                $wallet = Wallet::findOrFail($withdrawal->wallet_id);
                $driver = Driver::findOrFail($withdrawal->driver_id);

                // Check if wallet balance is still enough
                if ($wallet->balance < $amountPaid) {
                    throw new \Exception(__('Insufficient wallet balance for this amount'));
                }

                $receiptPath = null;
                if ($request->hasFile('receipt')) {
                    $receiptPath = FileHelper::uploadFile($request->file('receipt'), 'wallets/receipts');
                }

                // --- HyperPay Payout Logic ---
                if ($request->payment_method === 'hyperpay') {
                    if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
                        return response()->json([
                            'success' => false,
                            'message' => __('كلمة المرور الخاصة بالمشرف غير صحيحة.')
                        ], 422);
                    }

                    // Validate Driver Bank Details
                    if (!$driver->iban_number || !$driver->bic_code || !$driver->beneficiary_name) {
                        throw new \Exception(__('Driver bank details are incomplete for HyperPay Payout. Please update driver profile.'));
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

                    // Check if already pending payout
                    $existingPayout = \App\Models\HyperpayPayout::where('source_withdrawal_id', $withdrawal->id)
                        ->whereIn('status', ['pending', 'processing', 'pending_approval'])
                        ->exists();

                    if ($existingPayout) {
                        return response()->json([
                            'success' => false,
                            'message' => __('يوجد طلب دفع عبر HyperPay قيد الانتظار أو بانتظار المصادقة بالفعل لهذا الطلب.')
                        ], 422);
                    }

                    $ibanCheck = \App\Services\HyperPayPayoutService::validateIbanChecksum($driver->iban_number);
                    if (!$ibanCheck['valid']) {
                        return response()->json([
                            'success' => false,
                            'message' => $ibanCheck['message']
                        ], 422);
                    }

                    $beneficiaryName = $request->beneficiary_name ?: \App\Services\HyperPayPayoutService::formatBeneficiaryName($driver->beneficiary_name);
                    $externalId = 'WD-' . $withdrawal->id . '-' . time();

                    \App\Models\HyperpayPayout::create([
                        'reference_id' => $externalId,
                        'wallet_id' => $wallet->id,
                        'driver_id' => $driver->id,
                        'amount' => $amountPaid,
                        'payout_type' => 'WD',
                        'source_withdrawal_id' => $withdrawal->id,
                        'created_by' => auth()->id(),
                        'transaction_details' => [
                            'amount_paid' => $amountPaid,
                            'admin_notes' => $request->admin_notes,
                            'receipt_image' => $receiptPath,
                            'admin_id' => auth()->id(),
                            'beneficiary_name' => $beneficiaryName,
                            'iban' => str_replace(' ', '', $driver->iban_number),
                            'bic' => $driver->bic_code,
                            'bank_name' => $driver->bank_name,
                            'country' => $countryCode,
                            'address1' => $driver->bank_address1 ?? $driver->address,
                            'address2' => $driver->bank_address2 ?? '.',
                            'city' => $driver->bank_city ?? 'Riyadh',
                        ],
                        'status' => 'pending_approval'
                    ]);

                    $withdrawal->update([
                        'status' => 'processing',
                        'amount_paid' => $amountPaid,
                        'payment_method' => 'hyperpay',
                        'admin_notes' => ($request->admin_notes ? $request->admin_notes . ' | ' : '') . "بانتظار مصادقة المدير على التحويل عبر الـ Payout",
                        'receipt_image' => $receiptPath,
                        'processed_by' => auth()->id(),
                        'processed_at' => now(),
                    ]);

                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => __('تم اعتماد طلب السحب بنجاح وتم تحويله إلى صفحة "طلبات الدفع عبر الـ Payout" بانتظار مصادقة المدير.')
                    ]);
                }
                // --- End HyperPay Logic ---

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
            
            if ($request->action === 'reject') {
                $msg = __('تم رفض طلب السحب بنجاح.');
            } else {
                $msg = ($request->payment_method === 'hyperpay') 
                    ? __('تم اعتماد طلب السحب بنجاح وتم تحويله إلى صفحة "طلبات الدفع عبر الـ Payout" بانتظار مصادقة المدير.')
                    : __('تمت الموافقة وصرف طلب السحب بنجاح.');
            }

            return response()->json(['success' => true, 'message' => $msg]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Withdrawal Processing Error:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
