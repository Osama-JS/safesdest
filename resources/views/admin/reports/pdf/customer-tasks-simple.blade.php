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
            <div class="report-title">{{ __('Customer Tasks Report - Simple') }}</div>
        </div>

        <!-- Report Information -->
        <div class="report-info">
            <div class="info-row">
                <span class="info-label">العميل:</span>
                <span class="info-value">{{ implode(', ', $customerNames) }}</span>
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
                    <th style="width: 8%">رقم المهمة</th>
                    <th style="width: 12%">سعر المهمة</th>
                    <th style="width: 25%">المسار</th>
                    <th style="width: 20%">السائق</th>
                    <th style="width: 12%">حالة المهمة</th>
                    <th style="width: 12%">تاريخ الإنشاء</th>
                    <th style="width: 11%">تاريخ الإغلاق</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['tasks'] as $task)
                    <tr>
                        <td>{{ $task['id'] }}</td>
                        <td>{{ number_format($task['total_price'], 2) }} ريال</td>
                        <td class="text-truncate">
                            <strong>من:</strong> {{ $task['pickup_address'] }}<br>
                            <strong>إلى:</strong> {{ $task['delivery_address'] }}
                        </td>
                        <td>
                            <strong>{{ $task['driver_name'] }}</strong><br>
                            <span class="small-text">{{ $task['driver_phone'] }}</span><br>
                            @if ($task['team_name'] !== 'غير محدد')
                                <span class="small-text">الفريق: {{ $task['team_name'] }}</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusClass = 'status-pending';
                                if (in_array($task['status'], ['completed'])) {
                                    $statusClass = 'status-completed';
                                } elseif (in_array($task['status'], ['in_progress', 'started', 'in the way'])) {
                                    $statusClass = 'status-in-progress';
                                } elseif (in_array($task['status'], ['canceled'])) {
                                    $statusClass = 'status-canceled';
                                }
                            @endphp
                            <span class="status-badge {{ $statusClass }}">{{ $task['status_ar'] }}</span>
                        </td>
                        <td>{{ $task['created_at_formatted'] }}</td>
                        <td>{{ $task['closed_at_formatted'] ?: 'لم تُغلق بعد' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px; color: #6c757d;">
                            لا توجد مهام تطابق المعايير المحددة
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary -->
        @if (!empty($reportData['summary']))
            <div class="summary">
                <div class="summary-title">ملخص التقرير</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">إجمالي عدد المهام</div>
                        <div class="summary-value">{{ $reportData['summary']['total_tasks'] }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">إجمالي المبلغ</div>
                        <div class="summary-value">{{ number_format($reportData['summary']['total_amount'], 2) }} ريال
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">متوسط سعر المهمة</div>
                        <div class="summary-value">{{ number_format($reportData['summary']['average_amount'], 2) }}
                            ريال</div>
                    </div>
                </div>
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
