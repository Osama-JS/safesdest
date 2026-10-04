<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Brokers Commissions Report') }}</title>
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
            color: #333;
            background: white;
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
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 16px;
            color: #34495e;
            font-weight: bold;
        }

        .report-info {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 11px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: bold;
            color: #495057;
        }

        .info-value {
            color: #212529;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #dee2e6;
            padding: 7px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .data-table th {
            background: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .source-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            color: white;
        }

        .source-customer {
            background-color: #fd7e14;
        }

        .source-driver {
            background-color: #20c997;
        }

        .source-investor {
            background-color: #6f42c1;
        }

        .source-task {
            background-color: #0d6efd;
        }

        .summary {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }

        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #155724;
            margin-bottom: 12px;
            text-align: center;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
        }

        .summary-item {
            text-align: center;
            background: white;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
        }

        .summary-label {
            font-weight: bold;
            color: #155724;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #2c3e50;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .container {
                padding: 5px;
            }

            @page {
                size: A4 landscape;
                margin: 0.8cm;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            @if(view()->exists('_partials.macros'))
                <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 24])</span>
            @endif
            <div class="company-name">{{ __('SafeDests for Transport and Logistics') }}</div>
            <div class="report-title">
                @if(($reportData['mode'] ?? 'transactions') === 'aggregated')
                    {{ __('Brokers Commissions Report - Summary Aggregates') }}
                @else
                    {{ __('Brokers Commissions Report - Wallet Recorded Transactions') }}
                @endif
            </div>
        </div>

        <!-- Report Information -->
        <div class="report-info">
            <div class="info-row">
                <div>
                    <span class="info-label">{{ __('Brokers:') }} </span>
                    <span class="info-value">{{ $reportData['filters_applied']['brokers'] ?? __('All') }}</span>
                </div>
                <div>
                    <span class="info-label">{{ __('Time Period:') }} </span>
                    <span class="info-value">{{ $reportData['filters_applied']['date_range'] ?? __('All') }}</span>
                </div>
            </div>
            <div class="info-row">
                <div>
                    <span class="info-label">{{ __('Commission Source:') }} </span>
                    <span class="info-value">{{ $reportData['filters_applied']['source'] ?? __('All') }}</span>
                </div>
                <div>
                    <span class="info-label">{{ __('Report Type:') }} </span>
                    <span class="info-value">{{ $reportData['filters_applied']['mode'] ?? '-' }}</span>
                </div>
                <div>
                    <span class="info-label">{{ __('Creation Date:') }} </span>
                    <span class="info-value">{{ $reportData['generated_at'] ? $reportData['generated_at']->format('Y-m-d H:i:s') : date('Y-m-d H:i:s') }}</span>
                </div>
            </div>
        </div>

        <!-- Tables -->
        @if(($reportData['mode'] ?? 'transactions') === 'aggregated')
            <!-- Mode 2: Aggregated Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th>{{ __('Broker Name') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Customer Linking Commissions') }}</th>
                        <th>{{ __('Driver Linking Commissions') }}</th>
                        <th>{{ __('Direct Task Linking Commissions') }}</th>
                        <th>{{ __('Investor Linking Commissions') }}</th>
                        <th>{{ __('Total Commissions') }}</th>
                        <th>{{ __('Transactions Count') }}</th>
                        <th>{{ __('Current Wallet Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['brokers'] as $index => $b)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td style="font-weight: bold; text-align: right; padding-right: 10px;">{{ $b['broker_name'] }}</td>
                            <td>{{ $b['broker_phone'] }}</td>
                            <td style="color: #fd7e14; font-weight: bold;">{{ number_format($b['customer_commissions'], 2) }} {{ __('SAR') }}</td>
                            <td style="color: #20c997; font-weight: bold;">{{ number_format($b['driver_commissions'], 2) }} {{ __('SAR') }}</td>
                            <td style="color: #0d6efd; font-weight: bold;">{{ number_format($b['task_commissions'], 2) }} {{ __('SAR') }}</td>
                            <td style="color: #6f42c1; font-weight: bold;">{{ number_format($b['investor_commissions'] ?? 0, 2) }} {{ __('SAR') }}</td>
                            <td style="color: #28a745; font-weight: bold;">{{ number_format($b['total_commissions'], 2) }} {{ __('SAR') }}</td>
                            <td>{{ $b['transactions_count'] }}</td>
                            <td style="font-weight: bold;">{{ number_format($b['wallet_balance'], 2) }} {{ __('SAR') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 20px;">{{ __('No data available for selected brokers in specified period') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <!-- Mode 1: Detailed Transactions Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">{{ __('Tx #') }}</th>
                        <th style="width: 14%;">{{ __('Broker Name') }}</th>
                        <th style="width: 12%;">{{ __('Source Type') }}</th>
                        <th style="width: 16%;">{{ __('Source Entity / Name') }}</th>
                        <th style="width: 8%;">{{ __('Task #') }}</th>
                        <th style="width: 10%;">{{ __('Commission Amount') }}</th>
                        <th style="width: 20%;">{{ __('Description') }}</th>
                        <th style="width: 14%;">{{ __('Date & Time') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData['transactions'] as $tx)
                        <tr>
                            <td>#{{ $tx['sequence'] ?? $tx['id'] }}</td>
                            <td style="font-weight: bold; text-align: right; padding-right: 8px;">{{ $tx['broker_name'] }}</td>
                            <td>
                                @if($tx['source_type'] === 'customer')
                                    <span class="source-badge source-customer">{{ __('Linked with Customer') }}</span>
                                @elseif($tx['source_type'] === 'driver')
                                    <span class="source-badge source-driver">{{ __('Linked with Driver') }}</span>
                                @elseif($tx['source_type'] === 'investor')
                                    <span class="source-badge source-investor">{{ __('Linked with Investor') }}</span>
                                @else
                                    <span class="source-badge source-task">{{ __('Linked with Task') }}</span>
                                @endif
                            </td>
                            <td>{{ $tx['source_name'] }}</td>
                            <td>{{ !empty($tx['task_id']) && $tx['task_id'] !== '-' ? '#' . $tx['task_id'] : '-' }}</td>
                            <td style="color: #28a745; font-weight: bold;">{{ number_format($tx['amount'], 2) }} {{ __('SAR') }}</td>
                            <td style="font-size: 9.5px; text-align: right;">{{ $tx['description'] }}</td>
                            <td>{{ $tx['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">{{ __('No commission transactions recorded matching specified filter criteria') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        <!-- Summary Statistics -->
        @if(isset($reportData['summary']))
            <div class="summary">
                <div class="summary-title">{{ __('Brokers Report Statistics Summary') }}</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Grand Total Commissions') }}</div>
                        <div class="summary-value" style="color: #28a745;">
                            {{ number_format(($reportData['mode'] === 'aggregated' ? $reportData['summary']['grand_total_commissions'] : $reportData['summary']['total_commissions']), 2) }} {{ __('SAR') }}
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Customer Linking Commissions') }}</div>
                        <div class="summary-value" style="color: #fd7e14;">
                            {{ number_format(($reportData['mode'] === 'aggregated' ? $reportData['summary']['total_customer_commissions'] : $reportData['summary']['customer_commissions_total']), 2) }} {{ __('SAR') }}
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Driver Linking Commissions') }}</div>
                        <div class="summary-value" style="color: #20c997;">
                            {{ number_format(($reportData['mode'] === 'aggregated' ? $reportData['summary']['total_driver_commissions'] : $reportData['summary']['driver_commissions_total']), 2) }} {{ __('SAR') }}
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Direct Task Linking Commissions') }}</div>
                        <div class="summary-value" style="color: #0d6efd;">
                            {{ number_format(($reportData['mode'] === 'aggregated' ? $reportData['summary']['total_task_commissions'] : $reportData['summary']['task_commissions_total']), 2) }} {{ __('SAR') }}
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Investor Linking Commissions') }}</div>
                        <div class="summary-value" style="color: #6f42c1;">
                            {{ number_format(($reportData['mode'] === 'aggregated' ? ($reportData['summary']['total_investor_commissions'] ?? 0) : ($reportData['summary']['investor_commissions_total'] ?? 0)), 2) }} {{ __('SAR') }}
                        </div>
                    </div>
                </div>

                <div class="summary-grid" style="margin-top: 10px; grid-template-columns: repeat(2, 1fr);">
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Total Transactions Count') }}</div>
                        <div class="summary-value">
                            {{ $reportData['mode'] === 'aggregated' ? $reportData['summary']['total_transactions_count'] : $reportData['summary']['transactions_count'] }}
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Brokers Count in Report') }}</div>
                        <div class="summary-value">
                            {{ $reportData['mode'] === 'aggregated' ? $reportData['summary']['total_brokers'] : $reportData['summary']['brokers_count'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>{{ __('This report was generated automatically by SafeDests Logistics & Transportation System') }}</p>
            <p>{{ __('For inquiries and technical support, please contact the platform administration') }}</p>
        </div>
    </div>

    <!-- Print Script -->
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>

</html>
