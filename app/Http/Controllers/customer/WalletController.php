<?php

namespace App\Http\Controllers\customer;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\PdfService;

class WalletController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function downloadCreditReceipt($id)
    {
        $transaction = Wallet_Transaction::with(['wallet.customer'])->findOrFail($id);

        if ($transaction->wallet->customer_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($transaction->transaction_type, ['credit', 'debit'])) {
            return redirect()->back()->with('error', __('Receipt is available only for credit or debit transactions.'));
        }

        $amountInWords = $this->convertNumberToArabicWords($transaction->amount);

        $customerName = optional($transaction->wallet->customer)->name ?? 'Customer';
        $safeName = preg_replace('/[^A-Za-z0-9_\p{Arabic}]/u', '_', $customerName); // Support Arabic chars or just slug
        // Actually, for filename, better to use slug or simple chars.
        // Let's use Str::slug but it removes Arabic.
        // If I want to keep name recognizable, usually browsers handle UTF-8 filenames.
        $safeName = str_replace(' ', '_', $customerName);

        $file_name = "Receipt_{$transaction->wallet->id}_{$transaction->sequence}_{$safeName}";

        $pdfContent = $this->pdfService->generateRaw('admin.wallets.receipt_pdf', [
            'transaction' => $transaction,
            'wallet' => $transaction->wallet,
            'amountInWords' => $amountInWords
        ]);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$file_name}.pdf\"")
            ->header('Content-Length', strlen($pdfContent));
    }

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

        // Add decimal part (halalas)
        if ($decimalPart > 0) {
            $result .= ' و';
            if ($decimalPart >= 10 && $decimalPart < 20) {
                $result .= ' ' . $teens[$decimalPart - 10];
            } else {
                $tensDigit = floor($decimalPart / 10);
                $onesDigit = $decimalPart % 10;

                if ($tensDigit > 0) {
                    $result .= ' ' . $tens[$tensDigit];
                }
                if ($onesDigit > 0) {
                    $result .= ($tensDigit > 0 ? ' و' : ' ') . $ones[$onesDigit];
                }
            }
            $result .= ' هللة';
        }

        return $result;
    }
  public function index()
  {
    $data = Wallet::where('customer_id', Auth::user()->id)->first();



    return view('customers.wallet.index', compact('data'));
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'amount',
      3 => 'description',
      4 => 'maturity',
      5 => 'task',
      6 => 'user',
      7 => 'created_at',
    ];


    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');
    $search = $request->input('search');
    $type = $request->input('status');

    $wallet = Wallet::where('customer_id', Auth::user()->id)->first();



    if (!$wallet) {
      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'code'            => 200,
        'data'            => [],
      ]);
    }

    $totalData = Wallet_Transaction::where('wallet_id', $wallet->id)->count();
    $totalFiltered = $totalData;


    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';

    $query = Wallet_Transaction::query();
    $query->where('wallet_id', $wallet->id);

    if ($fromDate && $toDate) {
      $query->whereBetween('created_at', [
        Carbon::parse($fromDate)->startOfDay(),
        Carbon::parse($toDate)->endOfDay()
      ]);
    }





    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('description', 'LIKE', "%{$search}%")
          ->orWhere('amount', 'LIKE', "%{$search}%")
          ->orWhere('transaction_type', 'LIKE', "%{$search}%");
      });
    }

    if (!empty($type)) {
      $query->where('transaction_type', $type);
    }

    $totalFiltered = $query->count();

    $wallets = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();



    $data = [];
    $fakeId = $start;

    foreach ($wallets as $val) {
      $data[] = [
        'id'         => $val->id,
        'fake_id'    => ++$fakeId,
        'amount'     => $val->amount,
        'type'       => $val->transaction_type,
        'description'     => $val->description,
        'maturity'    => $val->maturity_time ?? '',
        'user'    => $val->user->name ?? 'automatic',
        'task'    => $val->task_id ?? '',
        'clearance'    => $val->clearance_id ?? '',
        'image'   => $val->image,
        'sequence'    => $val->sequence,
        'created_at' => $val->created_at->format('Y-m-d H:i'),
      ];
    }



    return response()->json([
      'draw'            => intval($request->input('draw')),
      'recordsTotal'    => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code'            => 200,
      'data'            => $data,
    ]);
  }
}
