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
     * تحميل إيصال المعاملة بصيغة PDF
     */
    public function downloadReceipt($transactionId)
    {
        try {
            $transaction = InvestorWalletTransaction::with(['wallet.investor'])->findOrFail($transactionId);
            $wallet = $transaction->wallet;
            $user = $wallet->investor;

            // تحويل المبلغ إلى كلمات بالعربية
            $amountInWords = $this->convertNumberToArabicWords($transaction->amount);

            $fileName = "receipt_investor_{$user->id}_{$transaction->id}";

            return $this->pdfService->generate('admin.investors.wallets.receipt_pdf', [
                'transaction' => $transaction,
                'wallet' => $wallet,
                'user' => $user,
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
}
