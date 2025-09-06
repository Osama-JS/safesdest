<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير المهام - {{ $customer->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: white;
            direction: rtl;
            font-size: 14px;
            line-height: 1.6;
            color: #000;
        }

        .report-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20mm;
            position: relative;
            box-sizing: border-box;
        }

        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #d40019;
            padding-bottom: 20px;
        }

        .company-name {
            font-size: 28px;
            font-weight: 800;
            color: #d40019;
            margin-bottom: 8px;
        }

        .company-name span {
            color: #6d6e71;
        }

        .report-title {
            font-size: 24px;
            font-weight: 700;
            color: #000;
            margin: 15px 0;
        }

        .report-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 12px;
            color: #666;
        }

        .customer-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .customer-info h3 {
            font-size: 16px;
            font-weight: 700;
            color: #d40019;
            margin-bottom: 10px;
            border-bottom: 1px solid #d40019;
            padding-bottom: 5px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: 600;
            min-width: 120px;
            color: #495057;
        }

        .info-value {
            color: #212529;
        }

        .statistics-section {
            margin-bottom: 25px;
        }

        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }


        .stat-number {
            font-size: 20px;
            font-weight: 700;
            color: #d40019;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #6c757d;
            font-weight: 500;
        }

        .tasks-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .tasks-table th {
            background-color: #828187;
            color: white;
            padding: 10px 8px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #828187;
        }

        .tasks-table td {
            padding: 8px;
            border: 1px solid #dee2e6;
            text-align: center;
            vertical-align: middle;
        }

        .tasks-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }

        .status-completed {
            background-color: #28a745;
        }

        .status-in_progress {
            background-color: #ffc107;
            color: #000;
        }

        .status-canceled {
            background-color: #dc3545;
        }

        .status-assign {
            background-color: #17a2b8;
        }

        .status-started {
            background-color: #6f42c1;
        }

        .payment-completed {
            background-color: #28a745;
        }

        .payment-pending {
            background-color: #ffc107;
            color: #000;
        }

        .payment-waiting {
            background-color: #6c757d;
        }

        .report-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }

        .filters-info {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .filters-title {
            font-weight: 700;
            color: #6d6e71;
            margin-bottom: 8px;
        }

        @media print {
            body {
                background-color: white;
                font-size: 12px;
                padding: 0;
                margin: 0;
            }

            .report-container {
                max-width: none;
                padding: 15mm;
                margin: 0;
                box-shadow: none;
                border: none;
            }

            .tasks-table {
                font-size: 10px;
            }

            .tasks-table th,
            .tasks-table td {
                padding: 6px 4px;
            }

            .statistics-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
            }

            .stat-card {
                padding: 10px;
            }

            .stat-number {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="report-container">
        <!-- Report Header -->
        <div class="report-header">
            <div class="company-name"> <span>Safedests |</span> وجهات آمنة</div>
            <div class="report-title">تقرير المهام للعملاء</div>
            <div class="report-info">
                <span>تاريخ التقرير: {{ $data['report_date'] }}</span>
                <span>الفترة: من {{ $data['from_date'] }} إلى {{ $data['to_date'] }}</span>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="customer-info">
            <h3>معلومات العميل</h3>
            <div class="info-row">
                <span class="info-label">اسم العميل:</span>
                <span class="info-value">{{ $customer->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">البريد الإلكتروني:</span>
                <span class="info-value">{{ $customer->email }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم الهاتف:</span>
                <span class="info-value">{{ $customer->phone ?? 'غير محدد' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ التسجيل:</span>
                <span class="info-value">{{ $customer->created_at->format('d/m/Y') }}</span>
            </div>
        </div>

        <!-- Applied Filters -->
        @if (array_filter($data['filters']))
            <div class="filters-info">
                <div class="filters-title">الفلاتر المطبقة:</div>
                <div style="line-height: 1.8;">
                    @if (!empty($data['filters']['status']) && is_array($data['filters']['status']))
                        <div style="margin-bottom: 5px;">
                            <strong>حالة المهمة:</strong>
                            @foreach ($data['filters']['status'] as $index => $status)
                                <span
                                    style="background: #e8f5e8; padding: 2px 6px; border-radius: 4px; margin: 0 2px; font-size: 11px;">
                                    {{ $data['status_labels'][$status] ?? $status }}
                                </span>
                                @if ($index < count($data['filters']['status']) - 1)
                                    ،
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($data['filters']['payment_status']) && is_array($data['filters']['payment_status']))
                        <div style="margin-bottom: 5px;">
                            <strong>حالة الدفع:</strong>
                            @foreach ($data['filters']['payment_status'] as $index => $paymentStatus)
                                <span
                                    style="background: #fff3cd; padding: 2px 6px; border-radius: 4px; margin: 0 2px; font-size: 11px;">
                                    {{ $data['payment_status_labels'][$paymentStatus] ?? $paymentStatus }}
                                </span>
                                @if ($index < count($data['filters']['payment_status']) - 1)
                                    ،
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($data['filters']['payment_method']) && is_array($data['filters']['payment_method']))
                        <div style="margin-bottom: 5px;">
                            <strong>طريقة الدفع:</strong>
                            @foreach ($data['filters']['payment_method'] as $index => $paymentMethod)
                                <span
                                    style="background: #d1ecf1; padding: 2px 6px; border-radius: 4px; margin: 0 2px; font-size: 11px;">
                                    {{ $data['payment_method_labels'][$paymentMethod] ?? $paymentMethod }}
                                </span>
                                @if ($index < count($data['filters']['payment_method']) - 1)
                                    ،
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if (empty($data['filters']['status']) &&
                            empty($data['filters']['payment_status']) &&
                            empty($data['filters']['payment_method']))
                        <span style="color: #6c757d; font-style: italic;">جميع المهام (بدون فلاتر)</span>
                    @endif
                </div>
            </div>
        @else
            <div class="filters-info">
                <div class="filters-title">الفلاتر المطبقة:</div>
                <span style="color: #6c757d; font-style: italic;">جميع المهام (بدون فلاتر)</span>
            </div>
        @endif

        <!-- Statistics -->
        <div class="statistics-section">
            <h3 style="font-size: 18px; font-weight: 700; color: #6d6e71; margin-bottom: 15px;">الإحصائيات العامة</h3>
            <div class="statistics-grid">
                <div class="stat-card">
                    <div class="stat-number">{{ $statistics['total_tasks'] }}</div>
                    <div class="stat-label">إجمالي المهام</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $statistics['completed_tasks'] }}</div>
                    <div class="stat-label">المهام المكتملة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $statistics['active_tasks'] }}</div>
                    <div class="stat-label">المهام النشطة</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $statistics['canceled_tasks'] }}</div>
                    <div class="stat-label">المهام الملغية</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($statistics['total_amount'], 2) }} ريال</div>
                    <div class="stat-label">إجمالي المبلغ</div>
                </div>
            </div>
        </div>

        <!-- Tasks Table -->
        <div class="tasks-section">
            <h3 style="font-size: 18px; font-weight: 700; color: #6d6e71; margin-bottom: 15px;">تفاصيل المهام</h3>
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>رقم المهمة</th>
                        <th>التاريخ</th>
                        <th>من</th>
                        <th>إلى</th>
                        <th>السائق</th>
                        <th>المبلغ</th>
                        <th>حالة المهمة</th>
                        <th>حالة الدفع</th>
                        <th>طريقة الدفع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>#{{ $task->id }}</td>
                            <td>{{ $task->created_at->format('d/m/Y') }}</td>
                            <td>{{ $task->pickup->address ?? 'غير محدد' }}</td>
                            <td>{{ $task->delivery->address ?? 'غير محدد' }}</td>
                            <td>{{ $task->driver->name ?? 'غير مُعين' }}</td>
                            <td>
                                @if ($task->status == 'canceled' || $task->status == 'refund')
                                    0
                                @else
                                    {{ $task->total_price }} ريال
                                @endif
                            </td>
                            <td>
                                <span class="status-badge ">
                                    {{ $data['status_labels'][$task->status] ?? $task->status }}
                                </span>
                            </td>
                            <td>
                                <span class="status-badge ">
                                    {{ $data['payment_status_labels'][$task->payment_status] ?? $task->payment_status }}
                                </span>
                            </td>
                            <td>{{ $data['payment_method_labels'][$task->payment_method] ?? $task->payment_method }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px; color: #6c757d;">
                                لا توجد مهام تطابق المعايير المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Report Footer -->
        <div class="report-footer">

            <p>تم إنشاء هذا التقرير تلقائياً في {{ $data['report_date'] }}</p>
        </div>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };

        // Close window after printing
        window.addEventListener('afterprint', function() {
            window.close();
        });
    </script>
</body>

</html>
