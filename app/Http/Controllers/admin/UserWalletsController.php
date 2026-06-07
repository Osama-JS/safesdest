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
use App\Models\Task;
use App\Models\InvestmentContract;
use App\Models\InvestorWallet;
use App\Services\InvestorPaymentService;


class UserWalletsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_beneficiaries_wallet', ['only' => ['show', 'getTransactions']]);
        $this->middleware('permission:transaction_beneficiaries_wallet', ['only' => [
            'addTransaction',
            'editTransaction',
            'destroyTransaction',
            'processWithdrawal',
            'reinvestProfits',
        ]]);
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

            return view('admin.user-wallets.show', [
                'user' => $user,
                'wallet' => $wallet,
                'balance' => $wallet->balance,
                'credit' => $wallet->credit,
                'debit' => $wallet->debit,
                'lastTransaction' => $wallet->last_transaction,
                'isBroker' => $isBroker,
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
                    'error'  => __('Withdrawal amount exceeds the available withdrawable balance. Maximum withdrawal allowed: ') .
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

    public function destroyDuplicateCommission(Request $req)
    {
        DB::beginTransaction();
        try {
            $find = UserWalletTransaction::find($req->id);
            if (!$find || $find->transaction_type !== 'credit' || !$find->task_id) {
                return response()->json([
                  'status' => 2,
                  'error'  => __('Invalid transaction or not a commission.')
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
                'user_wallet_id'   => $personalWallet->id,
                'task_id'          => $task->id,
                'amount'           => $investorCommission,
                'transaction_type' => 'credit',
                'description'      => "عمولة المضارب من المهمة رقم #{$task->id}",
                'created_by'       => Auth::id(),
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
            'notes'  => 'nullable|string|max:255',
        ], [
            'amount.required' => __('Amount required'),
            'amount.min'      => __('Minimum amount 0.01 SAR'),
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
                'status'  => 1,
                'success' => __('Reinvestment successful message', [
                    'amount' => number_format((float) $request->amount, 2),
                    'balance' => number_format($user->userWallet->withdrawable_balance, 2),
                ]),
                'withdrawable_balance'   => $user->userWallet->withdrawable_balance,
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
                        'user_wallet_id'   => $brokerWallet->id,
                        'task_id'          => $task->id,
                        'amount'           => $brokerShare,
                        'transaction_type' => 'credit',
                        'description'      => "عمولة وسيط: تسويق المضارب {$contract->investor->name} للمهمة رقم #{$task->id}",
                        'created_by'       => Auth::id(),
                        'status'           => true,
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
        $walletBalance  = $investorWallet?->balance ?? 0;

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
            'investor', 'contract', 'tasks', 'walletBalance'
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
