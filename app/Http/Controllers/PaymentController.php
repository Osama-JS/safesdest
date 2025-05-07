<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Devinweb\LaravelHyperpay\Facades\LaravelHyperpay;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Services\HyperpayService;


class PaymentController extends Controller
{
  protected $hyperpay;

  public function __construct(HyperpayService $hyperpay)
  {
    $this->hyperpay = $hyperpay;
  }

  public function index()
  {
    return view('payment.checkout');
  }

  // بدء عملية الدفع
  public function initiatePayment(Request $request)
  {
    // تأكد من البيانات المدخلة
    $amount = $request->input('amount');
    $checkoutData = $this->hyperpay->createCheckout($amount);

    // dd(auth()->user()->transactions);
    if ($checkoutData && isset($checkoutData['result']['code']) && $checkoutData['result']['code'] == '000.200.100') {
      // إنشاء سجل المعاملة في قاعدة البيانات
      $transaction = auth()->user()->transactions()->create([
        'amount' => $amount,
        'status' => 'pending',
        'type' => 'delivery',
        'reference_id' => $request->input('delivery_id'),
        'note' => 'دفع رسوم توصيل',
        'checkout_id' => $checkoutData['id']
      ]);

      $paymentUrl = "https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId=" . $checkoutData['id'];
      return redirect()->to(route('payment.form', ['checkout_id' => $checkoutData['id']]));
    }

    return redirect()->route('payment.failure')->with('error', 'حدث خطأ أثناء بدء عملية الدفع.');
  }

  public function handlePaymentCallback(Request $request)
  {
    $checkoutId = $request->query('id');

    $transaction = Transaction::where('checkout_id', $checkoutId)->first();

    if (!$transaction) {
      return redirect()->route('payment.failure')->withErrors(['msg' => 'المعاملة غير موجودة']);
    }

    $result = app(HyperpayService::class)->getPaymentStatus($checkoutId);

    if (!empty($result['result']['code']) && in_array($result['result']['code'], ['000.100.110', '000.100.111', '000.100.112'])) {
      $transaction->update(['status' => 'paid']);

      // إنشاء الفاتورة وإرسالها كما كنت تفعل سابقاً
      $pdf = Pdf::loadView('invoices.receipt', ['transaction' => $transaction]);
      $pdf->setOptions(['defaultFont' => 'Tajawal']);
      Mail::to($transaction->payable->email)->send(new \App\Mail\TransactionReceipt($transaction, $pdf));

      return redirect()->route('payment.success');
    } else {
      $transaction->update(['status' => 'failed']);
      return redirect()->route('payment.failure')->withErrors(['msg' => 'فشلت عملية الدفع.']);
    }
  }
}
