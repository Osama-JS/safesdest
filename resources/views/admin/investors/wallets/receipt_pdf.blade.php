<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Speculator Receipt') }} #{{ $transaction->id }}</title>
    <style>
        body {
            font-family: 'tajawal', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #2c3e50;
            font-size: 11.5px;
            direction: rtl;
            text-align: right;
        }

        .header-table {
            width: 100%;
            margin-bottom: 8px;
        }

        .platform-info {
            font-size: 11px;
            line-height: 1.5;
            color: #555;
        }

        .logo img {
            height: 42px;
        }

        .receipt-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 8px 0;
            padding-bottom: 6px;
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
        }

        .receipt-info {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .receipt-item {
            margin: 3px 0;
            font-size: 11px;
            color: #555;
        }

        .receipt-item strong {
            color: #2c3e50;
        }

        .section,
        footer,
        .signature-row,
        .receipt-title,
        .amount-box,
        .formal-text {
            page-break-inside: avoid;
        }

        .signature-row {
            display: table;
            width: 100%;
        }

        .signature-label {
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 4px;
            color: #1a2733;
        }

        .signature-name {
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
        }

        .formal-text {
            background-color: #f8f9fa;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            margin: 8px 0;
            font-size: 11.5px;
            line-height: 1.8;
            text-align: justify;
            direction: rtl;
        }

        .formal-text .highlight {
            font-weight: bold;
            color: #2c3e50;
            font-size: 12px;
        }

        .amount-box {
            background: #f8f9fa;
            border: 1.5px solid #2c3e50;
            color: #2c3e50;
            padding: 8px 12px;
            border-radius: 6px;
            text-align: center;
            margin: 8px 0;
        }

        .amount-label {
            font-size: 11px;
            margin-bottom: 2px;
            font-weight: 600;
        }

        .amount-value {
            font-size: 20px;
            font-weight: bold;
            margin: 3px 0;
        }

        .amount-words {
            font-size: 10.5px;
            margin-top: 2px;
            font-style: italic;
        }

        .section {
            margin-bottom: 8px;
            padding: 8px 12px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #2c3e50;
        }

        .detail-row {
            margin: 4px 0;
            padding: 4px 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            font-size: 11px;
            display: inline-block;
            min-width: 100px;
        }

        .detail-value {
            color: #2c3e50;
            font-size: 11.5px;
            font-weight: 500;
        }

        .image-container {
            text-align: center;
            margin: 6px 0;
            padding: 6px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .image-container img {
            max-width: 100%;
            max-height: 100px;
            border-radius: 4px;
        }

        footer {
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #e1e1e1;
            margin-top: 10px;
            padding-top: 8px;
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
                    @endphp
                    @if(file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo" height="42" style="height: 42px; max-height: 42px; width: auto;">
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Receipt Title --}}
    <div class="receipt-title">
        @if($transaction->type === 'credit' || $transaction->type === 1)
        إيصال استلام محفظة المضاربة / Speculator Wallet Receipt
        @else
        سند صرف محفظة المضاربة / Speculator Wallet Payment Voucher
        @endif
    </div>

    {{-- Receipt Info --}}
    <table style="width: 100%; margin-bottom: 8px;">
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
        @if($transaction->type === 'credit' || $transaction->type === 1)
        استلمت من المضارب المحترم/ـة 
        @else
        تم الدفع للمضارب المحترم/ـة 
        @endif
        <span class="highlight">{{ $user->name }}</span>
        مبلغ وقدره 
        <span class="highlight">{{ number_format($transaction->amount, 2) }} ريال سعودي</span>
        <span>({{ $amountInWords }})</span>
        @if($transaction->type === 'credit' || $transaction->type === 1)
        موردة إلى محفظة المضاربة الخاصة به رقم 
        @else
        خصماً من محفظة المضاربة الخاصة به رقم 
        @endif
        <span class="highlight">#{{ $wallet->id }}</span>
        مقيدة في العملية رقم 
        <span class="highlight">#{{ $transaction->id }}</span>
        بتاريخ 
        <span class="highlight">{{ $transaction->created_at->format('Y-m-d') }}</span>.
    </div>

    {{-- Amount Display --}}
    <div class="amount-box">
        <div class="amount-label">
            @if($transaction->type === 'credit' || $transaction->type === 1)
            المبلغ المستلم / Amount Received
            @else
            المبلغ المصروف / Amount Paid
            @endif
        </div>
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
                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            @endphp

            @if($isImage && file_exists($attachmentPath))
                <img src="{{ $attachmentPath }}" alt="Attachment">
            @else
                <div style="padding: 20px; background: #eee;">
                    مستند مرفق: {{ basename($transaction->attachment) }}
                </div>
            @endif
        </div>
    </div>
    @endif

    @php
        $resolveSignaturePath = function ($userModel) {
            if (!$userModel || !$userModel->signature_image) {
                return null;
            }
            $cleanPath = preg_replace('/^storage\//', '', $userModel->signature_image);
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleanPath)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->path($cleanPath);
            }
            $possible = [
                storage_path('app/public/' . $cleanPath),
                public_path('storage/' . $cleanPath),
                public_path($userModel->signature_image),
                storage_path('app/' . $cleanPath),
            ];
            foreach ($possible as $p) {
                if (file_exists($p) && is_file($p)) {
                    return $p;
                }
            }
            return null;
        };

        $investorSignature = $resolveSignaturePath($user);

        $currentUser = $printerUser ?? auth()->user() ?? $performer;
        $printerSignature = $resolveSignaturePath($currentUser);
        if (!$printerSignature && $performer) {
            $printerSignature = $resolveSignaturePath($performer);
        }
        if (!$printerSignature) {
            $nawaf = \App\Models\User::where('name', 'like', '%نواف%')->whereNotNull('signature_image')->first();
            if ($nawaf) {
                $printerSignature = $resolveSignaturePath($nawaf);
            }
        }
    @endphp

    {{-- Footer --}}
    <footer>
        <table class="signature-row" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <tr>
                <td style="width: 50%; padding: 6px; vertical-align: top; text-align: right;">
                    <div class="signature-label">المضارب</div>
                    <div class="signature-name">{{ $user->name }}</div>
                    @if($investorSignature)
                        <div style="margin-top: 5px;">
                            <img src="{{ $investorSignature }}" style="max-height: 48px; max-width: 150px;" alt="توقيع المستثمر">
                        </div>
                    @endif
                </td>
                <td style="width: 50%; padding: 6px; vertical-align: top; text-align: left;">
                    <div class="signature-label">المدير التنفيذي</div>
                    <div class="signature-name">نواف محمد العمري</div>
                    @if($printerSignature)
                        <div style="margin-top: 5px;">
                            <img src="{{ $printerSignature }}" style="max-height: 48px; max-width: 150px;" alt="توقيع المستخدم">
                        </div>
                    @endif
                </td>
            </tr>
        </table>
        <div style="margin-top: 10px; font-size: 9px; color: #888;">
            هذا المستند تم توليده آلياً من نظام SafeDest بتاريخ {{ now()->format('Y-m-d H:i') }}
        </div>
    </footer>

</body>

</html>
