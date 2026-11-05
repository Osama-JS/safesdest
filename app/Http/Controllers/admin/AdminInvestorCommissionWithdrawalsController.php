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
            'total'      => InvestorCommissionWithdrawal::count(),
            'pending'    => InvestorCommissionWithdrawal::where('status', 'pending')->count(),
            'processing' => InvestorCommissionWithdrawal::where('status', 'processing')->count(),
            'approved'   => InvestorCommissionWithdrawal::where('status', 'approved')->count(),
            'rejected'   => InvestorCommissionWithdrawal::where('status', 'rejected')->count(),
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

        $isPayout = ($request->withdrawal_method === 'payout');

        $rules = [
            'withdrawal_method' => 'required|in:payout,bank_transfer,cash,check',
            'admin_notes'       => 'nullable|string|max:1000',
        ];

        if (!$isPayout) {
            $rules['receipt']          = 'required|file|mimes:jpeg,png,jpg,pdf|max:5120';
            $rules['reference_number'] = 'nullable|string|max:100';
        }

        $validator = Validator::make($request->all(), $rules, [
            'receipt.required'           => 'صورة / ملف إيصال التحويل مطلوب للموافقة اليدوية على السحب.',
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

        // ═════════════════════════════════════════════════════════════════════
        // مسار الصرف عبر HyperPay Payout (التحويل الآلي المباشر)
        // ═════════════════════════════════════════════════════════════════════
        if ($isPayout) {
            $rawIban = trim($withdrawal->iban_number ?: ($withdrawal->user?->iban_number ?: ''));
            if (empty($rawIban)) {
                return response()->json([
                    'status' => 0,
                    'error'  => 'لا يوجد رقم آيبان (IBAN) مسجل لهذا المستثمر لتنفيذ التحويل عبر Payout. يرجى التأكد من تسجيل الآيبان أولاً.'
                ], 422);
            }

            // فحص التحقق الرياضي للآيبان
            $ibanCheck = \App\Services\HyperPayPayoutService::validateIbanChecksum($rawIban, 'المستثمر');
            if (!$ibanCheck['valid']) {
                return response()->json([
                    'status' => 0,
                    'error'  => $ibanCheck['message']
                ], 422);
            }
            $cleanIban = $ibanCheck['clean'];

            // التحقق من عدم وجود طلب Payout نشط سابق لنفس حركة السحب
            $existingPayout = \App\Models\HyperpayPayout::where('source_commission_withdrawal_id', $withdrawal->id)
                ->whereIn('status', ['pending', 'processing', 'pending_approval'])
                ->first();
            if ($existingPayout) {
                return response()->json([
                    'status' => 0,
                    'error'  => 'يوجد طلب Payout نشط بالفعل مرتبط بهذا السحب (المرجع: ' . $existingPayout->reference_id . ').'
                ], 422);
            }

            // تحديد بيانات البنك واسم المستفيد
            $saudiBank = \App\Services\HyperPayPayoutService::getSaudiBankInfoFromIban($cleanIban);
            $bicCode = $saudiBank['bic'] ?: ($withdrawal->user?->bic_code ?: 'NCBKSARI');
            $bankName = $saudiBank['name'] ?: ($withdrawal->bank_name ?: ($withdrawal->user?->bank_name ?: 'البنك التجاري'));
            $beneficiaryName = \App\Services\HyperPayPayoutService::formatBeneficiaryName(
                $withdrawal->account_holder ?: ($withdrawal->user?->beneficiary_name ?: $withdrawal->user?->name)
            );

            $externalId = 'IPW-' . $withdrawal->id . '-' . time();

            try {
                DB::beginTransaction();

                // إنشاء سجل الـ Payout في hyperpay_payouts بحالة انتظار المصادقة
                $payout = \App\Models\HyperpayPayout::create([
                    'reference_id'                    => $externalId,
                    'user_id'                         => $withdrawal->user_id,
                    'user_wallet_id'                  => $wallet->id,
                    'amount'                          => $withdrawal->amount,
                    'payout_type'                     => 'IPW',
                    'source_commission_withdrawal_id' => $withdrawal->id,
                    'created_by'                      => Auth::id(),
                    'transaction_details'             => [
                        'amount'           => $withdrawal->amount,
                        'beneficiary_name' => $beneficiaryName,
                        'description'      => "صرف أرباح مستثمر طلب #{$withdrawal->id}",
                        'admin_id'         => Auth::id(),
                        'iban'             => $cleanIban,
                        'bic'              => $bicCode,
                        'bank_name'        => $bankName,
                        'address1'         => $withdrawal->user?->bank_address1 ?? ($withdrawal->user?->address ?: 'Riyadh'),
                        'address2'         => $withdrawal->user?->bank_address2 ?? '.',
                        'city'             => $withdrawal->user?->bank_city ?? 'Riyadh',
                        'country'          => 'SA',
                        'purpose'          => 'BA',
                        'source_withdrawal_id' => $withdrawal->id,
                        'admin_notes'      => $request->admin_notes,
                    ],
                    'status' => 'pending_approval'
                ]);

                // تحديث طلب السحب ليصبح قيد المعالجة بالـ Payout
                $withdrawal->update([
                    'status'       => 'processing',
                    'admin_notes'  => ($request->admin_notes ? $request->admin_notes . ' | ' : '') . "تم تحويل الطلب إلى HyperPay Payout (المرجع: {$externalId}) وبانتظار مصادقة المدير المالي.",
                    'processed_by' => Auth::id(),
                    'processed_at' => now(),
                ]);

                DB::commit();

                // إرسال إشعار للمدير المالي بوجود طلب مصادقة دفع جديد
                \App\Services\AdminNotificationDispatcher::dispatch(
                    eventKey: 'payout_approval_required',
                    title: "طلب مصادقة دفعة Payout جديدة للمستثمر #{$externalId}",
                    message: "تم تحويل طلب سحب العمولات #{$withdrawal->id} للمستثمر {$withdrawal->user?->name} بمبلغ " . number_format($withdrawal->amount, 2) . " ر.س إلى Payout، وبانتظار مصادقة المدير.",
                    actionUrl: url('/admin/investors/payout-requests'),
                    priority: 'high',
                    extraData: [
                        'reference_id'   => $externalId,
                        'user_id'        => $withdrawal->user_id,
                        'user_wallet_id' => $wallet->id,
                        'amount'         => $withdrawal->amount,
                        'payout_type'    => 'IPW',
                    ]
                );

                return response()->json([
                    'status'     => 1,
                    'is_payout'  => true,
                    'payout_url' => route('investors.payout-requests.index'),
                    'success'    => 'تم إنشاء طلب الـ Payout بنجاح برقم مرجع (' . $externalId . '). الطلب الآن في شاشة طلبات دفع المستثمرين بانتظار مصادقة المدير المالي لتنفيذ الحوالة البنكية.'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Commission Payout Creation Failed', ['error' => $e->getMessage()]);
                return response()->json([
                    'status' => 0,
                    'error'  => 'حدث خطأ أثناء إنشاء طلب الـ Payout: ' . $e->getMessage()
                ], 500);
            }
        }

        // ═════════════════════════════════════════════════════════════════════
        // مسار الصرف اليدوي التقليدي (رفع الإيصال وخصم المحفظة فوراً)
        // ═════════════════════════════════════════════════════════════════════

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
