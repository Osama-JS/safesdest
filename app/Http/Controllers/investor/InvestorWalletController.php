<?php

namespace App\Http\Controllers\investor;

use App\Http\Controllers\Controller;
use App\Models\InvestorWalletTransaction;
use App\Models\UserWalletTransaction;
use App\Services\InvestorPaymentService;
use Illuminate\Http\Request;

class InvestorWalletController extends Controller
{
    public function __construct(private InvestorPaymentService $paymentService) {}

    /**
     * محفظة الاستثمار — عرض الرصيد والحركات
     */
    public function investmentWallet(Request $request)
    {
        $investor       = auth()->user();
        $investorWallet = $investor->investorWallet;

        $transactions = $investorWallet
            ? InvestorWalletTransaction::where('investor_wallet_id', $investorWallet->id)
                ->with('task')
                ->when($request->type, fn($q, $t) => $q->where('transaction_type', $t))
                ->when($request->from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($request->to,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                ->latest()->paginate(20)
            : InvestorWalletTransaction::where('id', 0)->paginate(20);

        return view('investor.investment-wallet.index', compact('investor', 'investorWallet', 'transactions'));
    }

    /**
     * المحفظة الشخصية (العمولات) — عرض الرصيد والحركات
     */
    public function personalWallet(Request $request)
    {
        $investor       = auth()->user();
        $personalWallet = $investor->userWallet;
        $contract       = $investor->activeInvestmentContract;

        $transactions = $personalWallet
            ? UserWalletTransaction::where('user_wallet_id', $personalWallet->id)
                ->with('task')
                ->when($request->from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($request->to,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                ->latest()->paginate(20)
            : UserWalletTransaction::where('id', 0)->paginate(20);

        return view('investor.personal-wallet.index', compact(
            'investor', 'personalWallet', 'transactions', 'contract'
        ));
    }

    /**
     * احتساب عمولات المستثمر العام — يُستدعى عند الضغط على زر "احتساب العمولات"
     */
    public function calculateGeneralCommissions(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $investor = auth()->user();

        // التحقق من كلمة المرور
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $investor->password)) {
            return back()->with('error', 'كلمة المرور غير صحيحة، يرجى المحاولة مرة أخرى.');
        }

        $contract = $investor->activeInvestmentContract;

        if (!$contract || $contract->contract_type !== 'general_investment') {
            return back()->with('error', 'هذه الميزة متاحة للمستثمر العام فقط.');
        }

        try {
            $result = $this->paymentService->calculateGeneralCommissions($investor, $contract);

            if ($result['count'] === 0) {
                return back()->with('info', 'لا توجد مهام جديدة لاحتساب عمولاتها.');
            }

            return back()->with('success',
                "تم احتساب عمولات {$result['count']} مهمة بإجمالي " .
                number_format($result['total_commission'], 2) . " ر.س"
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
