<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Customer Tasks Report - Simple') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 20px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 18px;
            color: #34495e;
            margin-bottom: 10px;
        }

        .report-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: bold;
            color: #495057;
        }

        .info-value {
            color: #6c757d;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #dee2e6;
            padding: 8px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .data-table th {
            background: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background: #e9ecef;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            color: white;
        }

        .status-completed {
            background: #28a745;
        }

        .status-in-progress {
            background: #007bff;
        }

        .status-canceled {
            background: #dc3545;
        }

        .status-pending {
            background: #ffc107;
            color: #212529;
        }

        .summary {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }

        .summary-title {
            font-size: 16px;
            font-weight: bold;
            color: #155724;
            margin-bottom: 10px;
            text-align: center;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-label {
            font-weight: bold;
            color: #155724;
            font-size: 11px;
        }

        .summary-value {
            font-size: 14px;
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

        .page-break {
            page-break-before: always;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .container {
                padding: 10px;
            }

            .data-table {
                font-size: 10px;
            }

            .data-table th,
            .data-table td {
                padding: 5px 3px;
            }
        }

        @page {
            size: A4 landscape;
            margin: 1cm;
        }

        .text-truncate {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .small-text {
            font-size: 10px;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20])</span>
            <div class="company-name">{{ __('SafeDests Transport and Logistics Company') }}</div>
            <div class="report-title">{{ __('Customer Tasks Report') }}</div>
        </div>

        <!-- Report Information -->
        <div class="report-info">
            <div class="info-row">
                <span class="info-label">العميل:</span>

                @foreach ($customerNames as $cust)
                    <span class="info-value">
                        {{ $cust->name }}
                    </span>
                @endforeach

            </div>
            <div class="info-row">
                <span class="info-label">الفترة الزمنية:</span>
                <span class="info-value">{{ $filters['date_from'] }} إلى {{ $filters['date_to'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ إنشاء التقرير:</span>
                <span class="info-value">{{ $reportData['generated_at']->format('Y-m-d H:i:s') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">أنشئ بواسطة:</span>
                <span class="info-value">{{ $reportData['generated_by'] }}</span>
            </div>
            @if (!empty($reportData['filters_applied']))
                <div class="info-row">
                    <span class="info-label">الفلاتر المطبقة:</span>
                    <span class="info-value">
                        @foreach ($reportData['filters_applied'] as $key => $value)
                            @if ($value)
                                {{ $key }}: {{ $value }}{{ !$loop->last ? ' | ' : '' }}
                            @endif
                        @endforeach
                    </span>
                </div>
            @endif
        </div>

        <!-- Data Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 6%">{{ __('Task ID') }}</th>
                    <th style="width: 10%">{{ __('Task Price') }}</th>
                    <th style="width: 20%">{{ __('Route') }}</th>
                    <th style="width: 15%">{{ __('Driver') }}</th>
                    <th style="width: 10%">{{ __('Task Status') }}</th>
                    <th style="width: 10%">{{ __('Payment Status') }}</th>
                    <th style="width: 10%">{{ __('Payment Method') }}</th>
                    <th style="width: 10%">{{ __('Created Date') }}</th>
                    <th style="width: 9%">{{ __('Closed Date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['tasks'] as $task)
                    <tr>
                        <td>{{ $task['id'] }}</td>
                        <td>
                            @if ($task['total_price'] == 0 && isset($task['original_price']) && $task['original_price'] > 0)
                                <span
                                    style="text-decoration: line-through; color: #6c757d;">{{ number_format($task['original_price'], 2) }}</span>
                                <br><strong style="color: #dc3545;">0.00</strong> {{ __('SAR') }}
                                <br><small style="color: #dc3545;">{{ __('Refunded/Cancelled') }}</small>
                            @else
                                {{ number_format($task['total_price'], 2) }} {{ __('SAR') }}
                            @endif
                        </td>
                        <td class="text-truncate">
                            <strong>{{ __('From') }}:</strong> {{ $task['pickup_address'] }}<br>
                            <strong>{{ __('To') }}:</strong> {{ $task['delivery_address'] }}
                        </td>
                        <td>
                            <strong>{{ $task['driver_name'] }}</strong><br>
                            <span class="small-text">{{ $task['driver_phone'] }}</span><br>
                            @if ($task['team_name'] !== 'غير محدد')
                                <span class="small-text">{{ __('Team') }}: {{ $task['team_name'] }}</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusClass = 'status-pending';
                                if (in_array($task['status'], ['completed'])) {
                                    $statusClass = 'status-completed';
                                } elseif (in_array($task['status'], ['in_progress', 'started', 'in the way'])) {
                                    $statusClass = 'status-in-progress';
                                } elseif (in_array($task['status'], ['canceled', 'refund'])) {
                                    $statusClass = 'status-canceled';
                                }
                            @endphp
                            <span class="status-badge {{ $statusClass }}">{{ $task['status_ar'] }}</span>
                        </td>
                        <td>
                            @php
                                $paymentStatusClass = 'status-pending';
                                if ($task['payment_status'] === 'completed') {
                                    $paymentStatusClass = 'status-completed';
                                } elseif ($task['payment_status'] === 'pending') {
                                    $paymentStatusClass = 'status-in-progress';
                                } elseif ($task['payment_status'] === 'waiting') {
                                    $paymentStatusClass = 'status-canceled';
                                }
                            @endphp
                            <span
                                class="status-badge {{ $paymentStatusClass }}">{{ $task['payment_status_ar'] }}</span>
                        </td>
                        <td>
                            @if ($task['payment_status'] === 'completed')
                                @if (empty($task['payment_method_ar']))
                                    <span style="color: #6c757d; font-style: italic;">{{ __('Not Completed') }}</span>
                                @else
                                    {{ $task['payment_method_ar'] }}
                                @endif
                            @endif

                        </td>
                        <td>{{ $task['created_at_formatted'] }}</td>
                        <td>{{ $task['closed_at_formatted'] ?: __('Not closed yet') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 20px; color: #6c757d;">
                            {{ __('No tasks match the specified criteria') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary -->
        @if (!empty($reportData['summary']))
            <div class="summary">
                <div class="summary-title">{{ __('Report Summary') }}</div>

                <!-- Basic Summary -->
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Total Tasks') }}</div>
                        <div class="summary-value">{{ $reportData['summary']['total_tasks'] }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Total Amount') }}</div>
                        <div class="summary-value">{{ number_format($reportData['summary']['total_amount'], 2) }}
                            {{ __('SAR') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Average Task Price') }}</div>
                        <div class="summary-value">{{ number_format($reportData['summary']['average_amount'], 2) }}
                            {{ __('SAR') }}</div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="summary-title" style="margin-top: 20px; font-size: 16px;">{{ __('Payment Summary') }}</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Paid Amount') }}</div>
                        <div class="summary-value" style="color: #28a745;">
                            {{ number_format($reportData['summary']['paid_amount'], 2) }} {{ __('SAR') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Pending Amount') }}</div>
                        <div class="summary-value" style="color: #ffc107;">
                            {{ number_format($reportData['summary']['partially_paid_amount'], 2) }}
                            {{ __('SAR') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Unpaid Amount') }}</div>
                        <div class="summary-value" style="color: #dc3545;">
                            {{ number_format($reportData['summary']['unpaid_amount'], 2) }} {{ __('SAR') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">{{ __('Remaining Amount') }}</div>
                        <div class="summary-value" style="color: #fd7e14; font-weight: bold;">
                            {{ number_format($reportData['summary']['remaining_amount'], 2) }} {{ __('SAR') }}
                        </div>
                    </div>
                </div>

                <!-- Payment Method Breakdown -->
                @if (!empty($reportData['summary']['payment_method_breakdown']))
                    <div class="summary-title" style="margin-top: 20px; font-size: 16px;">
                        {{ __('Payment Method Breakdown') }} ({{ __('Paid Only') }})</div>
                    <div class="summary-grid">
                        @foreach ($reportData['summary']['payment_method_breakdown'] as $method => $data)
                            <div class="summary-item">
                                <div class="summary-label">
                                    @switch($method)
                                        @case('cash')
                                            {{ __('Cash') }}
                                        @break

                                        @case('bank_transfer')
                                            {{ __('Bank Transfer') }}
                                        @break

                                        @case('credit_card')
                                            {{ __('Credit Card') }}
                                        @break

                                        @case('wallet')
                                            {{ __('Digital Wallet') }}
                                        @break

                                        @default
                                            {{ ucfirst($method) }}
                                    @endswitch
                                    ({{ $data['count'] }} {{ __('tasks') }})
                                </div>
                                <div class="summary-value">{{ number_format($data['total'], 2) }}
                                    {{ __('SAR') }}<br>
                                    @if ($method == 'wallet')
                                        @foreach ($customerNames as $cust)
                                            <span class="info-value">
                                                {{ $cust->name }} :
                                                {{ __('Wallet Balance: ') . number_format($cust->wallet->balance, 2) . __('SAR') }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>تم إنشاء هذا التقرير تلقائياً بواسطة نظام SafeDests للنقل والخدمات اللوجستية</p>
            <p>للاستفسارات والدعم الفني، يرجى التواصل مع فريق الدعم</p>
        </div>
    </div>

    <!-- Print Script -->
    <script>
        // Auto print when page loads
        window.onload = function() {
            // Small delay to ensure styles are loaded
            setTimeout(function() {
                window.print();
            }, 500);
        };

        // Close window after printing
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>

</html>
