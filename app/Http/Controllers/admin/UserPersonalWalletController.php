<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\HyperpayPayout;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserWalletTransaction;
use App\Exports\UserPersonalWalletExport;
use App\Services\HyperPayPayoutService;
use App\Services\AdminNotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class UserPersonalWalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:access_my_commission_wallet');
    }

    /**
     * عرض الصفحة الشخصية لمحفظة العمولات للمستخدم (الوسطاء والموظفين)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // المستثمرون لديهم لوحة تحكمهم الخاصة في /investor/personal-wallet
        if ($user->investor) {
            return redirect()->route('investor.personal-wallet');
        }

        // إيجاد أو إنشاء محفظة المستخدم
        $wallet = $user->userWallet;
        if (!$wallet) {
            $wallet = UserWallet::create([
                'user_id' => $user->id,
                'balance' => 0.00,
            ]);
        }

        // الأرصدة المالية
        $currentBalance = (float) $wallet->balance;
        $totalCredits   = (float) $wallet->credit;
        $totalDebits    = (float) $wallet->debit;

        // التحقق من وجود طلبات سحب Payout نشطة (قيد الانتظار أو المعالجة البنكية)
        $activePayoutsQuery = HyperpayPayout::where(function ($q) use ($user, $wallet) {
            $q->where('user_wallet_id', $wallet->id)
              ->orWhere('user_id', $user->id);
        })->whereIn('status', ['pending', 'processing', 'pending_approval']);

        $pendingAmount   = (float) (clone $activePayoutsQuery)->sum('amount');
        $hasActivePayout = (clone $activePayoutsQuery)->exists();
        $activePayout    = (clone $activePayoutsQuery)->latest()->first();

        // الرصيد الفعلي المتاح للسحب
        $withdrawableBalance = max(0.00, $currentBalance - $pendingAmount);

        // سجل طلبات السحب الخاصة بالمستخدم
        $payoutRequests = HyperpayPayout::where(function ($q) use ($user, $wallet) {
            $q->where('user_wallet_id', $wallet->id)
              ->orWhere('user_id', $user->id);
        })->latest()->paginate(10, ['*'], 'payouts_page');

        // جلب العمليات المالية مع الفلاتر
        $transactionsQuery = UserWalletTransaction::where('user_wallet_id', $wallet->id)
            ->with('task')
            ->when($request->filled('type'), fn($q) => $q->where('transaction_type', $request->type))
            ->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'),   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sq) use ($s) {
                    $sq->where('task_id', 'like', "%{$s}%")
                      ->orWhere('description', 'like', "%{$s}%");
                });
            })
            ->latest();

        $transactions = $transactionsQuery->paginate(20)->withQueryString();

        // حساب الرصيد التراكمي لكل عملية في الصفحة
        $allTrxForBalances = UserWalletTransaction::where('user_wallet_id', $wallet->id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'amount', 'transaction_type']);

        $runningBalances = [];
        $running = 0.0;
        foreach ($allTrxForBalances as $t) {
            $running += ($t->transaction_type === 'credit' ? (float) $t->amount : -(float) $t->amount);
            $runningBalances[$t->id] = $running;
        }

        // الاستجابة عبر الـ AJAX للتصفية والترقيم التفاعلي
        if ($request->ajax()) {
            if ($request->has('payouts_page')) {
                return response()->json([
                    'status' => 1,
                    'html'   => view('admin.my-wallet.partials.payouts_table', compact('payoutRequests', 'user'))->render(),
                    'total'  => $payoutRequests->total(),
                ]);
            }

            return response()->json([
                'status' => 1,
                'html'   => view('admin.my-wallet.partials.transactions_table', compact('transactions', 'runningBalances'))->render(),
                'total'  => $transactions->total(),
                'count'  => $transactions->count(),
                'from'   => $transactions->firstItem() ?? 0,
                'to'     => $transactions->lastItem() ?? 0,
            ]);
        }

        // فحص اكتمال البيانات البنكية في جدول المستخدم
        $cleanIban = str_replace(' ', '', $user->iban_number ?? '');
        $hasBankDetails = !empty($cleanIban) && !empty($user->beneficiary_name);

        return view('admin.my-wallet.index', compact(
            'user',
            'wallet',
            'currentBalance',
            'totalCredits',
            'totalDebits',
            'withdrawableBalance',
            'pendingAmount',
            'hasActivePayout',
            'activePayout',
            'payoutRequests',
            'transactions',
            'runningBalances',
            'hasBankDetails'
        ));
    }

    /**
     * تقديم طلب سحب عمولات جديد عبر HyperPay Payout
     */
    public function requestWithdrawal(Request $request)
    {
        $user = Auth::user();

        if ($user->investor) {
            return back()->with('error', 'المستثمرون يستخدمون لوحة تحكم المستثمر لتقديم طلبات السحب.');
        }

        $wallet = $user->userWallet;
        if (!$wallet) {
            return back()->with('error', 'تعذر العثور على محفظتك المالية.');
        }

        // 1. التحقق من عدم وجود طلب سحب نشط مسبقاً
        $existingActivePayout = HyperpayPayout::where(function ($q) use ($user, $wallet) {
            $q->where('user_wallet_id', $wallet->id)
              ->orWhere('user_id', $user->id);
        })->whereIn('status', ['pending', 'processing', 'pending_approval'])
          ->latest()
          ->first();

        if ($existingActivePayout) {
            return back()->with('error', 'لا يمكن تقديم طلب سحب جديد لوجود طلب سابق قيد الانتظار أو المعالجة البنكية (رقم المرجع: ' . $existingActivePayout->reference_id . ').');
        }

        // 2. التحقق من وجود واكتمال البيانات البنكية في جدول المستخدم
        $cleanIban = str_replace(' ', '', $user->iban_number ?? '');
        if (empty($cleanIban) || empty($user->beneficiary_name)) {
            return back()->with('error', 'بيانات الحساب البنكي غير مكتملة في ملفك الشخصي (اسم المستفيد الرسمي ورقم الآيبان مطلوبان). يرجى مراجعة الإدارة أو تحديث ملفك أولاً.');
        }

        // 3. التحقق من صحة الآيبان ومعيار Checksum
        $ibanCheck = HyperPayPayoutService::validateIbanChecksum($cleanIban, 'المستخدم');
        if (!$ibanCheck['valid']) {
            return back()->with('error', $ibanCheck['message']);
        }

        // 4. احتساب الرصيد المتاح للسحب
        $currentBalance = (float) $wallet->balance;
        if ($currentBalance <= 0) {
            return back()->with('error', 'رصيدك الحالي غير كافٍ لإجراء عملية السحب.');
        }

        // 5. التحقق من صحة المدخلات
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', "max:{$currentBalance}"],
            'notes'  => ['nullable', 'string', 'max:500'],
        ], [
            'amount.required' => 'يرجى إدخال المبلغ المراد سحبه.',
            'amount.numeric'  => 'يجب أن يكون المبلغ قيمة رقمية صحيحة.',
            'amount.min'      => 'الحد الأدنى لمبلغ السحب هو 1 ريال سعودي.',
            'amount.max'      => 'المبلغ المطلوب يتجاوز رصيدك المتاح للسحب حالياً (' . number_format($currentBalance, 2) . ' ر.س).',
        ]);

        try {
            // استنتاج كود السويفت/BIC واسم البنك إذا لم يكونا محددين بدقة
            $bicCode = $user->bic_code;
            $bankName = $user->bank_name;
            if (!$bicCode || !$bankName) {
                $saudiBankInfo = HyperPayPayoutService::getSaudiBankInfoFromIban($cleanIban);
                if (!$bicCode) {
                    $bicCode = $saudiBankInfo['bic'] ?? 'NCBKSAJE';
                }
                if (!$bankName) {
                    $bankName = $saudiBankInfo['name'] ?? 'البنك المعتمد';
                }
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
            $beneficiaryName = HyperPayPayoutService::formatBeneficiaryName($user->beneficiary_name ?: $user->name);

            $externalId = 'UWP-' . $wallet->id . '-' . time();

            // إنشاء سجل Payout بحالة pending_approval ليظهر في لوحة اعتماد الإدارة
            $payout = HyperpayPayout::create([
                'reference_id'   => $externalId,
                'user_id'        => $user->id,
                'user_wallet_id' => $wallet->id,
                'amount'         => $request->amount,
                'payout_type'    => 'UWP',
                'created_by'     => $user->id,
                'status'         => 'pending_approval',
                'transaction_details' => [
                    'amount'           => (float) $request->amount,
                    'beneficiary_name' => $beneficiaryName,
                    'description'      => $request->notes ?? 'طلب سحب عمولات مقدم من المستخدم',
                    'maturity'         => now()->format('Y-m-d H:i:s'),
                    'task_id'          => null,
                    'image'            => null,
                    'admin_id'         => null,
                    'iban'             => $cleanIban,
                    'bic'              => $bicCode,
                    'bank_name'        => $bankName,
                    'address1'         => $user->bank_address1 ?? $user->address ?? 'Riyadh',
                    'address2'         => $user->bank_address2 ?? '.',
                    'city'             => $user->bank_city ?? 'Riyadh',
                    'country'          => $countryCode,
                    'purpose'          => 'BA',
                    'requested_by_user'=> true,
                    'user_notes'       => $request->notes,
                ],
            ]);

            // إرسال إشعار للإدارة بمراجعة واعتماد طلب السحب المالي
            AdminNotificationDispatcher::dispatch(
                eventKey: 'payout_approval_required',
                title: "طلب سحب عمولات جديد عبر Payout من المستخدم {$user->name}",
                message: "قدم المستخدم {$user->name} طلب سحب عمولات بمبلغ " . number_format($request->amount, 2) . " ر.س وبانتظار مصادقة الإدارة للتحويل المالي.",
                actionUrl: url('/admin/users/payout-requests'),
                priority: 'high',
                extraData: [
                    'reference_id'   => $externalId,
                    'user_id'        => $user->id,
                    'user_name'      => $user->name,
                    'user_wallet_id' => $wallet->id,
                    'amount'         => $request->amount,
                    'payout_type'    => 'UWP',
                    'payout_id'      => $payout->id,
                ]
            );

            return back()->with('success', 'تم تقديم طلب سحب العمولات بمبلغ ' . number_format($request->amount, 2) . ' ر.س بنجاح. الطلب الآن بانتظار مصادقة الإدارة لتحويل المبلغ إلى حسابك البنكي المعتمد.');

        } catch (\Exception $e) {
            Log::error('User commission withdrawal request error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'amount'  => $request->amount,
            ]);

            return back()->with('error', 'حدث خطأ أثناء معالجة طلب السحب: ' . $e->getMessage());
        }
    }

    /**
     * تصدير كشف حساب محفظة العمولات كملف Excel
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();

        if ($user->investor) {
            return redirect()->route('investor.personal-wallet.export');
        }

        $wallet = $user->userWallet;
        if (!$wallet) {
            return back()->with('error', 'المحفظة غير موجودة.');
        }

        $transactions = UserWalletTransaction::where('user_wallet_id', $wallet->id)
            ->with('task')
            ->when($request->type, fn($q, $t) => $q->where('transaction_type', $t))
            ->when($request->from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($sq) use ($s) {
                    $sq->where('task_id', 'like', "%{$s}%")
                      ->orWhere('description', 'like', "%{$s}%");
                });
            })
            ->latest()
            ->get();

        $fileName = 'my_commission_wallet_' . date('Y-m-d') . '.xlsx';

        return Excel::download(new UserPersonalWalletExport($wallet, $transactions), $fileName);
    }
}
