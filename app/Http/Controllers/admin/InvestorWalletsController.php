<?php

namespace App\Http\Controllers\admin;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\InvestorWallet;
use App\Models\InvestorWalletTransaction;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\PdfService;
use App\Services\HyperPayPayoutService;

class InvestorWalletsController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService = null)
    {
        $this->pdfService = $pdfService;
        $this->middleware('permission:view_investors', ['only' => ['show', 'getTransactions']]);
        $this->middleware('permission:save_investors', ['only' => ['addTransaction', 'destroyTransaction', 'downloadReceipt']]);
    }

    /**
     * عرض محفظة الاستثمار
     */
    public function show($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $wallet = $user->investorWallet;

            if (!$wallet) {
                $wallet = InvestorWallet::create([
                    'user_id' => $userId,
                ]);
            }

            return view('admin.investors.wallets.invest', [
                'user' => $user,
                'wallet' => $wallet,
                'balance' => $wallet->balance,
                'credit' => $wallet->credit,
                'returned_capital' => $wallet->returned_capital,
                'debit' => $wallet->debit,
            ]);

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'المستثمر غير موجود');
        }
    }

    /**
     * جلب معاملات محفظة الاستثمار
     */
    public function getTransactions(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $wallet = $user->investorWallet;

            if (!$wallet) {
                return response()->json([
                    'draw' => intval($request->input('draw')),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ]);
            }

            $columns = [
                0 => 'id',
                1 => 'amount',
                2 => 'transaction_type',
                3 => 'description',
                4 => 'task_id',
                5 => 'created_at',
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

            $query = $wallet->transactions()->with(['task']);

            if ($fromDate && $toDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($fromDate)->startOfDay(),
                    Carbon::parse($toDate)->endOfDay()
                ]);
            }
            $searchValue = is_array($search) ? ($search['value'] ?? '') : $search;

            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('description', 'LIKE', "%{$searchValue}%")
                      ->orWhere('amount', 'LIKE', "%{$searchValue}%");
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
                $nestedData['amount'] = number_format($transaction->amount, 2);
                $nestedData['transaction_type'] = $transaction->transaction_type;
                $nestedData['source_type'] = $transaction->source_type;
                $nestedData['description'] = $transaction->description;
                $nestedData['attachment'] = $transaction->attachment;
                $nestedData['task_id'] = $transaction->task_id ?? '-';
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
     * إضافة أو تعديل معاملة (إيداع/خصم) لمحفظة الاستثمار
     */
    public function addTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:investor_wallet_transactions,id',
            'user' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:credit',
            'description' => 'required|string|max:255',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($request->user);
            $wallet = $user->investorWallet;

            if (!$wallet) {
                $wallet = InvestorWallet::create([
                    'user_id' => $user->id,
                ]);
            }

            $amount = $request->amount;
            $transaction = null;
            $oldAmount = 0;
            $oldType = 'credit';

            if ($request->id) {
                $transaction = InvestorWalletTransaction::findOrFail($request->id);
                if ($transaction->task_id) {
                    return response()->json(['status' => 2, 'error' => 'لا يمكن تعديل معاملة مرتبطة بمهمة.']);
                }

                if ($transaction->transaction_type === 'debit') {
                    return response()->json(['status' => 2, 'error' => 'لا يمكن تعديل عمليات التمويل/السحب.']);
                }

                $oldAmount = $transaction->amount;
                $oldType = $transaction->transaction_type;

                // Reverse old transaction impact on balance
                // balance is dynamic, no need to update
            }

            // Check if new balance is valid for debit
            $tempBalance = $wallet->balance; // Uses the accessor to get current actual balance

            // Adjust temp balance for modification of an existing transaction
            if ($transaction && $oldType === 'credit') {
                $tempBalance -= $oldAmount;
            } elseif ($transaction && $oldType === 'debit') {
                $tempBalance += $oldAmount;
            }

            if ($request->type === 'credit') {
                $tempBalance += $amount;
            } else {
                if ($tempBalance < $amount) {
                    return response()->json(['status' => 2, 'error' => 'رصيد المحفظة غير كافٍ لإجراء العملية.']);
                }
                $tempBalance -= $amount;
            }

            $data = [
                'investor_wallet_id' => $wallet->id,
                'amount'             => $amount,
                'transaction_type'   => $request->type,
                'source_type'        => 'capital',
                'description'        => $request->description,
                'performed_by'       => Auth::id(),
                'balance_after'      => $tempBalance,
            ];


            if ($request->hasFile('attachment')) {
                if ($transaction && $transaction->attachment) {
                    FileHelper::deleteFileIfExists($transaction->attachment);
                }
                $data['attachment'] = FileHelper::uploadFile($request->file("attachment"), 'investor-wallets/attachments');
            }

            if ($transaction) {
                $transaction->update($data);
                $msg = 'تم تحديث المعاملة بنجاح';
            } else {
                InvestorWalletTransaction::create($data);
                $msg = 'تم تسجيل المعاملة بنجاح';
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'success' => $msg
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    /**
     * تحويل عملية إيداع رأس مال إلى استعادة استثمار
     */
    public function convertTransactionToRefund(Request $request, $transactionId)
    {
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'كلمة المرور مطلوبة لتأكيد العملية.',
        ]);

        if ($request->password !== 'osama@1998') {
            return response()->json(['status' => 2, 'error' => 'كلمة المرور غير صحيحة.']);
        }

        try {
            DB::beginTransaction();

            $transaction = InvestorWalletTransaction::with('wallet')->findOrFail($transactionId);
            $wallet = $transaction->wallet;

            if (!$wallet) {
                return response()->json(['status' => 2, 'error' => 'المحفظة المرتبطة بهذه العملية غير موجودة.']);
            }

            if ($transaction->transaction_type !== 'credit' || $transaction->source_type === 'refund') {
                return response()->json(['status' => 2, 'error' => 'هذه العملية ليست إيداع رأس مال صالح للتحويل.']);
            }

            if ($transaction->task_id) {
                return response()->json(['status' => 2, 'error' => 'لا يمكن تحويل عملية مرتبطة بمهمة.']);
            }

            $transaction->update([
                'source_type' => 'refund',
                'description' => $transaction->description . ' | تم تحويل الإيداع إلى استعادة استثمار',
            ]);

            DB::commit();

            return response()->json(['status' => 1, 'success' => 'تم تحويل الإيداع إلى استعادة استثمار بنجاح.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    /**
     * حذف معاملة (فقط إذا لم تكن مرتبطة بمهمة)
     */
    public function destroyTransaction(Request $req)
    {
        try {
            DB::beginTransaction();
            $transaction = InvestorWalletTransaction::findOrFail($req->id);

            if ($transaction->task_id) {
                return response()->json(['status' => 2, 'error' => 'لا يمكن حذف معاملة مرتبطة بمهمة مدفوعة.']);
            }

            if ($transaction->transaction_type === 'debit') {
                return response()->json(['status' => 2, 'error' => 'لا يمكن حذف عمليات التمويل/السحب.']);
            }

            // Update balance before deleting (only for credits since debits are blocked above)
            $wallet = $transaction->wallet;
            // Balance is calculated dynamically, no need to update and save it.

            $transaction->delete();
            DB::commit();
            return response()->json(['status' => 1, 'success' => 'تم حذف المعاملة وتحديث الرصيد بنجاح']);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    /**
     * حذف معاملة التسوية من محفظة الاستثمار (مرتبطة بمهمة) - محمية بكلمة مرور
     */
    public function destroySettlementTransaction(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'كلمة المرور مطلوبة لتأكيد الحذف.',
        ]);

        if ($request->password !== 'osama@1998') {
            return response()->json(['status' => 2, 'error' => 'كلمة المرور غير صحيحة. لا يمكن تنفيذ العملية.']);
        }

        try {
            DB::beginTransaction();

            $transaction = InvestorWalletTransaction::with('wallet')->findOrFail($id);

            // التأكد من أنها معاملة تسوية (مرتبطة بمهمة)
            if (!$transaction->task_id) {
                return response()->json(['status' => 2, 'error' => 'هذه ليست معاملة تسوية مرتبطة بمهمة. استخدم خيار الحذف العادي.']);
            }

            $amount    = $transaction->amount;
            $type      = $transaction->transaction_type;
            $taskId    = $transaction->task_id;

            $transaction->delete();

            // إرجاع حالة التسوية في عملية التمويل الأصلية
            $fundingTx = \App\Models\Wallet_Transaction::where('task_id', $taskId)
                ->where('transaction_type', 'debit')
                ->first();

            if ($fundingTx) {
                $fundingTx->settled_amount -= $amount;
                if ($fundingTx->settled_amount < 0) {
                    $fundingTx->settled_amount = 0;
                }
                $fundingTx->is_settled = false;
                $fundingTx->save();
            }

            DB::commit();

            return response()->json([
                'status'  => 1,
                'success' => "تم حذف معاملة التسوية (المهمة #{$taskId}) بمبلغ {$amount} ر.س بنجاح.",
            ]);

        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    /**
     * تحميل إيصال المعاملة بصيغة PDF
     */
    public function downloadReceipt($transactionId)
    {
        try {
            $transaction = InvestorWalletTransaction::with(['wallet.investor', 'performer'])->findOrFail($transactionId);
            $wallet = $transaction->wallet;
            $user = $wallet->investor;
            $performer = $transaction->performer;

            // تحويل المبلغ إلى كلمات بالعربية
            $amountInWords = $this->convertNumberToArabicWords($transaction->amount);

            $fileName = "receipt_investor_{$user->id}_{$transaction->id}";

            return $this->pdfService->generate('admin.investors.wallets.receipt_pdf', [
                'transaction' => $transaction,
                'wallet' => $wallet,
                'user' => $user,
                'performer' => $performer,
                'amountInWords' => $amountInWords
            ], "{$fileName}.pdf", true);

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'فشل في توليد الإيصال: ' . $e->getMessage());
        }
    }

    /**
     * تحويل الأرقام إلى كلمات بالعربية
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

        if ($decimalPart > 0) {
            $result .= ' و' . $decimalPart . ' هللة';
        }

        return $result;
    }

    /**
     * فحص توافق تمويل المهام مع محفظة الاستثمار
     */
    public function checkFunding(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $wallet = $user->investorWallet;

            if (!$wallet) {
                return response()->json(['status' => 0, 'error' => __('المحفظة غير موجودة')]);
            }

            $anomalies = [];

            // 1. مهام مرتبطة بالمستثمر وليس لها عملية تمويل في المحفظة
            $tasksWithoutFunding = \App\Models\Task::where('investor_id', $userId)->get();

            foreach ($tasksWithoutFunding as $task) {
                $hasFunding = InvestorWalletTransaction::where('investor_wallet_id', $wallet->id)
                    ->where('task_id', $task->id)
                    ->where('transaction_type', 'debit')
                    ->exists();

                if (!$hasFunding) {
                    $anomalies[] = [
                        'type' => 'task_without_transaction',
                        'task_id' => $task->id,
                        'amount_needed' => $task->ad ? $task->ad->service_cost : 0, // أو القيمة الاستثمارية المناسبة للمهمة
                        'message' => "المهمة #{$task->id} مرتبطة بالمضارب ولكن لا يوجد لها عملية تمويل في محفظته."
                    ];
                }
            }

            // 2. عمليات تمويل في المحفظة لمهام غير مرتبطة بالمستثمر
            $fundingTransactions = InvestorWalletTransaction::where('investor_wallet_id', $wallet->id)
                ->where('transaction_type', 'debit')
                ->whereNotNull('task_id')
                ->get();

            foreach ($fundingTransactions as $transaction) {
                $task = \App\Models\Task::find($transaction->task_id);

                if (!$task || $task->investor_id != $userId) {
                    $anomalies[] = [
                        'type' => 'transaction_without_task',
                        'transaction_id' => $transaction->id,
                        'task_id' => $transaction->task_id,
                        'amount' => $transaction->amount,
                        'message' => "عملية التمويل #{$transaction->id} (للمهمة #{$transaction->task_id}) موجودة، ولكن المهمة غير مرتبطة بهذا المضارب."
                    ];
                }
            }

            return response()->json([
                'status' => 1,
                'anomalies' => $anomalies
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }

    /**
     * معالجة مشكلة من مشاكل التمويل
     */
    public function fixFunding(Request $request, $userId)
    {
        $request->validate([
            'anomaly_type' => 'required|string',
            'fix_action' => 'required|string',
            'task_id' => 'required|integer',
            'transaction_id' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($userId);
            $wallet = $user->investorWallet;

            if (!$wallet) {
                return response()->json(['status' => 0, 'error' => __('المحفظة غير موجودة')]);
            }

            $anomalyType = $request->anomaly_type;
            $fixAction = $request->fix_action;
            $taskId = $request->task_id;
            $transactionId = $request->transaction_id;

            if ($anomalyType === 'task_without_transaction') {
                $task = \App\Models\Task::findOrFail($taskId);

                if ($fixAction === 'unlink_task') {
                    $task->investor_id = null;
                    $task->save();
                    $message = "تم فصل المهمة #{$taskId} من هذا المضارب بنجاح.";
                } elseif ($fixAction === 'create_funding') {
                    // نحتاج لمعرفة كم التكلفة المفترض خصمها من محفظة الاستثمار
                    // يعتمد على إجمالي تكلفة المهمة
                    $cost = $task->ad ? $task->ad->service_cost : 0;
                    
                    if ($cost <= 0) {
                        return response()->json(['status' => 0, 'error' => 'تكلفة المهمة غير معروفة أو تساوي صفر. يرجى فصل المهمة يدوياً.']);
                    }

                    if ($wallet->balance < $cost) {
                        return response()->json(['status' => 0, 'error' => 'الرصيد غير كافٍ لإنشاء عملية تمويل تلقائية بقيمة ' . $cost]);
                    }

                    InvestorWalletTransaction::create([
                        'investor_wallet_id' => $wallet->id,
                        'task_id' => $task->id,
                        'transaction_type' => 'debit',
                        'amount' => $cost,
                        'description' => "دفع رسوم استثمار المهمة رقم #{$task->id} (تسوية آلية)",
                        'performed_by' => Auth::id(),
                    ]);
                    $message = "تم إنشاء عملية تمويل بمبلغ {$cost} للمهمة #{$taskId} بنجاح.";
                } else {
                    return response()->json(['status' => 0, 'error' => 'إجراء غير معروف.']);
                }

            } elseif ($anomalyType === 'transaction_without_task') {
                $transaction = InvestorWalletTransaction::where('investor_wallet_id', $wallet->id)
                    ->where('id', $transactionId)
                    ->firstOrFail();

                if ($fixAction === 'delete_transaction') {
                    $transaction->delete();
                    $message = "تم حذف عملية التمويل #{$transactionId} بنجاح.";
                } elseif ($fixAction === 'link_task') {
                    $task = \App\Models\Task::findOrFail($taskId);
                    $task->investor_id = $userId;
                    $task->save();
                    $message = "تم ربط المهمة #{$taskId} بهذا المضارب بنجاح.";
                } else {
                    return response()->json(['status' => 0, 'error' => 'إجراء غير معروف.']);
                }
            } else {
                return response()->json(['status' => 0, 'error' => 'نوع المشكلة غير معروف.']);
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'success' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'error' => $e->getMessage()]);
        }
    }
}
