<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Invoice') }} #{{ $invoice_number }}</title>
    <style>
        body {
            font-family: 'tajawal';
            margin: 0;
            padding: 20px;
            color: #2c3e50;
            font-size: 12px;
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
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
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }

        .invoice-title h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .invoice-title .subtitle {
            font-size: 14px;
            margin-top: 5px;
            opacity: 0.9;
        }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-box {
            display: table-cell;
            width: 48%;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            vertical-align: top;
        }

        .info-box + .info-box {
            margin-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 4%;
        }

        .info-box-title {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #667eea;
        }

        .info-row {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 11px;
        }

        .info-value {
            color: #2c3e50;
            font-size: 12px;
        }

        .section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #ffffff;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 12px;
            padding-bottom: 6px;
            padding-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 10px;
            border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 4px solid #667eea;
        }

        .task-detail {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .task-detail-label {
            font-weight: 600;
            color: #667eea;
            font-size: 11px;
            display: block;
            margin-bottom: 4px;
        }

        .task-detail-value {
            color: #2c3e50;
            font-size: 12px;
        }

        .pricing-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .pricing-table th {
            background-color: #667eea;
            color: white;
            padding: 12px;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            font-weight: 600;
            font-size: 13px;
        }

        .pricing-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e1e8ed;
        }

        .pricing-table tr:last-child td {
            border-bottom: none;
        }

        .pricing-table .description {
            color: #2c3e50;
            font-size: 12px;
        }

        .pricing-table .amount {
            text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }};
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
        }

        .pricing-table .subtotal-row {
            background-color: #f8f9fa;
        }

        .pricing-table .total-row {
            background-color: #667eea;
            color: white;
            font-weight: bold;
            font-size: 15px;
        }

        .pricing-table .total-row td {
            padding: 15px 12px;
        }

        .payment-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            border-radius: 6px;
        }

        .payment-info.unpaid {
            background-color: #fff3e0;
            border-left-color: #ff9800;
        }

        .payment-status {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .payment-method {
            font-size: 12px;
            color: #666;
        }

        footer {
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #e1e1e1;
            margin-top: 40px;
            padding-top: 15px;
        }

        .tax-note {
            font-size: 10px;
            color: #666;
            font-style: italic;
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
            <td style="text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}">
                <div class="logo">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(url('assets/img/logo.png'))) }}"
                        alt="Logo" height="50">
                </div>
            </td>
        </tr>
    </table>

    {{-- Invoice Title --}}
    <div class="invoice-title">
        <h1>{{ __('Tax Invoice') }} / {{ __('فاتورة ضريبية') }}</h1>
        <div class="subtitle">{{ __('Invoice Number') }}: {{ $invoice_number }}</div>
        <div class="subtitle">{{ __('Task') }} #{{ $task->id }}</div>
    </div>

    {{-- Customer & Invoice Info --}}
    <div class="info-section">
        <div class="info-box">
            <div class="info-box-title">{{ __('Customer Information') }}</div>
            <div class="info-row">
                <span class="info-label">{{ __('Company Name') }}:</span>
                <span class="info-value">{{ $task->customer->company_name ?? $task->customer->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('Phone Number') }}:</span>
                <span class="info-value">{{ $task->customer->phone ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('Email') }}:</span>
                <span class="info-value">{{ $task->customer->email ?? '-' }}</span>
            </div>
        </div>
        <div class="info-box">
            <div class="info-box-title">{{ __('Invoice Information') }}</div>
            <div class="info-row">
                <span class="info-label">{{ __('Invoice Date') }}:</span>
                <span class="info-value">{{ $invoice_date->format('Y-m-d') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('Task Created') }}:</span>
                <span class="info-value">{{ $task->created_at->format('Y-m-d') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ __('Payment Status') }}:</span>
                <span class="info-value">
                    @if($task->payment_status === 'paid')
                        <strong style="color: #4caf50;">{{ __('Paid') }}</strong>
                    @else
                        <strong style="color: #ff9800;">{{ __('Unpaid') }}</strong>
                    @endif
                </span>
            </div>
            @if($task->payment_status === 'paid')
            <div class="info-row">
                <span class="info-label">{{ __('Payment Method') }}:</span>
                <span class="info-value">
                    {{ $task->payment_method === 'cash' ? __('Cash') : __('Electronic') }}
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- Task Details --}}
    <div class="section">
        <div class="section-title">{{ __('Task Details') }}</div>

        <div class="task-detail">
            <span class="task-detail-label">{{ __('Vehicle Information') }}</span>
            <div class="task-detail-value">
                {{ $task->vehicle_size?->type?->vehicle->name ?? '-' }}
                ({{ $task->vehicle_size?->type->name ?? '-' }})
                @if($task->vehicle_size?->name)
                    - {{ $task->vehicle_size->name }}
                @endif
            </div>
        </div>

        <div class="task-detail">
            <span class="task-detail-label">{{ __('Pickup Point') }}</span>
            <div class="task-detail-value">
                <strong>{{ __('Address') }}:</strong> {{ $task->pickup?->address ?? '-' }}<br>

            </div>
        </div>

        <div class="task-detail">
            <span class="task-detail-label">{{ __('Delivery Point') }}</span>
            <div class="task-detail-value">
                <strong>{{ __('Address') }}:</strong> {{ $task->delivery?->address ?? '-' }}<br>

            </div>
        </div>
    </div>

    {{-- Pricing Breakdown --}}
    <div class="section">
        <div class="section-title">{{ __('Pricing Breakdown') }}</div>

        <table class="pricing-table">
            <thead>
                <tr>
                    <th>{{ __('Description') }}</th>
                    <th style="text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}">{{ __('Amount') }} ({{ __('SAR') }})</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $pricingDetails = $task->pricing_details ?? [];
                    $basePrice = $pricingDetails['base_price'] ?? 0;
                    $distancePrice = $pricingDetails['distance_price'] ?? 0;
                    $commission = $task->commission ?? 0;
                    $vat = $pricingDetails['vat'] ?? 0;
                    $totalPrice = $task->total_price ?? 0;

                    // If pricing_details is empty, try to calculate from total_price
                    if (empty($pricingDetails) && $totalPrice > 0) {
                        // Assume 15% VAT
                        $priceBeforeVat = $totalPrice / 1.15;
                        $vat = $totalPrice - $priceBeforeVat;
                        $basePrice = $priceBeforeVat ;
                    }
                @endphp

                @if($basePrice > 0)
                <tr>
                    <td class="description">{{ __('Base Price') }}</td>
                    <td class="amount">{{ number_format($basePrice, 2) }}</td>
                </tr>
                @endif

                @if($distancePrice > 0)
                <tr>
                    <td class="description">{{ __('Distance Price') }}</td>
                    <td class="amount">{{ number_format($distancePrice, 2) }}</td>
                </tr>
                @endif



                <tr class="subtotal-row">
                    <td class="description"><strong>{{ __('Subtotal (Before VAT)') }}</strong></td>
                    <td class="amount"><strong>{{ number_format($totalPrice - $vat, 2) }}</strong></td>
                </tr>

                @if($vat > 0)
                <tr>
                    <td class="description">{{ __('VAT') }} (15%)</td>
                    <td class="amount">{{ number_format($vat, 2) }}</td>
                </tr>
                @endif

                <tr class="total-row">
                    <td>{{ __('TOTAL AMOUNT') }}</td>
                    <td style="text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}">
                        {{ number_format($totalPrice, 2) }} {{ __('SAR') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="tax-note">
            {{ __('* This invoice includes Value Added Tax (VAT) at 15% as per Saudi Arabian tax regulations.') }}
        </div>
    </div>

    {{-- Payment Information --}}
    <div class="payment-info {{ $task->payment_status === 'paid' ? '' : 'unpaid' }}">
        <div class="payment-status">
            {{ __('Payment Status') }}:
            @if($task->payment_status === 'paid')
                {{ __('PAID') }} ✓
            @else
                {{ __('PENDING PAYMENT') }}
            @endif
        </div>
        <div class="payment-method">
            {{ __('Payment Method') }}: {{ $task->payment_method === 'cash' ? __('Cash on Delivery') : __('Electronic Payment') }}
        </div>
        @if($task->payment_status === 'paid' && $task->payment_paid)
            <div style="font-size: 11px; margin-top: 5px; color: #666;">
                {{ __('Amount Paid') }}: {{ number_format($task->payment_paid, 2) }} {{ __('SAR') }}
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <footer>
        <div>{{ __('This is a computer-generated invoice and does not require a signature.') }}</div>
        <div style="margin-top: 5px;">
            {{ __('Generated at') }}: {{ now()->format('Y-m-d H:i') }}
        </div>
        <div style="margin-top: 10px; font-size: 9px;">
            {{ __('For any inquiries, please contact us at info@safedest.com or call +966556978782') }}
        </div>
    </footer>

</body>

</html>
