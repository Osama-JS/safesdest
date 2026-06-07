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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InvestorWalletExport;
use App\Exports\InvestorPersonalWalletExport;

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
                ->when($request->search, fn($q, $s) => $q->where('task_id', 'like', "%{$s}%"))
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
                ->when($request->type, fn($q, $t) => $q->where('transaction_type', $t))
                ->when($request->from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($request->to,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                ->when($request->search, fn($q, $s) => $q->where('task_id', 'like', "%{$s}%"))
                ->latest()->paginate(20)
            : UserWalletTransaction::where('id', 0)->paginate(20);

        return view('investor.personal-wallet.index', compact(
            'investor', 'personalWallet', 'transactions', 'contract'
        ));
    }

    /**
     * استخراج تقرير الإكسل لمحفظة الاستثمار
     */
    public function exportInvestmentWallet(Request $request)
    {
        $investor = auth()->user();
        $investorWallet = $investor->investorWallet;

        if (!$investorWallet) {
            return back()->with('error', __('Wallet not found'));
        }

        $transactions = InvestorWalletTransaction::where('investor_wallet_id', $investorWallet->id)
            ->with('task')
            ->when($request->type, fn($q, $t) => $q->where('transaction_type', $t))
            ->when($request->from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->search, fn($q, $s) => $q->where('task_id', 'like', "%{$s}%"))
            ->latest()
            ->get();

        return Excel::download(new InvestorWalletExport($investorWallet, $transactions), 'investment_wallet_transactions.xlsx');
    }

    /**
     * استخراج تقرير الإكسل للمحفظة الشخصية (العمولات)
     */
    public function exportPersonalWallet(Request $request)
    {
        $investor = auth()->user();
        $personalWallet = $investor->userWallet;

        if (!$personalWallet) {
            return back()->with('error', __('Wallet not found'));
        }

        $transactions = UserWalletTransaction::where('user_wallet_id', $personalWallet->id)
            ->with('task')
            ->when($request->type, fn($q, $t) => $q->where('transaction_type', $t))
            ->when($request->from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->search, fn($q, $s) => $q->where('task_id', 'like', "%{$s}%"))
            ->latest()
            ->get();

        return Excel::download(new InvestorPersonalWalletExport($personalWallet, $transactions), 'commission_wallet_transactions.xlsx');
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
            return back()->with('error', __('Password incorrect'));
        }

        $contract = $investor->activeInvestmentContract;

        if (!$contract || $contract->contract_type !== 'general_investment') {
            return back()->with('error', __('General commissions feature only'));
        }

        try {
            $result = $this->paymentService->calculateGeneralCommissions($investor, $contract);

            if ($result['count'] === 0) {
                return back()->with('info', __('No new tasks for commission'));
            }

            return back()->with('success', __('Commissions calculated success', [
                'count' => $result['count'],
                'amount' => number_format($result['total_commission'], 2),
            ]));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * إعادة استثمار الأرباح — تحويل من محفظة العمولات إلى محفظة المضاربة
     */
    public function reinvestProfits(Request $request)
    {
        $request->validate([
            'amount'   => 'required|numeric|min:0.01',
            'password' => 'required|string',
        ], [
            'amount.required' => __('Amount required'),
            'amount.min'      => __('Minimum amount 0.01 SAR'),
            'password.required' => __('Password required for confirmation'),
        ]);

        $investor = auth()->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->password, $investor->password)) {
            return back()->with('error', __('Password incorrect'));
        }

        try {
            $this->paymentService->reinvestProfits($investor, (float) $request->amount);

            return redirect()
                ->route('investor.investment-wallet')
                ->with('success', __('Reinvested success redirect', [
                    'amount' => number_format((float) $request->amount, 2),
                ]));
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
                throw new \Exception(__('HyperPay connection failed', [
                    'message' => $checkout['result']['description'] ?? __('Unknown error'),
                ]));
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
            return back()->with('error', __('Payment initiation error', ['message' => $e->getMessage()]));
        }
    }

    /**
     * معالجة العودة من HyperPay بعد الدفع
     */
    public function handleDepositCallback(Request $request)
    {
        Log::info('Investor Deposit Callback Query', $request->query());

        $checkoutId   = $request->query('id');
        $resourcePath = $request->query('resourcePath');
        $payment      = $checkoutId ? Payments::where('transaction_reference', $checkoutId)->first() : null;

        if (!$payment) {
            return redirect()->route('investor.investment-wallet')->with('error', __('Transaction data not found'));
        }

        try {
            $brand = strtoupper($payment->payment_method);
            $result = null;

            if ($resourcePath) {
                // Prefer the callback-provided resourcePath when available
                $result = $this->hyperpay->getPaymentStatusByResourcePath($resourcePath, $brand);
            }

            if (empty($result) && $checkoutId) {
                $result = $this->hyperpay->getPaymentStatus($checkoutId, $brand);
            }

            if (empty($result)) {
                throw new \Exception(__('Failed to get payment status from HyperPay'));
            }

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
                $this->paymentService->depositToInvestorWallet(
                    $investor,
                    (float) $payment->amount,
                    __('Electronic wallet top-up #:id', ['id' => $payment->id])
                );

                return redirect()->route('investor.investment-wallet')->with('success', __('Wallet topped up success', [
                    'amount' => number_format($payment->amount, 2),
                ]));
            }

            $payment->update([
                'status'           => $status,
                'gateway_code'     => $code,
                'gateway_msg'      => $message,
                'gateway_response' => json_encode($result)
            ]);

            if (in_array($status, ['pending', 'review'])) {
                return redirect()->route('investor.investment-wallet')->with('info', __('Partial payment awaiting bank confirmation'));
            }

            return redirect()->route('investor.investment-wallet')->with('error', __('Payment failed', ['message' => $message]));

        } catch (\Exception $e) {
            Log::error('Investor Deposit Callback Error', ['error' => $e->getMessage()]);
            return redirect()->route('investor.investment-wallet')->with('error', __('Payment processing error'));
        }
    }

    /**
     * حذف عمولة متكررة بالخطأ من محفظة العمولات
     */
    public function deleteCommissionTransaction(Request $request, $id)
    {
        $investor = auth()->user();
        $personalWallet = $investor->userWallet;

        if (!$personalWallet) {
            return back()->with('error', __('Wallet not found'));
        }

        $transaction = UserWalletTransaction::where('id', $id)
            ->where('user_wallet_id', $personalWallet->id)
            ->where('transaction_type', 'credit')
            ->first();

        if (!$transaction) {
            return back()->with('error', __('Transaction not found or not authorized.'));
        }

        $transaction->delete();

        return back()->with('success', __('Duplicate commission deleted successfully.'));
    }
}
