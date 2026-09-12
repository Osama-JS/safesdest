<?php

namespace App\Http\Controllers\admin;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Models\InvestorCommissionWithdrawal;
use App\Models\UserWalletTransaction;
use App\Services\InvestorNotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminInvestorCommissionWithdrawalsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_investor_withdrawals|view_investors', ['only' => ['index', 'show']]);
        $this->middleware('permission:approve_investor_withdrawals|manage_investor_withdrawals|save_investors', ['only' => ['approve']]);
        $this->middleware('permission:reject_investor_withdrawals|manage_investor_withdrawals|save_investors', ['only' => ['reject']]);
    }

    /**
     * عرض قائمة طلبات سحب العمولات في لوحة الإدارة
     */
    public function index(Request $request)
    {
        $query = InvestorCommissionWithdrawal::with(['user', 'wallet', 'transaction', 'processor']);

        // فلترة بالبحث (اسم المستثمر، البريد، الهاتف)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })->orWhere('id', $search);
        }

        // فلترة بالحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة بالتاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $withdrawals = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total'    => InvestorCommissionWithdrawal::count(),
            'pending'  => InvestorCommissionWithdrawal::where('status', 'pending')->count(),
            'approved' => InvestorCommissionWithdrawal::where('status', 'approved')->count(),
            'rejected' => InvestorCommissionWithdrawal::where('status', 'rejected')->count(),
            'total_approved_amount' => InvestorCommissionWithdrawal::where('status', 'approved')->sum('amount'),
        ];

        return view('admin.commissions.withdrawals', compact('withdrawals', 'stats'));
    }

    /**
     * الموافقة على طلب سحب العمولة ورفع الإيصال وتسجيل الحركة في المحفظة
     */
    public function approve(Request $request, $id)
    {
        $withdrawal = InvestorCommissionWithdrawal::with(['user', 'wallet'])->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'status' => 0,
                'error'  => __('هذا الطلب تمت معالجته مسبقاً.')
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'receipt'           => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'withdrawal_method' => 'required|in:bank_transfer,cash,check',
            'reference_number'  => 'nullable|string|max:100',
            'admin_notes'       => 'nullable|string|max:1000',
        ], [
            'receipt.required'           => 'صورة / ملف إيصال التحويل مطلوب للموافقة على السحب.',
            'receipt.mimes'              => 'يجب أن يكون الإيصال ملف صورة (jpeg, png, jpg) أو مستند PDF.',
            'receipt.max'                => 'الحد الأقصى لحجم ملف الإيصال هو 5 ميجابايت.',
            'withdrawal_method.required' => 'يرجى تحديد طريقة السحب / الصرف.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'error'  => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $wallet = $withdrawal->wallet;
        if (!$wallet) {
            return response()->json([
                'status' => 0,
                'error'  => __('محفظة العمولات للمستثمر غير موجودة.')
            ], 422);
        }

        // التحقق من الرصيد المتاح للسحب
        $withdrawable = (float) $wallet->withdrawable_balance;
        if ($withdrawal->amount > $withdrawable) {
            return response()->json([
                'status' => 0,
                'error'  => 'مبلغ السحب (' . number_format($withdrawal->amount, 2) . ' ر.س) يتجاوز الرصيد المتاح حالياً في محفظة العمولات (' . number_format($withdrawable, 2) . ' ر.س).'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 1. رفع صورة الإيصال
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = FileHelper::uploadFile($request->file('receipt'), 'user-wallets/withdrawals');
            }

            // 2. تجهيز وصف الحركة
            $methods = [
                'bank_transfer' => 'تحويل بنكي',
                'cash'          => 'نقداً',
                'check'         => 'شيك مصدق'
            ];
            $methodName = $methods[$request->withdrawal_method] ?? 'تحويل بنكي';
            $description = "سحب عمولات (طلب #{$withdrawal->id}) - {$methodName}";
            if ($request->filled('reference_number')) {
                $description .= " - رقم المرجع: {$request->reference_number}";
            }
            if ($request->filled('admin_notes')) {
                $description .= " - ملاحظة: {$request->admin_notes}";
            }

            // 3. تسجيل حركة خصم debit في محفظة العمولات
            $transaction = UserWalletTransaction::create([
                'user_wallet_id'   => $wallet->id,
                'amount'           => $withdrawal->amount,
                'description'      => $description,
                'transaction_type' => 'debit',
                'image'            => $receiptPath,
                'user_id'          => Auth::id(),
                'status'           => true,
                'maturity_time'    => now(),
            ]);

            // 4. تحديث طلب السحب
            $withdrawal->update([
                'status'                     => 'approved',
                'admin_notes'                => $request->admin_notes,
                'receipt_image'              => $receiptPath,
                'user_wallet_transaction_id' => $transaction->id,
                'processed_by'               => Auth::id(),
                'processed_at'               => now(),
            ]);

            DB::commit();

            // 5. إرسال إشعار للمستثمر عبر البريد الإلكتروني
            $newBalance = (float) $wallet->fresh()->withdrawable_balance;
            if ($withdrawal->user) {
                app(InvestorNotificationService::class)->notifyCommissionWithdrawalApproved(
                    $withdrawal->user,
                    $withdrawal,
                    $newBalance
                );
            }

            return response()->json([
                'status'  => 1,
                'success' => 'تمت الموافقة على طلب سحب العمولات بنجاح، وخصم المبلغ (' . number_format($withdrawal->amount, 2) . ' ر.س) من محفظة العمولات وتوثيق الإيصال وإشعار المستثمر.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Commission Withdrawal Approve Error: ' . $e->getMessage());
            return response()->json([
                'status' => 0,
                'error'  => 'حدث خطأ أثناء تنفيذ الموافقة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * رفض طلب سحب العمولة مع إلزام ذكر السبب
     */
    public function reject(Request $request, $id)
    {
        $withdrawal = InvestorCommissionWithdrawal::with('user')->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'status' => 0,
                'error'  => __('هذا الطلب تمت معالجته مسبقاً.')
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:3|max:1000',
            'admin_notes'      => 'nullable|string|max:1000',
        ], [
            'rejection_reason.required' => 'يرجى كتابة سبب رفض طلب سحب العمولات للمستثمر.',
            'rejection_reason.min'      => 'يجب أن لا يقل سبب الرفض عن 3 أحرف.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'error'  => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $withdrawal->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'admin_notes'      => $request->admin_notes,
                'processed_by'     => Auth::id(),
                'processed_at'     => now(),
            ]);

            // إرسال إشعار بريد إلكتروني للمستثمر
            if ($withdrawal->user) {
                app(InvestorNotificationService::class)->notifyCommissionWithdrawalRejected(
                    $withdrawal->user,
                    $withdrawal,
                    $request->rejection_reason
                );
            }

            return response()->json([
                'status'  => 1,
                'success' => 'تم رفض طلب سحب العمولات بنجاح وإرسال إشعار للمستثمر بسبب الرفض.'
            ]);

        } catch (Exception $e) {
            Log::error('Commission Withdrawal Reject Error: ' . $e->getMessage());
            return response()->json([
                'status' => 0,
                'error'  => 'حدث خطأ أثناء رفض الطلب: ' . $e->getMessage()
            ], 500);
        }
    }
}
