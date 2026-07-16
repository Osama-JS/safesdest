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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Task;
use App\Models\InvestmentContract;
use App\Models\InvestorWallet;
use App\Services\InvestorPaymentService;


class UserWalletsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_beneficiaries_wallet', ['only' => ['show', 'getTransactions']]);
        $this->middleware('permission:transaction_beneficiaries_wallet', [
            'only' => [
                'addTransaction',
                'editTransaction',
                'destroyTransaction',
                'processWithdrawal',
                'reinvestProfits',
            ]
        ]);
    }

    /**
     * عرض محفظة المستخدم
     */
    public function show($userId)
    {
        try {
            $user = User::with(['userWallet', 'investorWallet', 'activeInvestmentContract'])->findOrFail($userId);
            $wallet = $user->userWallet;

            // إنشاء محفظة تلقائية إذا لم تكن موجودة
            if (!$wallet) {
                $wallet = $this->createWallet($userId);
            }

            // التحقق مما إذا كان المستخدم وسيطاً نشطاً لعقد استثماري
            $isBroker = InvestmentContract::where('broker_id', $userId)
                ->where('status', 'active')
                ->exists();

            // التحقق مما إذا كان المستخدم وسيط شاحنات
            $isTruckBroker = \App\Models\Driver::where('broker_id', $userId)->exists() ||
                \App\Models\Task::where('broker_id', $userId)->exists();

            $isInvestor = (bool) $user->investor;
            $withdrawableBalance = $isInvestor ? $wallet->withdrawable_balance : null;
            $investmentWallet = $isInvestor ? $user->investorWallet : null;
            $investmentWalletBalance = $investmentWallet?->balance ?? 0;
            $activeContract = $isInvestor ? $user->activeInvestmentContract : null;

            $duplicateCommissions = collect();
            $negativeCommissions = collect();

            if ($wallet && $isInvestor) {
                // 1. Duplicate commissions (multiple credits for same task)
                $duplicateTaskIds = UserWalletTransaction::where('user_wallet_id', $wallet->id)
                    ->where('transaction_type', 'credit')
                    ->whereNotNull('task_id')
                    ->select('task_id')
                    ->groupBy('task_id')
                    ->havingRaw('COUNT(id) > 1')
                    ->pluck('task_id');

                if ($duplicateTaskIds->isNotEmpty()) {
                    $duplicateCommissions = UserWalletTransaction::where('user_wallet_id', $wallet->id)
                        ->where('transaction_type', 'credit')
                        ->whereIn('task_id', $duplicateTaskIds)
                        ->with('task')
                        ->get()
                        ->groupBy('task_id');
                }

                // 2. Negative/Mismatch commissions (Debit > Credit)
                $negativeTaskIds = UserWalletTransaction::where('user_wallet_id', $wallet->id)
                    ->whereNotNull('task_id')
                    ->select('task_id')
                    ->groupBy('task_id')
                    ->havingRaw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) < 0")
                    ->pluck('task_id');

                if ($negativeTaskIds->isNotEmpty()) {
                    $negativeCommissions = UserWalletTransaction::where('user_wallet_id', $wallet->id)
                        ->whereIn('task_id', $negativeTaskIds)
                        ->orderBy('created_at', 'desc')
                        ->with('task')
                        ->get()
                        ->groupBy('task_id');
                }
            }

            $fromDate = request()->input('from_date');
            $toDate = request()->input('to_date');

            $queryBase = clone $wallet->transactions();

            $queryInPeriod = clone $queryBase;
            if ($fromDate && $toDate) {
                $queryInPeriod->whereBetween('created_at', [
                    Carbon::parse($fromDate)->startOfDay(),
                    Carbon::parse($toDate)->endOfDay()
                ]);
            }

            // Credit (Deposits/Commissions) - ONLY within date range
            $credit = (float) (clone $queryInPeriod)
                ->where('transaction_type', 'credit')
                ->sum('amount');

            // Debit (Withdrawals) - ONLY within date range
            $debit = (float) (clone $queryInPeriod)
                ->where('transaction_type', 'debit')
                ->sum('amount');

            // Available Balance (Actual Balance for Investor Commission Wallet)
            // It remains the actual Withdrawable Balance up to now, regardless of date range.
            // But we can limit it by to_date if we want point-in-time, however, $withdrawableBalance logic in Model is complex.
            // As decided, Balance is not filtered by from_date, but let's keep the actual current withdrawableBalance as it is.
            $balance = $wallet->balance;

            return view('admin.user-wallets.show', [
                'user' => $user,
                'wallet' => $wallet,
                'balance' => $balance,
                'credit' => $credit,
                'debit' => $debit,
                'lastTransaction' => $wallet->last_transaction,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'isBroker' => $isBroker,
                'isTruckBroker' => $isTruckBroker,
                'isInvestor' => $isInvestor,
                'withdrawableBalance' => $withdrawableBalance,
                'investmentWalletBalance' => $investmentWalletBalance,
                'hasInvestmentWallet' => (bool) $investmentWallet,
                'activeContract' => $activeContract,
                'duplicateCommissions' => $duplicateCommissions,
                'negativeCommissions' => $negativeCommissions,
            ]);

        } catch (Exception $e) {
            return redirect()->back()->with('error', __('User not found'));
        }
    }

    /**
     * تصدير العمليات إلى ملف Excel
     */
    public function exportExcel(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\UserWalletExport($userId, $request->from_date, $request->to_date),
                'user_wallet_' . $user->name . '_' . date('Y-m-d') . '.xlsx'
            );
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء التصدير: ' . $e->getMessage());
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

            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
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
            }

            if (!empty($type) && $type != 'all') {
                $query->where('transaction_type', $type);
            }

            $totalFiltered = $query->count();

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
                'error' => $validator->errors()
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
                if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
                    return response()->json(['status' => 2, 'error' => __('كلمة المرور الخاصة بالمشرف غير صحيحة.')]);
                }

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

            // للمستثمر بالمهام: يُستخدم الرصيد القابل للسحب الفعلي (المهام المسواة فقط)
            // للمستثمر العام: يساوي الرصيد الدفتري الكامل
            $currentBalance = $wallet->withdrawable_balance;
            $debtCeiling = $wallet->debt_ceiling;
            $requestedAmount = $request->amount;

            // حساب الرصيد بعد السحب
            $balanceAfterWithdrawal = $currentBalance - $requestedAmount;

            // التحقق من عدم تجاوز الرصيد المتاح للسحب
            if ($balanceAfterWithdrawal < 0) {
                return response()->json([
                    'status' => 2,
                    'error' => __('Withdrawal amount exceeds the available withdrawable balance. Maximum withdrawal allowed: ') .
                        number_format($currentBalance, 2) . ' SAR'
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
                // محمي بكلمة مرور وإيميل الإدارة
                $password = $req->input('password');
                $email = auth()->user()->email;

                if ($email !== 'osama.samomy@gmail.com' || $password !== 'osama@1998') {
                    return response()->json([
                        'status' => 2,
                        'error' => __('لا تملك الصلاحية لحذف هذه العمولة المرتبطة بمهمة، أو كلمة المرور غير صحيحة.')
                    ]);
                }
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

    public function destroyDuplicateCommission(Request $req)
    {
        DB::beginTransaction();
        try {
            $find = UserWalletTransaction::find($req->id);
            if (!$find || $find->transaction_type !== 'credit' || !$find->task_id) {
                return response()->json([
                    'status' => 2,
                    'error' => __('Invalid transaction or not a commission.')
                ]);
            }

            $done = $find->delete();

            if (!$done) {
                DB::rollBack();
                return response()->json(['status' => 2, 'error' => 'Error deleting transaction']);
            }
            DB::commit();
            return response()->json(['status' => 1, 'success' => __('Duplicate commission deleted successfully.')]);
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
    public function searchTaskForCommission(Request $request, $userId)
    {
        try {
            $taskId = $request->task_id;
            $task = Task::with(['ad', 'customer'])->find($taskId);

            if (!$task) {
                return response()->json(['status' => 0, 'error' => 'المهمة غير موجودة.']);
            }

            $user = User::findOrFail($userId);
            $wallet = $user->userWallet;

            // التحقق من وجود مضارب آخر
            $fundedByOther = $task->investor_id && $task->investor_id != $userId;

            // التحقق هل تم الاحتساب مسبقاً لهذا المستخدم
            $alreadyCalculated = UserWalletTransaction::where('user_wallet_id', $wallet->id)
                ->where('task_id', $taskId)
                ->where('transaction_type', 'credit')
                ->exists();

            // حساب عمولة المنصة المتوقعة
            $platformCut = 0;
            if ($task->ad) {
                if ($task->ad->service_commission_type == 1) {
                    $platformCut = (float) $task->ad->service_commission;
                } else {
                    $platformCut = ($task->total_price * $task->ad->service_commission) / 100;
                }
            } else {
                $platformCut = (float) $task->commission;
            }

            return response()->json([
                'status' => 1,
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'customer_name' => $task->customer->name ?? 'غير معروف',
                    'total_price' => $task->total_price,
                    'platform_cut' => $platformCut,
                    'is_closed' => $task->closed,
                    'investor_id' => $task->investor_id,
                ],
                'funded_by_other' => $fundedByOther,
                'already_calculated' => $alreadyCalculated,
                'is_cancelled' => $task->status === 'canceled',
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    public function calculateManualCommission(Request $request, $userId)
    {
        DB::beginTransaction();
        try {
            $taskId = $request->task_id;
            $task = Task::with('ad')->findOrFail($taskId);
            $investor = User::findOrFail($userId);
            $personalWallet = $investor->userWallet;
            $contract = $investor->activeInvestmentContract;

            if (!$contract) {
                return response()->json(['status' => 0, 'error' => 'لا يوجد عقد نشط لهذا المضارب.']);
            }

            // شرط عدم التداخل مع مستثمر آخر
            if ($task->investor_id && $task->investor_id != $userId) {
                return response()->json(['status' => 0, 'error' => 'هذه المهمة ممولة من مضارب آخر.']);
            }

            // منع التكرار (السماح باحتساب العمولة إذا تم عمل Refund لها سابقاً وصافي العمولة = 0)
            $netCommission = UserWalletTransaction::where('user_wallet_id', $personalWallet->id)
                ->where('task_id', $taskId)
                ->selectRaw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as net_amount")
                ->value('net_amount') ?? 0;

            if ($netCommission > 0) {
                return response()->json(['status' => 0, 'error' => 'تم احتساب عمولة هذه المهمة مسبقاً.']);
            }

            // حساب مبلغ عمولة المنصة الفعلي
            $platformCut = 0;
            if ($task->ad) {
                if ($task->ad->service_commission_type == 1) {
                    $platformCut = (float) $task->ad->service_commission;
                } else {
                    $platformCut = ($task->total_price * $task->ad->service_commission) / 100;
                }
            } else {
                $platformCut = (float) $task->commission;
            }

            if ($platformCut <= 0) {
                return response()->json(['status' => 0, 'error' => 'لا توجد عمولة للمنصة في هذه المهمة.']);
            }

            // حساب نصيب المضارب
            $investorCommission = $contract->calculateCommission($platformCut);

            if ($investorCommission <= 0) {
                return response()->json(['status' => 0, 'error' => 'عمولة المضارب تساوي صفراً بناءً على العقد.']);
            }

            UserWalletTransaction::create([
                'user_wallet_id' => $personalWallet->id,
                'task_id' => $task->id,
                'amount' => $investorCommission,
                'transaction_type' => 'credit',
                'description' => "عمولة المضارب من المهمة رقم #{$task->id}",
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            return response()->json(['status' => 1, 'success' => 'تم احتساب العمولة اليدوية بنجاح.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }
    /**
     * إعادة استثمار أرباح المضارب من محفظة العمولات إلى محفظة المضاربة (بواسطة الإدارة)
     */
    public function reinvestProfits(Request $request, $userId, InvestorPaymentService $paymentService)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ], [
            'amount.required' => __('Amount required'),
            'amount.min' => __('Minimum amount 0.01 SAR'),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'errors' => $validator->errors()]);
        }

        try {
            $user = User::findOrFail($userId);

            if (!$user->investor) {
                return response()->json(['status' => 0, 'error' => __('This user is not an investor.')]);
            }

            if (!$user->investorWallet) {
                InvestorWallet::create(['user_id' => $user->id, 'status' => true]);
                $user->load('investorWallet');
            }

            $paymentService->reinvestProfits(
                $user,
                (float) $request->amount,
                true,
                $request->notes
            );

            $user->load(['userWallet', 'investorWallet']);

            return response()->json([
                'status' => 1,
                'success' => __('Reinvestment successful message', [
                    'amount' => number_format((float) $request->amount, 2),
                    'balance' => number_format($user->userWallet->withdrawable_balance, 2),
                ]),
                'withdrawable_balance' => $user->userWallet->withdrawable_balance,
                'investment_wallet_balance' => $user->investorWallet->balance,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    public function calculateGeneralCommissions(Request $request, $userId, InvestorPaymentService $paymentService)
    {
        try {
            $investor = User::findOrFail($userId);
            $contract = $investor->activeInvestmentContract;

            if (!$contract || $contract->contract_type !== 'general_investment') {
                return response()->json(['status' => 0, 'error' => 'هذه الميزة متاحة للمضارب العام فقط.']);
            }

            $result = $paymentService->calculateGeneralCommissions($investor, $contract);

            if ($result['count'] === 0) {
                return response()->json(['status' => 1, 'info' => 'لا توجد مهام جديدة لاحتساب عمولاتها.']);
            }

            return response()->json([
                'status' => 1,
                'success' => "تم احتساب عمولات {$result['count']} مهمة بإجمالي " . number_format($result['total_commission'], 2) . " ر.س"
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    public function calculateTasksCommissions(Request $request, $userId, InvestorPaymentService $paymentService)
    {
        try {
            $investor = User::findOrFail($userId);
            $contract = $investor->activeInvestmentContract;

            if (!$contract || $contract->contract_type !== 'task_investment') {
                return response()->json(['status' => 0, 'error' => 'هذه الميزة متاحة للمضارب بالمهام فقط.']);
            }

            $result = $paymentService->calculateTasksCommissions($investor, $contract);

            if ($result['count'] === 0) {
                return response()->json(['status' => 1, 'info' => 'لا توجد مهام جديدة لاحتساب عمولاتها.']);
            }

            return response()->json([
                'status' => 1,
                'success' => "تم احتساب عمولات {$result['count']} مهمة بإجمالي " . number_format($result['total_commission'], 2) . " ر.س"
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    public function calculateBrokerCommissions(Request $request, $userId)
    {
        DB::beginTransaction();
        try {
            $broker = User::findOrFail($userId);
            $brokerWallet = $broker->userWallet;

            if (!$brokerWallet) {
                $brokerWallet = $this->createWallet($userId);
            }

            // جلب عقود المضاربة النشطة المرتبطة بالوسيط
            $contracts = InvestmentContract::where('broker_id', $userId)
                ->where('status', 'active')
                ->get();

            if ($contracts->isEmpty()) {
                return response()->json([
                    'status' => 0,
                    'error' => 'هذا المستخدم ليس وسيطاً نشطاً لأي عقد مضاربة حالي.'
                ]);
            }

            $processedTasksCount = 0;
            $totalCommissionCredited = 0;

            foreach ($contracts as $contract) {
                $investorId = $contract->user_id;

                // جلب المهام الممولة من هذا المضارب والتي تندرج تحت فترة هذا العقد
                $query = Task::where('investor_id', $investorId)
                    ->whereIn('payment_status', ['paid', 'completed'])
                    ->where('created_at', '>=', $contract->start_date->startOfDay());

                if ($contract->end_date) {
                    $query->where('created_at', '<=', $contract->end_date->endOfDay());
                }

                $tasks = $query->get();

                foreach ($tasks as $task) {
                    // فحص إذا كان العقد يتطلب تصفية عملاء محددين
                    if ($contract->filter_customer_ids && count($contract->filter_customer_ids) > 0) {
                        if (!in_array($task->customer_id, $contract->filter_customer_ids)) {
                            continue;
                        }
                    }

                    // فحص إذا كان هناك حد أدنى لعمولة المنصة
                    $platformCut = 0;
                    if ($task->ad) {
                        if ($task->ad->service_commission_type == 1) {
                            $platformCut = (float) $task->ad->service_commission;
                        } else {
                            $platformCut = ($task->total_price * $task->ad->service_commission) / 100;
                        }
                    } else {
                        $platformCut = (float) $task->commission;
                    }

                    if ($contract->min_commission_threshold && $platformCut < $contract->min_commission_threshold) {
                        continue;
                    }

                    // تحقق من أن عمولة الوسيط لم تُحتسب مسبقاً لهذه المهمة في محفظته
                    $exists = UserWalletTransaction::where('user_wallet_id', $brokerWallet->id)
                        ->where('task_id', $task->id)
                        ->where('transaction_type', 'credit')
                        ->where('description', 'LIKE', '%عمولة وسيط%')
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // احتساب عمولة الوسيط الرياضية بناءً على خيارات العقد
                    $brokerShare = 0;

                    if ($contract->broker_commission_source === 'investor_commission') {
                        // من حصة المضارب
                        $investorCommission = $contract->calculateCommission($platformCut);
                        if ($contract->broker_commission_type === 'percentage') {
                            $brokerShare = ($investorCommission * $contract->broker_commission_value) / 100;
                        } else {
                            $brokerShare = (float) $contract->broker_commission_value;
                        }
                        // حماية ألا تزيد حصة الوسيط عن عمولة المضارب نفسها
                        $brokerShare = min($brokerShare, $investorCommission);
                    } else {
                        // من عمولة المهمة (المنصة)
                        if ($contract->broker_commission_type === 'percentage') {
                            $brokerShare = ($platformCut * $contract->broker_commission_value) / 100;
                        } else {
                            $brokerShare = (float) $contract->broker_commission_value;
                        }
                        // حماية المنصة: يجب ألا يتجاوز مجموع حصة المضارب وحصة الوسيط عمولة المنصة
                        $investorCommission = $contract->calculateCommission($platformCut);
                        if ($investorCommission + $brokerShare > $platformCut) {
                            $brokerShare = max(0.00, $platformCut - $investorCommission);
                        }
                    }

                    if ($brokerShare <= 0) {
                        continue;
                    }

                    // تسجيل الحركة الائتمانية في محفظة الوسيط
                    UserWalletTransaction::create([
                        'user_wallet_id' => $brokerWallet->id,
                        'task_id' => $task->id,
                        'amount' => $brokerShare,
                        'transaction_type' => 'credit',
                        'description' => "عمولة وسيط: تسويق المضارب {$contract->investor->name} للمهمة رقم #{$task->id}",
                        'created_by' => Auth::id(),
                        'status' => true,
                    ]);

                    $processedTasksCount++;
                    $totalCommissionCredited += $brokerShare;
                }
            }

            if ($processedTasksCount === 0) {
                DB::rollBack();
                return response()->json([
                    'status' => 1,
                    'info' => 'لا توجد مهام جديدة غير محتسبة تندرج تحت شروط عقود مضاربة هذا الوسيط.'
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 1,
                'success' => "تم احتساب العمولات بنجاح لـ {$processedTasksCount} مهمة بإجمالي " . number_format($totalCommissionCredited, 2) . " ر.س وتمت إضافتها لمحفظة الوسيط."
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    /**
     * حساب عمولات وسطاء الشاحنات
     */
    public function calculateTruckBrokerCommissions(Request $request, $userId)
    {
        DB::beginTransaction();
        try {
            $broker = User::findOrFail($userId);
            $brokerWallet = $broker->userWallet;

            if (!$brokerWallet) {
                $brokerWallet = $this->createWallet($userId);
            }

            // 1. استخراج المهام المرتبطة مباشرة بالوسيط
            $directTasks = Task::with(['brokers', 'driver.brokers'])->whereHas('brokers', function($q) use ($userId) {
                    $q->where('users.id', $userId);
                })
                ->whereNotIn('status', ['canceled', 'cancelled', 'refund', 'refound', 'refunded'])
                ->get();

            // 2. استخراج المهام للسائقين المرتبطين بالوسيط
            $drivers = \App\Models\Driver::with('brokers')->whereHas('brokers', function($q) use ($userId) {
                    $q->where('users.id', $userId);
                })->get();
                
            $driverTaskIds = [];
            foreach ($drivers as $driver) {
                $driverBrokerPivot = $driver->brokers->where('id', $userId)->first()->pivot;
                
                $query = Task::with(['brokers', 'driver.brokers'])->where('driver_id', $driver->id)
                    ->doesntHave('brokers') // No direct brokers
                    ->whereNotIn('status', ['canceled', 'cancelled', 'refund', 'refound', 'refunded']);

                if ($driverBrokerPivot->commission_start_date) {
                    $query->where('created_at', '>=', $driverBrokerPivot->commission_start_date);
                }

                $dTasks = $query->get();
                foreach ($dTasks as $dt) {
                    $driverTaskIds[$dt->id] = $dt;
                }
            }

            $allTasks = collect($directTasks)->keyBy('id')->merge(collect($driverTaskIds)->keyBy('id'));

            $totalTasksFound = $allTasks->count();
            $closedTasksCount = 0;
            $openTasksCount = 0;
            $duplicateCommissionsCount = 0;
            $processedTasksCount = 0;
            $totalCommissionCredited = 0;

            Log::info("--- Starting calculateTruckBrokerCommissions for Broker ID: {$userId} ---");
            Log::info("Total tasks found linked to this broker (direct or via driver): {$totalTasksFound}");

            // 📝 طباعة تفاصيل كل مهمة لغرض التتبع
            foreach ($allTasks as $task) {
                $isClosed = $task->closed == 1 ? 'Yes' : 'No';
                Log::info("Task ID: {$task->id} | Status: {$task->status} | Closed: {$isClosed}");
            }

            foreach ($allTasks as $task) {
                if ($task->closed != 1) {
                    $openTasksCount++;
                    continue;
                }
                
                $closedTasksCount++;

                // منع التكرار
                $exists = UserWalletTransaction::where('user_wallet_id', $brokerWallet->id)
                    ->where('task_id', $task->id)
                    ->where('transaction_type', 'credit')
                    ->where('description', 'LIKE', '%عمولة وساطة شاحنات%')
                    ->exists();

                if ($exists) {
                    $duplicateCommissionsCount++;
                    continue;
                }

                $platformCut = 0;
                if ($task->ad) {
                    if ($task->ad->service_commission_type == 1) {
                        $platformCut = (float) $task->ad->service_commission;
                    } else {
                        $platformCut = ($task->total_price * $task->ad->service_commission) / 100;
                    }
                } else {
                    $platformCut = (float) $task->commission;
                }

                $activeBrokers = $task->brokers->count() > 0 ? $task->brokers : ($task->driver ? $task->driver->brokers : collect());
                
                $totalBrokersShare = 0;
                $currentBrokerShare = 0;
                
                foreach($activeBrokers as $ab) {
                     $cType = $ab->pivot->commission_type;
                     $cValue = (float) $ab->pivot->commission_value;
                     
                     $share = 0;
                     if ($cType === 'percentage') {
                         $share = ($platformCut * $cValue) / 100;
                     } else {
                         $share = $cValue;
                     }
                     $totalBrokersShare += $share;
                     
                     if ($ab->id == $userId) {
                         $currentBrokerShare = $share;
                     }
                }
                
                if ($totalBrokersShare > $platformCut) {
                     Log::error("Task {$task->id}: Total brokers commission ({$totalBrokersShare}) exceeds platform cut ({$platformCut}). No commissions will be paid.");
                     continue;
                }
                
                $brokerShare = $currentBrokerShare;

                if ($brokerShare <= 0) {
                    continue;
                }

                UserWalletTransaction::create([
                    'user_wallet_id' => $brokerWallet->id,
                    'task_id' => $task->id,
                    'amount' => $brokerShare,
                    'transaction_type' => 'credit',
                    'description' => "عمولة وساطة شاحنات للمهمة رقم #{$task->id}",
                    'created_by' => Auth::id(),
                    'status' => true,
                ]);

                $processedTasksCount++;
                $totalCommissionCredited += $brokerShare;
            }

            if ($processedTasksCount === 0) {
                DB::rollBack();
                Log::info("Broker ID: {$userId} | Summary: Total: {$totalTasksFound}, Open: {$openTasksCount}, Closed: {$closedTasksCount}, Duplicates: {$duplicateCommissionsCount}, Processed: 0");
                return response()->json([
                    'status' => 1,
                    'info' => 'لا توجد مهام جديدة غير محتسبة لاحتساب عمولة وساطة الشاحنات عليها.'
                ]);
            }

            Log::info("Broker ID: {$userId} | Summary:");
            Log::info("Total Tasks Found: {$totalTasksFound}");
            Log::info("Open Tasks (Skipped): {$openTasksCount}");
            Log::info("Closed Tasks: {$closedTasksCount}");
            Log::info("Duplicate Commissions (Already Paid): {$duplicateCommissionsCount}");
            Log::info("Processed Tasks (Newly Paid): {$processedTasksCount}");
            Log::info("Total Commission Credited: {$totalCommissionCredited}");

            DB::commit();
            return response()->json([
                'status' => 1,
                'success' => "تم احتساب عمولات وساطة الشاحنات بنجاح لـ {$processedTasksCount} مهمة بإجمالي " . number_format($totalCommissionCredited, 2) . " ر.س وتمت إضافتها لمحفظة الوسيط."
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    /**
     * حساب عمولات وسطاء الشاحنات بالطريقة القديمة
     */
    private function getValidLegacyBrokerTasks($userId)
    {
        $broker = User::findOrFail($userId);
        $brokerWallet = $broker->userWallet;

        if (!$brokerWallet) {
            $brokerWallet = $this->createWallet($userId);
        }

        // 1. استخراج المهام المرتبطة مباشرة بالوسيط في النظام القديم
        $directTasks = Task::with(['ad', 'driver'])->where('broker_id', $userId)
            ->whereNotIn('status', ['canceled', 'cancelled', 'refund', 'refound', 'refunded'])
            ->get();

        // 2. استخراج المهام للسائقين المرتبطين بالوسيط بالنظام القديم
        $drivers = \App\Models\Driver::where('broker_id', $userId)->get();
        $driverTaskIds = [];
        foreach ($drivers as $driver) {
            $query = Task::with(['ad', 'driver'])->where('driver_id', $driver->id)
                ->whereNull('broker_id') // تجنب التكرار لو كانت المهمة مرتبطة بوسيط آخر
                ->whereNotIn('status', ['canceled', 'cancelled', 'refund', 'refound', 'refunded']);

            if ($driver->broker_commission_start_date) {
                $query->where('created_at', '>=', $driver->broker_commission_start_date);
            }

            $dTasks = $query->get();
            foreach ($dTasks as $dt) {
                $driverTaskIds[$dt->id] = $dt;
            }
        }

        $allTasks = collect($directTasks)->keyBy('id')->merge(collect($driverTaskIds)->keyBy('id'));

        $validTasks = [];
        foreach ($allTasks as $task) {
            if ($task->closed != 1) {
                continue;
            }

            // منع التكرار
            $exists = UserWalletTransaction::where('user_wallet_id', $brokerWallet->id)
                ->where('task_id', $task->id)
                ->where('transaction_type', 'credit')
                ->where('description', 'LIKE', '%عمولة وساطة شاحنات%')
                ->exists();

            if ($exists) {
                continue;
            }

            $platformCut = 0;
            if ($task->ad) {
                if ($task->ad->service_commission_type == 1) {
                    $platformCut = (float) $task->ad->service_commission;
                } else {
                    $platformCut = ($task->total_price * $task->ad->service_commission) / 100;
                }
            } else {
                $platformCut = (float) $task->commission;
            }

            $brokerShare = 0;
            $cType = null;
            $cValue = 0;

            if ($task->broker_id == $userId) {
                $cType = $task->broker_commission_type;
                $cValue = (float) $task->broker_commission_value;
            } else {
                $driver = $task->driver;
                if ($driver && $driver->broker_id == $userId) {
                    $cType = $driver->broker_commission_type;
                    $cValue = (float) $driver->broker_commission_value;
                }
            }

            if (!$cType || $cValue <= 0) {
                continue;
            }

            if ($cType === 'percentage') {
                $brokerShare = ($platformCut * $cValue) / 100;
            } else {
                $brokerShare = $cValue;
            }

            if ($brokerShare <= 0) {
                continue;
            }

            $validTasks[] = [
                'task' => $task,
                'brokerShare' => $brokerShare,
                'walletId' => $brokerWallet->id
            ];
        }

        return $validTasks;
    }

    /**
     * عرض مهام عمولة وساطة الشاحنات بالنظام القديم قبل احتسابها
     */
    public function previewOldTruckBrokerCommissions($userId)
    {
        if (auth()->user()->email !== 'osama.samomy@gmail.com') {
            return response()->json(['status' => 0, 'error' => 'غير مصرح']);
        }

        $validTasks = $this->getValidLegacyBrokerTasks($userId);

        $previewData = [];
        $totalCommission = 0;

        foreach ($validTasks as $item) {
            $task = $item['task'];
            $brokerShare = $item['brokerShare'];
            $previewData[] = [
                'task_id' => $task->id,
                'total_price' => number_format($task->total_price, 2),
                'commission' => number_format($brokerShare, 2),
                'date' => $task->created_at->format('Y-m-d')
            ];
            $totalCommission += $brokerShare;
        }

        return response()->json([
            'status' => 1,
            'tasks' => $previewData,
            'total_commission' => number_format($totalCommission, 2)
        ]);
    }

    /**
     * استخراج مهام عمولة وساطة الشاحنات بالنظام القديم إلى ملف Excel (CSV)
     */
    public function exportOldTruckBrokerCommissions($userId)
    {
        if (auth()->user()->email !== 'osama.samomy@gmail.com') {
            abort(403);
        }

        $validTasks = $this->getValidLegacyBrokerTasks($userId);

        $filename = "broker_commissions_preview_{$userId}_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($validTasks) {
            $file = fopen('php://output', 'w');
            
            // إضافة دعم اللغة العربية لملف CSV
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['رقم المهمة', 'إجمالي السعر (ريال)', 'عمولة الوسيط (ريال)', 'التاريخ']);

            $totalCommission = 0;
            foreach ($validTasks as $item) {
                $task = $item['task'];
                $brokerShare = $item['brokerShare'];
                fputcsv($file, [
                    $task->id,
                    $task->total_price,
                    $brokerShare,
                    $task->created_at->format('Y-m-d')
                ]);
                $totalCommission += $brokerShare;
            }

            fputcsv($file, ['الإجمالي', '', $totalCommission, '']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * حساب عمولات وسطاء الشاحنات بالطريقة القديمة
     */
    public function calculateOldTruckBrokerCommissions(Request $request, $userId)
    {
        DB::beginTransaction();
        try {
            // حماية العملية
            $password = $request->input('password');
            $email = auth()->user()->email;

            if ($email !== 'osama.samomy@gmail.com' || $password !== 'osama@1998') {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'error' => 'لا تملك الصلاحية لإجراء هذه العملية، أو كلمة المرور غير صحيحة.'
                ]);
            }

            $validTasks = $this->getValidLegacyBrokerTasks($userId);
            
            if (empty($validTasks)) {
                DB::rollBack();
                return response()->json([
                    'status' => 1,
                    'info' => 'لا توجد مهام جديدة بالنظام القديم لاحتساب عمولة وساطة الشاحنات عليها.'
                ]);
            }

            $processedTasksCount = 0;
            $totalCommissionCredited = 0;

            foreach ($validTasks as $item) {
                $task = $item['task'];
                $brokerShare = $item['brokerShare'];
                $walletId = $item['walletId'];

                UserWalletTransaction::create([
                    'user_wallet_id' => $walletId,
                    'task_id' => $task->id,
                    'amount' => $brokerShare,
                    'transaction_type' => 'credit',
                    'description' => "عمولة وساطة شاحنات للمهمة رقم #{$task->id}",
                    'created_by' => Auth::id(),
                    'status' => true,
                ]);

                $processedTasksCount++;
                $totalCommissionCredited += $brokerShare;
            }

            DB::commit();
            return response()->json([
                'status' => 1,
                'success' => "تم احتساب عمولات وساطة الشاحنات (نظام قديم) بنجاح لـ {$processedTasksCount} مهمة بإجمالي " . number_format($totalCommissionCredited, 2) . " ر.س وتمت إضافتها لمحفظة الوسيط."
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }
    /**
     * عرض المهام المتاحة للدفع لمستثمر معين من لوحة الإدارة
     */
    public function tasksForFunding(Request $request, $userId)
    {
        $investor = User::with(['investorWallet', 'activeInvestmentContract'])->findOrFail($userId);
        $contract = $investor->activeInvestmentContract;

        if (!$contract) {
            return redirect()->back()
                ->with('error', __('Task payment page only for task investors'));
        }

        $investorWallet = $investor->investorWallet;
        $walletBalance = $investorWallet?->balance ?? 0;

        $query = Task::availableForInvestorPayment()
            ->whereNotNull('customer_id')
            ->with(['customer', 'pickup', 'delivery', 'ad'])
            ->latest();

        // فلتر العملاء المخصصين
        if (!empty($contract->filter_customer_ids)) {
            $query->whereIn('customer_id', $contract->filter_customer_ids);
        }

        // فلتر الحد الأدنى لعمولة المنصة
        if ($contract->min_commission_threshold > 0) {
            $query->where(function ($q) use ($contract) {
                $q->where('commission', '>=', $contract->min_commission_threshold)
                    ->orWhereHas('ad', function ($sub) use ($contract) {
                        $sub->where('service_commission', '>=', $contract->min_commission_threshold);
                    });
            });
        }

        // فلتر البحث
        if ($request->search) {
            $query->where('id', 'like', "%{$request->search}%");
        }

        $tasks = $query->paginate(15)->withQueryString();

        return view('admin.user-wallets.tasks-funding', compact(
            'investor',
            'contract',
            'tasks',
            'walletBalance'
        ));
    }

    /**
     * تمويل مهمة محددة لمستثمر من لوحة الإدارة
     */
    public function fundTask(Request $request, $userId, Task $task, InvestorPaymentService $paymentService)
    {
        $investor = User::findOrFail($userId);
        $contract = $investor->activeInvestmentContract;

        if (!$contract) {
            return back()->with('error', __('Not authorized'));
        }

        try {
            // يتم التمويل باستخدام خدمة التمويل دون طلب كلمة المرور في لوحة الإدارة
            $paymentService->payTask($investor, $task, $contract);
            return back()->with('success', __('Task paid successfully', ['id' => $task->id]));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
