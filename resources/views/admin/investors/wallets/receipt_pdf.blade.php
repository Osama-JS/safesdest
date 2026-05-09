<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Investor Receipt') }} #{{ $transaction->id }}</title>
    <style>
        body {
            font-family: 'tajawal', 'Arial', sans-serif;
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

        footer {
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #e1e1e1;
            margin-top: 40px;
            padding-top: 15px;
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
                </div>
            </td>
            <td style="text-align: left">
                <div class="logo">
                    @php
                        $logoPath = public_path('assets/img/logo.png');
                        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
                    @endphp
                    @if($logoData)
                        <img src="data:image/png;base64,{{ $logoData }}" alt="Logo">
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Receipt Title --}}
    <div class="receipt-title">
        إيصال استلام محفظة الاستثمار / Investor Wallet Receipt
    </div>

    {{-- Receipt Info --}}
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <div class="receipt-item"><strong>رقم المحفظة:</strong> #{{ $wallet->id }}</div>
                <div class="receipt-item"><strong>رقم العملية:</strong> #{{ $transaction->id }}</div>
            </td>
            <td style="width: 50%; text-align: left; vertical-align: top;">
                <div class="receipt-item"><strong>التاريخ:</strong> {{ $transaction->created_at->format('Y-m-d') }}</div>
            </td>
        </tr>
    </table>

    {{-- Formal Arabic Text --}}
    <div class="formal-text">
        استلمت من المستثمر المحترم/ـة 
        <span class="highlight">{{ $user->name }}</span>
        مبلغ وقدره 
        <span class="highlight">{{ number_format($transaction->amount, 2) }} ريال سعودي</span>
        <span>({{ $amountInWords }})</span>
        موردة إلى محفظة الاستثمار الخاصة به رقم 
        <span class="highlight">#{{ $wallet->id }}</span>
        مقيدة في العملية رقم 
        <span class="highlight">#{{ $transaction->id }}</span>
        بتاريخ 
        <span class="highlight">{{ $transaction->created_at->format('Y-m-d') }}</span>.
    </div>

    {{-- Amount Display --}}
    <div class="amount-box">
        <div class="amount-label">المبلغ المستلم / Amount Received</div>
        <div class="amount-value">{{ number_format($transaction->amount, 2) }} ريال</div>
        <div class="amount-words">{{ $amountInWords }}</div>
    </div>

    {{-- Transaction Details --}}
    <div class="section">
        <div class="section-title">تفاصيل العملية / Transaction Details</div>
        <div class="detail-row">
            <span class="detail-label">البيان:</span>
            <span class="detail-value">{{ $transaction->description }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">نوع العملية:</span>
            <span class="detail-value">{{ $transaction->transaction_type === 'credit' ? 'إيداع / شحن' : 'خصم / تمويل' }}</span>
        </div>
        @if($transaction->task_id)
        <div class="detail-row">
            <span class="detail-label">المهمة المرتبطة:</span>
            <span class="detail-value">#{{ $transaction->task_id }}</span>
        </div>
        @endif
    </div>

    {{-- Attachment --}}
    @if($transaction->attachment)
    <div class="section">
        <div class="section-title">المرفقات / Attachments</div>
        <div class="image-container">
            @php
                $attachmentPath = storage_path('app/public/' . $transaction->attachment);
                if (!file_exists($attachmentPath)) {
                    $attachmentPath = public_path('storage/' . $transaction->attachment);
                }
                
                $extension = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp']);
            @endphp

            @if($isImage && file_exists($attachmentPath))
                <img src="data:image/{{ $extension }};base64,{{ base64_encode(file_get_contents($attachmentPath)) }}" alt="Attachment">
            @else
                <div style="padding: 20px; background: #eee;">
                    مستند مرفق: {{ basename($transaction->attachment) }}
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <footer>
        <div style="margin-top: 20px; display: table; width: 100%;">
            <div style="display: table-cell; width: 50%; text-align: right;">
                <strong>توقيع المحاسب</strong><br><br>
                ___________________
            </div>
            <div style="display: table-cell; width: 50%; text-align: left;">
                <strong>توقيع المستثمر</strong><br><br>
                ___________________
            </div>
        </div>
        <div style="margin-top: 50px; font-size: 10px; color: #888;">
            هذا المستند تم توليده آلياً من نظام SafeDest بتاريخ {{ now()->format('Y-m-d H:i') }}
        </div>
    </footer>

</body>

</html>
