<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Payment Receipt') }} #{{ $transaction->sequence }}</title>
    <style>
        body {
            font-family: 'tajawal';
            margin: 0;
            padding: 20px;
            color: #2c3e50;
            font-size: 13px;
            direction: rtl;
            text-align: right;
        }

        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .platform-info {
            font-size: 12px;
            line-height: 1.6;
            color: #555;
        }

        .logo img {
            height: 50px;
        }

        .receipt-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            padding-bottom: 15px;
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
        }

        .receipt-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .receipt-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }

        .receipt-left {
            display: table-cell;
            width: 50%;
            text-align: left;
            vertical-align: top;
        }

        .receipt-item {
            margin: 5px 0;
            font-size: 11px;
            color: #555;
        }

        .receipt-item strong {
            color: #2c3e50;
        }

        .formal-text {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            margin: 20px 0;
            font-size: 14px;
            line-height: 2.2;
            text-align: justify;
            direction: rtl;
        }

        .formal-text .highlight {
            font-weight: bold;
            color: #2c3e50;
            font-size: 15px;
        }

        .amount-box {
            background: #f8f9fa;
            border: 2px solid #2c3e50;
            color: #2c3e50;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }

        .amount-label {
            font-size: 14px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .amount-value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }

        .amount-words {
            font-size: 13px;
            margin-top: 5px;
            font-style: italic;
        }

        .section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2c3e50;
        }

        .detail-row {
            margin: 10px 0;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            font-size: 12px;
            display: inline-block;
            min-width: 120px;
        }

        .detail-value {
            color: #2c3e50;
            font-size: 13px;
            font-weight: 500;
        }

        .image-container {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .image-container img {
            max-width: 100%;
            max-height: 350px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .image-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .document-placeholder {
            padding: 20px;
            background: #fff3cd;
            border-radius: 6px;
            color: #856404;
            text-align: center;
        }

        footer {
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #e1e1e1;
            margin-top: 40px;
            padding-top: 15px;
        }

        .company-info {
            font-size: 10px;
            color: #666;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td>
                <div class="platform-info">
                    <div><strong>{{ __('شركة وجهات آمنة') }}</strong></div>
                    <div>{{__('السجل التجاري')}} : 5850148029</div>
                    <div>{{ __('+966556978782') }}</div>
                    <div style="margin-top: 5px; font-size: 10px;">{{ __('VAT Number') }}: 300000000000003</div>
                </div>
            </td>
            <td style="text-align: left">
                <div class="logo">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(url('assets/img/logo.png'))) }}"
                        alt="Logo" height="50">
                </div>
            </td>
        </tr>
    </table>

    {{-- Receipt Title --}}
    <div class="receipt-title">
        @if($transaction->transaction_type === 'credit')
        إيصال استلام / Payment Receipt
        @else
        سند صرف / Payment Voucher
        @endif
    </div>

    {{-- Receipt Info --}}
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <div class="receipt-item"><strong>رقم المحفظة:</strong> #{{ $wallet->id }}</div>
                <div class="receipt-item"><strong>رقم العملية:</strong> #{{ $transaction->sequence }}</div>
            </td>
            <td style="width: 50%; text-align: left; vertical-align: top;">
                <div class="receipt-item"><strong>التاريخ:</strong> {{ $transaction->created_at->format('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    {{-- Formal Arabic Text --}}
    <div class="formal-text">
        <div style="unicode-bidi: bidi-override; direction: rtl;">
            @if($transaction->transaction_type === 'credit')
            استلمت من
            @else
            تم الدفع لـ
            @endif
            @if($wallet->user_type === 'customer')
                <span class="highlight">{{ $wallet->customer->name }}</span>
            @else
                <span class="highlight">{{ $wallet->driver->name }}</span>
            @endif
            المحترم/ـة مبلغ وقدره
            <span class="highlight" style="direction: ltr; unicode-bidi: bidi-override; display: inline-block;">{{ number_format($transaction->amount, 2) }}</span>
            <span class="highlight">ريال سعودي</span>
            <span style="direction: rtl; unicode-bidi: normal;">({{ $amountInWords }})</span>
            @if($transaction->transaction_type === 'credit')
            موردة إلى محفظته رقم
            @else
            خصماً من محفظته رقم
            @endif
            <span class="highlight" style="direction: ltr; unicode-bidi: bidi-override; display: inline-block;">#{{ $wallet->id }}</span>
            مقيدة في العملية رقم
            <span class="highlight" style="direction: ltr; unicode-bidi: bidi-override; display: inline-block;">#{{ $transaction->sequence }}</span>
            بتاريخ
            <span class="highlight" style="direction: ltr; unicode-bidi: bidi-override; display: inline-block;">{{ $transaction->created_at->format('Y-m-d') }}</span>.
        </div>
    </div>

    {{-- Amount Display --}}
    <div class="amount-box">
        <div class="amount-label">
            @if($transaction->transaction_type === 'credit')
            المبلغ المستلم / Amount Received
            @else
            المبلغ المصروف / Amount Paid
            @endif
        </div>
        <div class="amount-value">{{ number_format($transaction->amount, 2) }} ريال</div>
        <div class="amount-words">{{ $amountInWords }}</div>
    </div>

    {{-- Transaction Details --}}
    @if($transaction->description || $transaction->task_id || $transaction->clearance_id)
    <div class="section">
        <div class="section-title">تفاصيل العملية / Transaction Details</div>

        @if($transaction->description)
        <div class="detail-row">
            <span class="detail-label">الوصف:</span>
            <span class="detail-value">{{ $transaction->description }}</span>
        </div>
        @endif

        @if($transaction->task_id)
        <div class="detail-row">
            <span class="detail-label">المهمة المرتبطة:</span>
            <span class="detail-value">#{{ $transaction->task_id }}</span>
        </div>
        @endif

        @if($transaction->clearance_id)
        <div class="detail-row">
            <span class="detail-label">التخليص الجمركي:</span>
            <span class="detail-value">#{{ $transaction->clearance_id }}</span>
        </div>
        @endif

        <div class="detail-row">
            <span class="detail-label">نوع المحفظة:</span>
            <span class="detail-value">{{ $wallet->user_type === 'customer' ? 'عميل / Customer' : 'سائق / Driver' }}</span>
        </div>

        <div class="detail-row">
            <span class="detail-label">صاحب المحفظة:</span>
            <span class="detail-value">
                @if($wallet->user_type === 'customer')
                    {{ $wallet->customer->name }}
                @else
                    {{ $wallet->driver->name }}
                @endif
            </span>
        </div>
    </div>
    @endif

    {{-- Attached Image --}}
    @if($transaction->image)
    <div class="section">
        <div class="section-title">المرفقات / Attachments</div>
        <div class="image-container">
            <div class="image-label">المستند المرفق / Attached Document</div>
            @php
                $candidates = [
                    public_path($transaction->image),
                    public_path('storage/' . $transaction->image),
                    public_path('uploads/' . $transaction->image),
                    storage_path('app/public/' . $transaction->image),
                    storage_path('app/' . $transaction->image),
                ];

                $imagePath = null;
                foreach ($candidates as $candidate) {
                    if (file_exists($candidate) && is_file($candidate)) {
                        $imagePath = $candidate;
                        break;
                    }
                }

                $imageExtension = $imagePath ? strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)) : '';
            @endphp

            @if($imagePath && in_array($imageExtension, ['jpg', 'jpeg', 'png', 'webp']))
                <img src="data:image/{{ $imageExtension }};base64,{{ base64_encode(file_get_contents($imagePath)) }}"
                     alt="Receipt Image" style="max-height: 400px; max-width: 100%;">
            @elseif($imagePath && $imageExtension === 'pdf')
                <div class="document-placeholder">
                    <strong>📄 مستند PDF مرفق</strong><br>
                    {{ basename($transaction->image) }}
                </div>
            @else
                <div class="document-placeholder">
                    <strong>📎 مستند مرفق (غير مدعوم للعرض أو غير موجود)</strong><br>
                    {{ basename($transaction->image) }}
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <footer>
        <div class="company-info">
            <strong>شركة وجهات آمنة</strong> | السجل التجاري: 5850148029 | الرقم الضريبي: 300000000000003<br>
            +966556978782 | info@safedest.com
        </div>
        <div style="margin-top: 10px;">
            {{ __('This is a computer-generated receipt.') }}<br>
            {{ __('Generated at') }}: {{ now()->format('Y-m-d H:i') }}
        </div>
    </footer>

</body>

</html>
