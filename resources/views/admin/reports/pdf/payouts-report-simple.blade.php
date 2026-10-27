<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('تقرير المدفوعات عبر Payout') }} - SafeDest</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Segoe UI', Tahoma, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1e293b;
            background: #ffffff;
            direction: {{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }};
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 12px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .report-title {
            font-size: 15px;
            color: #475569;
            font-weight: bold;
        }

        .report-info {
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: bold;
            color: #475569;
        }

        .info-value {
            color: #0f172a;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }

        .summary-card.success {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        .summary-card.danger {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .summary-card.warning {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .summary-title {
            font-size: 10px;
            font-weight: bold;
            color: #64748b;
            margin-bottom: 4px;
        }

        .summary-card.success .summary-title {
            color: #047857;
        }

        .summary-card.danger .summary-title {
            color: #b91c1c;
        }

        .summary-card.warning .summary-title {
            color: #b45309;
        }

        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }

        .summary-sub {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }

        .table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #0f172a;
            white-space: nowrap;
        }

        .table td {
            padding: 6px 5px;
            text-align: center;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8.5px;
            font-weight: bold;
            border-radius: 4px;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-dark {
            background: #e2e8f0;
            color: #1e293b;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        .print-btn-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }

        .print-btn:hover {
            background: #1d4ed8;
        }

        @media print {
            .print-btn-container {
                display: none !important;
            }
            body {
                padding: 0;
            }
            .container {
                padding: 5px;
            }
            .table th {
                background-color: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .summary-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Print Button for manual clicking if auto-print blocked -->
        <div class="print-btn-container">
            <button class="print-btn" onclick="window.print()">
                <svg style="width:14px;height:14px;vertical-align:middle;margin-inline-end:4px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                {{ __('طباعة التقرير / حفظ كـ PDF') }}
            </button>
        </div>

        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ __('نظام سيف ديست للخدمات اللوجستية والنقل - SafeDest') }}</div>
            <div class="report-title">{{ __('تقرير المدفوعات والتحويلات عبر Payout (HyperPay)') }}</div>
        </div>

        <!-- Report Info -->
        <div class="report-info">
            <div class="info-row">
                <span class="info-label">{{ __('تاريخ وتوقيت التوليد:') }}</span>
                <span class="info-value">{{ now()->format('Y-m-d H:i:s') }}</span>
            </div>
            @if(!empty($reportData['filters']['date_from']) || !empty($reportData['filters']['date_to']))
            <div class="info-row">
                <span class="info-label">{{ __('الفترة المحددة:') }}</span>
                <span class="info-value">
                    {{ __('من: ') }} {{ $reportData['filters']['date_from'] ?? '—' }} | {{ __('إلى: ') }} {{ $reportData['filters']['date_to'] ?? '—' }}
                </span>
            </div>
            @endif
            @if(!empty($reportData['filters']['status']) && $reportData['filters']['status'] !== 'all')
            <div class="info-row">
                <span class="info-label">{{ __('الحالة المفلترة:') }}</span>
                <span class="info-value">{{ $reportData['filters']['status'] }}</span>
            </div>
            @endif
        </div>

        <!-- Summary KPIs -->
        @php
            $summary = $reportData['summary'] ?? [];
        @endphp
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-title">{{ __('إجمالي العمليات') }}</div>
                <div class="summary-value">{{ number_format($summary['total_amount'] ?? 0, 2) }} {{ __('SAR') }}</div>
                <div class="summary-sub">{{ $summary['total_count'] ?? 0 }} {{ __('عملية') }}</div>
            </div>
            <div class="summary-card success">
                <div class="summary-title">{{ __('العمليات الناجحة') }}</div>
                <div class="summary-value">{{ number_format($summary['completed_amount'] ?? 0, 2) }} {{ __('SAR') }}</div>
                <div class="summary-sub">{{ $summary['completed_count'] ?? 0 }} {{ __('عملية ناجحة') }}</div>
            </div>
            <div class="summary-card danger">
                <div class="summary-title">{{ __('العمليات الفاشلة والمرفوضة') }}</div>
                <div class="summary-value">{{ number_format(($summary['failed_amount'] ?? 0) + ($summary['rejected_amount'] ?? 0), 2) }} {{ __('SAR') }}</div>
                <div class="summary-sub">{{ ($summary['failed_count'] ?? 0) + ($summary['rejected_count'] ?? 0) }} {{ __('عملية') }}</div>
            </div>
            <div class="summary-card warning">
                <div class="summary-title">{{ __('قيد المعالجة / الانتظار') }}</div>
                <div class="summary-value">{{ number_format($summary['pending_amount'] ?? 0, 2) }} {{ __('SAR') }}</div>
                <div class="summary-sub">{{ $summary['pending_count'] ?? 0 }} {{ __('عملية معلقة') }}</div>
            </div>
        </div>

        <!-- Data Table -->
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 14%;">{{ __('مرجع Payout') }}</th>
                    <th style="width: 12%;">{{ __('قسم الـ Payout') }}</th>
                    <th style="width: 16%;">{{ __('المستفيد البنكي') }}</th>
                    <th style="width: 18%;">{{ __('الآيبان') }}</th>
                    <th style="width: 10%;">{{ __('البنك') }}</th>
                    <th style="width: 9%;">{{ __('المبلغ') }}</th>
                    <th style="width: 8%;">{{ __('الحالة') }}</th>
                    <th style="width: 9%;">{{ __('التاريخ') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['payouts'] ?? [] as $index => $item)
                    @php
                        $badgeClass = match($item['status']) {
                            'completed' => 'badge-success',
                            'failed' => 'badge-danger',
                            'rejected' => 'badge-dark',
                            'pending_approval' => 'badge-warning',
                            default => 'badge-info'
                        };
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong style="color:#0f172a;font-size:9.5px;">{{ $item['reference_id'] }}</strong>
                            @if(!empty($item['payout_id']) && $item['payout_id'] !== '—')
                                <div style="font-size:8px;color:#64748b;">{{ $item['payout_id'] }}</div>
                            @endif
                        </td>
                        <td>
                            <strong style="font-size:9px;color:#0f172a;">{{ $item['payout_section'] }}</strong>
                            <div style="font-size:8px;color:#64748b;">{{ $item['payout_type_label'] }}</div>
                        </td>
                        <td>
                            <strong>{{ $item['beneficiary_name'] }}</strong>
                            @if(!empty($item['entity_name']) && $item['entity_name'] !== $item['beneficiary_name'] && $item['entity_name'] !== '—')
                                <div style="font-size:8px;color:#64748b;">{{ $item['entity_name'] }}</div>
                            @endif
                        </td>
                        <td dir="ltr" style="font-family:monospace;font-size:9px;">{{ $item['iban'] }}</td>
                        <td>{{ $item['bank_name'] }}</td>
                        <td>
                            <strong style="color:#0f172a;">{{ number_format((float)$item['amount'], 2) }}</strong>
                            <span style="font-size:8px;color:#64748b;">{{ __('SAR') }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ $item['status_label'] }}</span>
                            @if(!empty($item['reason']))
                                <div style="font-size:7.5px;color:#dc2626;margin-top:2px;max-width:110px;word-break:break-word;">
                                    {{ \Illuminate\Support\Str::limit($item['reason'], 40) }}
                                </div>
                            @endif
                        </td>
                        <td style="font-size:8.5px;">{{ $item['created_at'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding: 20px; color: #64748b;">
                            {{ __('لا توجد سجلات مدفوعات مطابقة لمعايير البحث المحددة') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($reportData['payouts'] ?? []) > 0)
            <tfoot>
                <tr style="background:#f1f5f9;font-weight:bold;">
                    <td colspan="6" style="text-align: right; padding: 8px;">{{ __('الإجمالي الكلي:') }}</td>
                    <td style="text-align: center; color: #0f172a;">{{ number_format($summary['total_amount'] ?? 0, 2) }} {{ __('SAR') }}</td>
                    <td colspan="2" style="text-align: center;">{{ $summary['total_count'] ?? 0 }} {{ __('عملية') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>{{ __('تم إصدار هذا التقرير آلياً من نظام سيف ديست للنقل والخدمات اللوجستية') }} - SafeDest</p>
            <p>{{ __('لأي استفسارات أو دعم فني يرجى التواصل مع إدارة المنصة') }}</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 600);
        };
    </script>
</body>

</html>
