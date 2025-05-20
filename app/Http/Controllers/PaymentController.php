<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\HyperpayService;
use App\Models\Wallet_Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Devinweb\LaravelHyperpay\Facades\LaravelHyperpay;

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

  public function initiatePayment(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'id' => 'nullable|exists:tasks,id',
      'payment_method' => 'required|in:credit,banking,wallet,cash',

    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    $task = Task::find($request->input('id'));

    DB::beginTransaction();
    try {
      if (in_array($request->payment_method, ['credit', 'cash'])) {
        if ($request->payment_method === 'credit') {
          $amount = $task->total_price;
          $payment_paid = 'all';
        } else {
          $payment_paid = 'just_commission';
          $amount = $task->commission;
        }
        $checkoutData = $this->hyperpay->createCheckout($amount);
        if ($checkoutData && isset($checkoutData['result']['code']) && $checkoutData['result']['code'] == '000.200.100') {

          if ($task->owner == 'customer') {
            $user = $task->customer;
          } else {
            $user = $task->user;
          }
          $transaction = $user->transactions()->create([
            'amount' => $amount,
            'status' => 'pending',
            'type' => 'delivery',
            'payment_type' => 'credit',
            'reference_id' => $task->id,
            'note' => 'دفع رسوم توصيل',
            'checkout_id' => $checkoutData['id']
          ]);

          $paymentUrl = "https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId=" . $checkoutData['id'];
          $url = route('payment.form', ['checkout_id' => $checkoutData['id']]);
          $task->update([
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'payment_paid' => $payment_paid,
            'payment_id' => $transaction->id,
          ]);
          DB::commit();
          return response()->json([
            'status' => 1,
            'url' => $url,
            'hyperpay' => true,
            'success' => __('You will now be redirected to the payment completion page'),
          ]);
        }
        return response()->json([
          'status' => 2,
          'error' => __('An error occurred while starting the payment process'),
        ]);
      } elseif ($request->payment_method === 'banking') {
        $amount = $task->total_price;

        if ($task->owner == 'customer') {
          $user = $task->customer;
        } else {
          $user = $task->user;
        }

        $result = null;
        if ($request->hasFile('receipt_image')) {
          $result = (new FunctionsController)->convert($request->receipt_image, 'tasks/payment');
        }
        $transaction = $user->transactions()->create([
          'amount' => $amount,
          'status' => 'pending',
          'type' => 'delivery',
          'payment_type' => 'banking',
          'reference_id' => $task->id,
          'note' => $request->note,
          'receipt_image' => $result,
          'receipt_number' => $request->receipt_number,
        ]);

        $task->update([
          'payment_method' => 'banking',
          'payment_status' => 'pending',
          'payment_paid' => 'all',
          'payment_id' => $transaction->id,
        ]);

        DB::commit();
        return response()->json([
          'status' => 1,
          'success' => __('ستم مراجعة التحويل البنكي ثم إبلاغك بالنتيجة عبر البريد الإلكتروني'),
        ]);
      } elseif ($request->payment_method === 'wallet') {
        if ($task->owner !== 'customer') {
          return response()->json([
            'status' => 2,
            'success' => __('You can not pay using this method! you can not hav a wallet'),
          ]);
        }
        $amount = $task->total_price;

        $wallet = $task->customer->wallet;
        $adjustedBalance = $wallet->balance;
        $adjustedBalance -= $amount;

        if ($adjustedBalance < -$wallet->debt_ceiling) {
          return response()->json([
            'status' => 2,
            'error'  => __('The amount exceeds the debt ceiling')
          ]);
        }



        $data = [
          'amount'              => $amount,
          'description'         => 'Pay the delivery fee for task #' . $task->id,
          'transaction_type'    => 'debit',
          'wallet_id'           => $wallet->id,
          'maturity_time'       => Carbon::now()->copy()->addDays(3),
          'task_id'             => $task->id,
        ];


        $done = Wallet_Transaction::create($data);

        if (!$done) {
          DB::rollBack();
          return response()->json([
            'status' => 2,
            'success' => __('Error: can not complete the payment'),
          ]);
        }


        // if ($done->status == false) {
        //   DB::rollBack();
        //   return response()->json([
        //     'status' => 2,
        //     'error' => __('The wallet is inactive, please wait for the admin to active it'),
        //   ]);
        // }


        $transaction = $task->customer->transactions()->create([
          'amount' => $amount,
          'status' => 'completed',
          'type' => 'delivery',
          'payment_type' => 'wallet',
          'reference_id' => $task->id,
          'receipt_number' => $done->sequence,
        ]);
        if (!$transaction) {
          DB::rollBack();
          return response()->json([
            'status' => 2,
            'success' => __('Error: can not complete the payment'),
          ]);
        }

        $task->update([
          'payment_method' => 'wallet',
          'payment_status' => 'completed',
          'payment_paid' => 'all',
          'payment_id' => $transaction->id,
        ]);

        DB::commit();
        return response()->json([
          'status' => 1,
          'success' => __('Tha Payment process was completed successfully through the wallet. thank you'),
        ]);
      }
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error' => __('طريقة الدفع غير مدعومة'),
      ]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error' => $ex->getMessage(),
      ]);
    }
  }

  public function handlePaymentCallback(Request $request)
  {
    $checkoutId = $request->query('id');

    $transaction = Transaction::firstWhere('checkout_id', $checkoutId);


    if (!$transaction) {
      return redirect()
        ->route('payment.failure')
        ->withErrors(['msg' => 'المعاملة غير موجودة.']);
    }

    // استعلام حالة الدفع
    $result = app(HyperpayService::class)->getPaymentStatus($checkoutId);
    // ❶ التحقق من وجود الكود
    $code        = data_get($result, 'result.code');
    $description = data_get($result, 'result.description', 'غير معرَّف');

    // سجِّل كل شىء للفحص لاحقاً
    Log::info('HyperPay-Callback', [
      'checkout_id' => $checkoutId,
      'code'        => $code,
      'description' => $description,
      'payload'     => $result,
    ]);

    /***********************************************************************
    | تصنيف أكواد HyperPay (طبقاً للوثائق الرسمية)                       |
    |---------------------------------------------------------------------|
    | - نجاح فوري          : 000.000.*  أو 000.100.1xx                    |
    | - نجاح مع مراجعة     : 000.400.0xx أو 000.400.100                   |
    | - مُعلَّق            : 000.200.*                                    |
    | - فشل / خطأ مصادقة   : 800.*  / 900.*  / أكواد أخرى                 |
     ***********************************************************************/
    $status = 'failed';            // القيمة الافتراضية
    $userMessage = 'فشلت عملية الدفع.';

    if (Str::startsWith($code, ['000.000', '000.100'])) {          // نجاح فوري
      $status      = 'paid';
      $userMessage = 'تم الدفع بنجاح.';
    } elseif (Str::startsWith($code, '000.400')) {                 // نجاح مع مراجعة
      $status      = 'review';
      $userMessage = 'تمت العملية، ولكن تحتاج إلى مراجعة يدوية.';
    } elseif (Str::startsWith($code, '000.200')) {                 // مُعلَّق
      $status      = 'pending';
      $userMessage = 'العملية قيد الانتظار… سيتم تحديثها لاحقاً.';
    } elseif (Str::startsWith($code, ['800', '900'])) {            // خطأ مصادقة أو فشل
      $status      = 'auth_error';
      $userMessage = 'فشل التحقق من الهوية لدى بوابة الدفع.';
    }

    // ❷ تحديث سجل المعاملة
    $transaction->update([
      'status'        => $status,
      'gateway_code'  => $code,
      'gateway_msg'   => $description,
      'processed_at'  => Carbon::now(),
    ]);

    /***************** إجراءات ما بعد كل حالة *****************/
    if ($status === 'paid') {                       // ✅ نجاح فوري
      // توليد الفاتورة وإرسالها
      $pdf = PDF::loadView('invoices.receipt', ['transaction' => $transaction])
        ->setOptions(['defaultFont' => 'Tajawal']);

      if ($transaction->payable && $transaction->payable->email) {
        Mail::to($transaction->payable->email)
          ->send(new \App\Mail\TransactionReceipt($transaction, $pdf));
      }

      return redirect()->route('payment.success');
    }

    if ($status === 'pending' || $status === 'review') {
      // صفحة انتظار أو مراجعة
      return redirect()
        ->route('payment.pending')
        ->with('msg', $userMessage);
    }

    /* جميع الحالات الأخرى تُعامل كفشل */
    return redirect()
      ->route('payment.failure')
      ->withErrors(['msg' => $userMessage . " ({$code})"]);
  }
}
