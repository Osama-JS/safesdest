<?php

namespace App\Http\Controllers\admin;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserWallet;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\UserWalletTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserWalletsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_beneficiaries_wallet', ['only' => ['show', 'getTransactions']]);
        $this->middleware('permission:transaction_beneficiaries_wallet', ['only' => ['addTransaction', 'editTransaction' ,'destroyTransaction','processWithdrawal']]);
    }

    /**
     * عرض محفظة المستخدم
     */
    public function show($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $wallet = $user->userWallet;

            // إنشاء محفظة تلقائية إذا لم تكن موجودة
            if (!$wallet) {
                $wallet = $this->createWallet($userId);
            }

            return view('admin.user-wallets.show', [
                'user' => $user,
                'wallet' => $wallet,
                'balance' => $wallet->balance,
                'credit' => $wallet->credit,
                'debit' => $wallet->debit,
                'lastTransaction' => $wallet->last_transaction,
            ]);

        } catch (Exception $e) {
            return redirect()->back()->with('error', __('User not found'));
        }
    }

    /**
     * جلب معاملات المحفظة
     */
    public function getTransactions(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $wallet = $user->userWallet;

            if (!$wallet) {
                return response()->json([
                    'draw' => intval($request->input('draw')),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ]);
            }

            $columns = [
                1 => 'id',
                2 => 'sequence',
                3 => 'amount',
                4 => 'transaction_type',
                5 => 'description',
                6 => 'created_at',
            ];

            $fromDate  = $request->input('from_date');
            $toDate    = $request->input('to_date');
            $search = $request->input('search');
            $type = $request->input('status');


            $totalData = $wallet->transactions()->count();
            $totalFiltered = $totalData;

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')] ?? 'created_at';
            $dir = $request->input('order.0.dir') ?? 'desc';

            $query = $wallet->transactions()->with(['task', 'user']);


            if ($fromDate && $toDate) {
                $query->whereBetween('created_at', [
                  Carbon::parse($fromDate)->startOfDay(),
                  Carbon::parse($toDate)->endOfDay()
                ]);
            }
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'LIKE', "%{$search}%")
                      ->orWhere('amount', 'LIKE', "%{$search}%");
                });

                $totalFiltered = $query->count();
            }

            if (!empty($type) && $type != 'all') {
                $query->where('transaction_type', $type);
            }

            $transactions = $query->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $data = [];
            foreach ($transactions as $transaction) {
                $nestedData = [];
                $nestedData['id'] = $transaction->id;
                $nestedData['sequence'] = $transaction->sequence;
                $nestedData['amount'] = number_format($transaction->amount, 2);
                $nestedData['transaction_type'] = $transaction->transaction_type;
                $nestedData['description'] = $transaction->description;
                $nestedData['task_id'] = $transaction->task_id ?? '';
                $nestedData['user'] = $transaction->task_id ? ($transaction->task?->user?->name ?? 'Task User Not Found') : ($transaction->user?->name ?? 'System');
                $nestedData['image'] = $transaction->image ? (Str::startsWith($transaction->image, 'storage/') ? $transaction->image : 'storage/' . $transaction->image) : '';
                $nestedData['created_at'] = $transaction->created_at->format('Y-m-d H:i');
                $data[] = $nestedData;
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data' => $data
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * إنشاء محفظة للمستخدم
     */
    public function createWallet($userId, $status = true)
    {
        try {
            $user = User::findOrFail($userId);

            // التحقق من عدم وجود محفظة مسبقاً
            $existingWallet = $user->userWallet;
            if ($existingWallet) {
                return $existingWallet;
            }

            $wallet = UserWallet::create([
                'user_type' => 'user',
                'user_id' => $userId,
                'status' => $status,
                'preview' => 0,
                'debt_ceiling' => 5000, // القيمة الافتراضية
            ]);

            return $wallet;

        } catch (Exception $e) {
            throw new Exception('Failed to create wallet: ' . $e->getMessage());
        }
    }

    /**
     * إضافة معاملة إلى محفظة المستخدم
     */
    public function addTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            // 'transaction_type' => 'required|in:credit,debit',
            'payment_method' => 'nullable|string|in:manual,hyperpay',
            'description' => 'required|string|max:255',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx|max:4096',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'error'  => $validator->errors()
            ]);
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($request->user);
            $wallet = $user->userWallet;

            if (!$wallet) {
                $wallet = $this->createWallet($request->user);
            }

            $existingTransaction = null;
            $adjustedBalance = $wallet->balance;
            $request->transaction_type = 'debit';

            // في حالة التعديل، نسترجع العملية القديمة ونرجع تأثيرها من الرصيد
            if ($request->filled('id')) {
                $existingTransaction = UserWalletTransaction::findOrFail($request->id);
                if ($existingTransaction->transaction_type === 'credit') {
                    $adjustedBalance -= $existingTransaction->amount;
                } elseif ($existingTransaction->transaction_type === 'debit') {
                    $adjustedBalance += $existingTransaction->amount;
                }
            }

            // تطبيق المعاملة الجديدة
            if ($request->transaction_type === 'credit') {
                $adjustedBalance += $request->amount;
            } elseif ($request->transaction_type === 'debit') {
                $adjustedBalance -= $request->amount;
            }

            // التأكد من عدم تجاوز الحد الائتماني
            if ($adjustedBalance < -$wallet->debt_ceiling) {
                $maxDebitAmount = $wallet->balance + $wallet->debt_ceiling;
                return response()->json([
                    'status' => 2,
                    'error' => __('Transaction amount exceeds debt ceiling. Maximum debit allowed: ') .
                        number_format($maxDebitAmount, 2) . ' SAR' .
                        ' (Current Balance: ' . number_format($wallet->balance, 2) . ' SAR, ' .
                        'Debt Ceiling: ' . number_format($wallet->debt_ceiling, 2) . ' SAR)'
                ]);
            }

            // --- HyperPay Payout Logic for Debit ---
            $hyperPayNotes = '';
            if ($request->transaction_type === 'debit' && $request->payment_method === 'hyperpay') {
                if (!$user->iban_number || !$user->bic_code || !$user->beneficiary_name) {
                    return response()->json(['status' => 2, 'error' => 'البيانات البنكية غير مكتملة لاستخدام تحويل HyperPay. يرجى تحديث الملف (رقم الآيبان، رمز السويفت، اسم المستفيد).']);
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
                $countryCode = $countryMapping[$user->bank_country] ?? ($user->bank_country ?: 'SA');

                $payoutService = app(\App\Services\HyperPayPayoutService::class);
                $payoutResponse = $payoutService->sendPayout([
                    'amount' => $request->amount,
                    'currency' => 'SAR',
                    'externalId' => 'UWP-' . $wallet->id . '-' . time(),
                    'beneficiary_name' => $user->beneficiary_name,
                    'address1' => $user->bank_address1 ?? $user->address ?? '.',
                    'address2' => $user->bank_address2 ?? '.',
                    'city' => $user->bank_city ?? 'Riyadh',
                    'country' => $countryCode,
                    'iban' => str_replace(' ', '', $user->iban_number),
                    'bic' => $user->bic_code,
                    'description' => "Payout for User #{$user->id}"
                ]);

                if (!$payoutResponse['status']) {
                    return response()->json(['status' => 2, 'error' => 'خطأ من HyperPay: ' . $payoutResponse['message']]);
                }

                $payoutId = $payoutResponse['data']['payoutId'] ?? 'N/A';
                $bulkId = $payoutResponse['data']['bulkId'] ?? 'N/A';
                $hyperPayNotes = " | HyperPay PayoutId: {$payoutId} | BulkId: {$bulkId}";
            }
            // --- End HyperPay Logic ---

            // تجهيز البيانات المشتركة
            $data = [
                'user_wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'description' => $request->description . $hyperPayNotes,

                'transaction_type' => $request->transaction_type,
                'task_id' => $request->task_id,
                'user_id' => Auth::user()->id,
            ];

            $oldImage = null;

            // معالجة الصورة
            if ($request->hasFile('image')) {
                $data['image'] = FileHelper::uploadFile($request->file("image"), 'user-wallets/transactions');
            }

            // تعديل أو إضافة جديدة
            if ($existingTransaction) {
                // في حالة وجود صورة جديدة نحذف القديمة
                if ($request->hasFile('image') && $existingTransaction->image) {
                    $oldImage = $existingTransaction->image;
                }

                $existingTransaction->update($data);

            } else {
                UserWalletTransaction::create($data);
            }

            // حذف الصورة القديمة (إن وجدت وتم استبدالها)
            if ($oldImage) {
                FileHelper::deleteFileIfExists($oldImage);
            }

            $wallet->save();

            DB::commit();

            return response()->json([
                'status' => 1,
                'success' => $existingTransaction
                    ? __('Transaction updated successfully')
                    : __('Transaction added successfully')
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }


    public function editTransaction($id)
    {
        $data = UserWalletTransaction::findOrFail($id);
        if (!$data) {
            return response()->json(['status' => 2, 'error' => __('Can not find the selected Transaction')]);
        }
        return response()->json(['status' => 1, 'data' => $data]);
    }

    /**
     * معالجة طلب سحب من المحفظة
     */
    public function processWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'withdrawal_method' => 'required|in:cash,bank_transfer,check',
            'withdrawal_reason' => 'required|in:commission_payout,salary,bonus,advance,refund,other',
            'notes' => 'nullable|string|max:500',
            'reference_number' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'bank_name' => 'required_if:withdrawal_method,bank_transfer|string|max:100',
            'account_number' => 'required_if:withdrawal_method,bank_transfer|string|max:50',
            'account_holder' => 'required_if:withdrawal_method,bank_transfer|string|max:100',
        ], [
            'user_id.required' => __('User is required'),
            'user_id.exists' => __('Selected user does not exist'),
            'amount.required' => __('Amount is required'),
            'amount.numeric' => __('Amount must be a number'),
            'amount.min' => __('Amount must be greater than 0'),
            'withdrawal_method.required' => __('Withdrawal method is required'),
            'withdrawal_method.in' => __('Invalid withdrawal method'),
            'withdrawal_reason.required' => __('Withdrawal reason is required'),
            'withdrawal_reason.in' => __('Invalid withdrawal reason'),
            'bank_name.required_if' => __('Bank name is required for bank transfers'),
            'account_number.required_if' => __('Account number is required for bank transfers'),
            'account_holder.required_if' => __('Account holder name is required for bank transfers'),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 2, 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($request->user_id);
            $wallet = $user->userWallet;

            if (!$wallet) {
                return response()->json([
                    'status' => 2,
                    'error' => __('User wallet not found')
                ]);
            }

            $currentBalance = $wallet->balance;
            $debtCeiling = $wallet->debt_ceiling;
            $requestedAmount = $request->amount;

            // حساب الرصيد بعد السحب
            $balanceAfterWithdrawal = $currentBalance - $requestedAmount;

            // التحقق من عدم تجاوز سقف الدين
            if ($balanceAfterWithdrawal < -$debtCeiling) {
                $maxWithdrawalAmount = $currentBalance + $debtCeiling;
                return response()->json([
                    'status' => 2,
                    'error' => __('Withdrawal amount exceeds debt ceiling. Maximum withdrawal allowed: ') .
                              number_format($maxWithdrawalAmount, 2) . ' SAR' .
                              ' (Current Balance: ' . number_format($currentBalance, 2) . ' SAR, ' .
                              'Debt Ceiling: ' . number_format($debtCeiling, 2) . ' SAR)'
                ]);
            }

            // إنشاء وصف مفصل للمعاملة
            $methodNames = [
                'cash' => __('Cash'),
                'bank_transfer' => __('Bank Transfer'),
                'check' => __('Check')
            ];

            $reasonNames = [
                'commission_payout' => __('Commission Payout'),
                'salary' => __('Salary'),
                'bonus' => __('Bonus'),
                'advance' => __('Salary Advance'),
                'refund' => __('Refund'),
                'other' => __('Other')
            ];

            $description = __('Cash Withdrawal') . ' - ' . $methodNames[$request->withdrawal_method] . ' - ' . $reasonNames[$request->withdrawal_reason];

            if ($request->notes) {
                $description .= ' - ' . $request->notes;
            }

            if ($request->reference_number) {
                $description .= ' - ' . __('Ref') . ': ' . $request->reference_number;
            }

            $transactionData = [
                'user_wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'description' => $description,
                'transaction_type' => 'debit',
                'user_id' => $request->user_id,
                'status' => true,
                'maturity_time' => now(),
            ];

            // رفع الملف إذا كان موجود
            if ($request->hasFile('receipt')) {
                $transactionData['image'] = FileHelper::uploadFile($request->file("receipt"), 'user-wallets/withdrawals');
            }

            UserWalletTransaction::create($transactionData);

            DB::commit();
            return response()->json([
                'status' => 1,
                'success' => __('Withdrawal processed successfully. Amount: ') . number_format($request->amount, 2) . ' SAR'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    /**
     * إحصائيات المحفظة
     */
    public function getWalletStats($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $wallet = $user->userWallet;

            if (!$wallet) {
                return response()->json([
                    'status' => 2,
                    'error' => __('Wallet not found')
                ]);
            }

            $stats = [
                'balance' => $wallet->balance,
                'total_credit' => $wallet->credit,
                'total_debit' => $wallet->debit,
                'transactions_count' => $wallet->transactions()->count(),
                'last_transaction' => $wallet->last_transaction,
            ];

            return response()->json(['status' => 1, 'data' => $stats]);

        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }


    public function destroyTransaction(Request $req)
    {
        DB::beginTransaction();
        try {
            $find = UserWalletTransaction::find($req->id);
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
    public function clearWallet(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $wallet = $user->userWallet;

            if (!$wallet) {
                return response()->json(['status' => 2, 'error' => 'المحفظة غير موجودة.']);
            }

            // حذف جميع الحركات المرتبطة بالمحفظة
            UserWalletTransaction::where('user_wallet_id', $wallet->id)->delete();

            return response()->json(['status' => 1, 'success' => 'تمت تصفية المحفظة بنجاح (حذف جميع الحركات).']);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }
}
