<?php

namespace App\Http\Controllers\investor;

use App\Http\Controllers\Controller;
use App\Models\InvestorWalletTransaction;
use App\Models\UserWalletTransaction;
use App\Models\Payments;
use App\Services\InvestorPaymentService;
use App\Services\HyperpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvestorWalletController extends Controller
{
    public function __construct(
        private InvestorPaymentService $paymentService,
        private HyperpayService $hyperpay
    ) {}

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

    /**
     * بدء عملية شحن المحفظة عبر HyperPay
     */
    public function initiateDeposit(Request $request)
    {
        Log::info('Initiate Deposit hit', $request->all());
        $request->validate([
            'amount' => 'required|numeric|min:10',
            'brand'  => 'required|in:VISA,MADA',
        ]);

        $user   = auth()->user();
        $amount = $request->amount;
        $brand  = $request->brand;

        try {
            $callbackUrl = route('investor.investment-wallet.deposit.callback');
            
            // Create Checkout Session
            $checkout = $this->hyperpay->createCheckout($amount, $brand, $callbackUrl, [
                'merchantTransactionId' => 'INV-' . time() . '-' . $user->id,
                'customer.email'        => $user->email,
                'customer.givenName'    => $user->name,
                'customer.surname'      => 'Investor',
            ]);

            if (!isset($checkout['id'])) {
                throw new \Exception('فشل الاتصال بـ HyperPay: ' . ($checkout['result']['description'] ?? 'خطأ غير معروف'));
            }

            // Create Payment Record
            Payments::create([
                'owner_type'            => 'investor',
                'owner_id'              => $user->id,
                'amount'                => $amount,
                'payment_method'        => strtolower($brand),
                'purpose'               => 'investor_deposit',
                'transaction_reference' => $checkout['id'],
                'status'                => 'pending',
                'gateway_name'          => 'hyperpay',
                'return_url'            => route('investor.investment-wallet'),
            ]);

            $scriptUrl = $this->hyperpay->getScriptUrl() . '?checkoutId=' . $checkout['id'];

            return view('investor.investment-wallet.payment', [
                'checkoutId' => $checkout['id'],
                'brand'      => $brand,
                'scriptUrl'  => $scriptUrl,
                'amount'     => $amount,
                'callback'   => $callbackUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Investor Deposit Initiation Failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'حدث خطأ أثناء بدء عملية الدفع: ' . $e->getMessage());
        }
    }

    /**
     * معالجة العودة من HyperPay بعد الدفع
     */
    public function handleDepositCallback(Request $request)
    {
        $checkoutId = $request->id;
        $payment    = Payments::where('transaction_reference', $checkoutId)->first();

        if (!$payment) {
            return redirect()->route('investor.investment-wallet')->with('error', 'بيانات العملية غير موجودة.');
        }

        try {
            $brand    = strtoupper($payment->payment_method);
            $result   = $this->hyperpay->getPaymentStatus($checkoutId, $brand);

            $code    = $result['result']['code'] ?? '';
            $status  = HyperpayService::codeToStatus($code);
            $message = $result['result']['description'] ?? 'No description';

            if ($status === 'paid') {
                // Success: Update Wallet
                $payment->update([
                    'status'           => 'paid',
                    'gateway_code'     => $code,
                    'gateway_msg'      => $message,
                    'completed_at'     => now(),
                    'gateway_response' => json_encode($result)
                ]);

                $investor = \App\Models\User::find($payment->owner_id);
                $this->paymentService->depositToInvestorWallet($investor, (float)$payment->amount, "شحن إلكتروني للمحفظة (#{$payment->id})");

                return redirect()->route('investor.investment-wallet')->with('success', 'تم شحن المحفظة بنجاح بمبلغ ' . number_format($payment->amount, 2) . ' ر.س');
            } else {
                // Failed
                $payment->update([
                    'status'           => 'failed',
                    'gateway_code'     => $code,
                    'gateway_msg'      => $message,
                    'gateway_response' => json_encode($result)
                ]);

                return redirect()->route('investor.investment-wallet')->with('error', 'فشلت عملية الدفع: ' . $message);
            }

        } catch (\Exception $e) {
            Log::error('Investor Deposit Callback Error', ['error' => $e->getMessage()]);
            return redirect()->route('investor.investment-wallet')->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع.');
        }
    }
}
