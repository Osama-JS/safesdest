<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Invoice') }} #{{ $invoice_number }}</title>
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

        .invoice-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            padding-bottom: 15px;
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
        }

        .invoice-info-table {
            width: 100%;
            margin-bottom: 30px;
        }

        .invoice-info-table td {
            vertical-align: top;
            padding: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #2c3e50;
            display: inline-block;
            min-width: 100px;
        }

        .info-value {
            color: #555;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .invoice-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: bold;
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        .invoice-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        .invoice-table td.text-start {
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: #fff;
        }

        .status-paid {
            background-color: #28a745;
        }

        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .status-unpaid {
            background-color: #dc3545;
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

    {{-- Header (Same as Receipt) --}}
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

    {{-- Title --}}
    <div class="invoice-title">
        فاتورة ضريبية / Tax Invoice
    </div>

    {{-- Invoice Info Sections --}}
    <table class="invoice-info-table">
        <tr>
            <td width="50%">
                <h4 style="margin: 0 0 10px 0; color: #2c3e50;">بيانات الفاتورة</h4>
                <div>
                    <span class="info-label">رقم الفاتورة:</span>
                    <span class="info-value">#{{ $invoice_number }}</span>
                </div>
                <div>
                    <span class="info-label">تاريخ الإصدار:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($invoice_date)->format('Y-m-d') }}</span>
                </div>
                <div>
                    <span class="info-label">طريقة الدفع:</span>
                   @switch($task->payment_method)
                       @case('cash')
                           <span class="info-value">نقدي</span>
                           @break
                       @case('credit')
                           <span class="info-value">بطاقة</span>
                           @break
                       @case('banking')
                           <span class="info-value">تحويل بنكي</span>
                           @break
                       @case('wallet')
                           <span class="info-value">محفظة</span>
                           @break
                       @default
                           <span class="info-value">غير معروف</span>
                   @endswitch
                </div>
                <div>
                    <span class="info-label">حالة الدفع:</span>
                    @if($task->payment_status == 'completed')
                        <span class="status-badge status-paid">مدفوعة</span>
                    @else
                        <span class="status-badge status-pending">غير مدفوعة</span>
                    @endif
                </div>

            </td>
            <td width="50%">
                <h4 style="margin: 0 0 10px 0; color: #2c3e50;">فوترة إلى</h4>
                <div>
                    <span class="info-label">العميل:</span>
                    <span class="info-value">{{ $task->customer->name ?? $task->user->name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="info-label">رقم الهاتف:</span>
                    <span class="info-value">{{ $task->phone_number ?? $task->customer->phone ?? 'N/A' }}</span>
                </div>
                @if($task->pickup)
                <div>
                    <span class="info-label">المصدر:</span>
                    <span class="info-value">{{ \Illuminate\Support\Str::limit($task->pickup->address ?? '', 40) }}</span>
                </div>
                @endif
                @if($task->delivery)
                <div>
                    <span class="info-label">الوجهة:</span>
                    <span class="info-value">{{ \Illuminate\Support\Str::limit($task->delivery->address ?? '', 40) }}</span>
                </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Invoice Table --}}
    <table class="invoice-table">
        <thead>
            <tr>
                <th width="40%">الوصف / Description</th>
                <th width="15%">الكمية / Qty</th>
                <th width="20%">السعر / Price</th>
                <th width="25%">الإجمالي / Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-start">
                    <strong>خدمة توصيل / Delivery Service</strong><br>
                    <span style="font-size: 11px; color: #666;">
                        مهمة رقم #{{ $task->id }}<br>
                        نوع المركبة: {{ $task->vehicle_size->name ?? 'N/A' }}
                    </span>
                </td>
                <td>1</td>
                <td>{{ number_format($task->total_price, 2) }} ريال</td>
                <td>{{ number_format($task->total_price, 2) }} ريال</td>
            </tr>

            <tr class="total-row" style="background-color: #e8f5e9;">
                <td colspan="3" class="text-start" style="font-size: 14px;">الإجمالي الكلي / Grand Total</td>
                <td style="font-size: 14px; color: #28a745;">{{ number_format($task->total_price, 2) }} ريال</td>
            </tr>
        </tbody>
    </table>

    {{-- Footer (Same as Receipt) --}}
    <footer>
        <div class="company-info">
            <strong>شركة وجهات آمنة</strong> | السجل التجاري: 5850148029 | الرقم الضريبي: 300000000000003<br>
            +966556978782 | info@safedest.com
        </div>
        <div style="margin-top: 10px;">
            {{ __('This is a computer-generated invoice.') }}<br>
            {{ __('Generated at') }}: {{ now()->format('Y-m-d H:i') }}
        </div>
    </footer>

</body>

</html>
