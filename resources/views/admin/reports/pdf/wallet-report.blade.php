<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Wallet Report') }} - {{ $wallet_data['owner']->name }}</title>
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

        .status-confirmed {
            background: #28a745;
        }

        .status-pending {
            background: #ffc107;
            color: #212529;
        }

        .type-credit {
            background: #28a745;
        }

        .type-debit {
            background: #dc3545;
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
            grid-template-columns: repeat(4, 1fr);
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
            size: A4 portrait;
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

        .amount-positive {
            color: #28a745;
            font-weight: bold;
        }

        .amount-negative {
            color: #dc3545;
            font-weight: bold;
        }

        .amount-balance {
            color: #2c3e50;
            font-weight: bold;
            background: #f8f9fa;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20])</span>
            <div class="company-name">{{ __('SafeDests Transport and Logistics Company') }}</div>
            <div class="report-title">{{ __('ًWallet Report') }}</div>
        </div>


        <!-- Report Information -->
        <div class="report-info">
            <div class="info-row">
                <span class="info-label">تاريخ التقرير:</span>
                <span class="info-value">{{ $report_date }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الفترة الزمنية:</span>
                <span class="info-value">من {{ $from_date }} إلى {{ $to_date }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">نوع المحفظة:</span>
                <span class="info-value">
                    @if ($wallet_data['type'] == 'customer')
                        محفظة عميل
                    @elseif($wallet_data['type'] == 'driver')
                        محفظة سائق
                    @else
                        محفظة فريق
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">اسم المالك:</span>
                <span class="info-value">{{ $wallet_data['owner']->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم المحفظة:</span>
                <span class="info-value">#{{ $wallet_data['wallet']->id }}</span>
            </div>
        </div>

        <!-- Transactions Table -->
        @if (isset($transactions) && (is_array($transactions) || is_object($transactions)) && count($transactions) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>رقم المعاملة</th>
                        <th>الرصيد بعد المعاملة</th>
                        <th>المبلغ (ريال)</th>
                        <th>النوع</th>
                        <th>الوصف</th>
                        <th>التاريخ</th>
                        <th>رقم المهمة</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $runningBalance = $summary['current_balance'] ?? 0;
                        $transactionsArray = is_array($transactions) ? $transactions : $transactions->toArray();

                        // حساب الرصيد لكل معاملة بترتيب عكسي
                        $transactionsWithBalance = [];
                        foreach (array_reverse($transactionsArray) as $transaction) {
                            // التعامل مع المعاملة سواء كانت object أو array
                            $transactionAmount = is_object($transaction)
                                ? $transaction->amount ?? 0
                                : $transaction['amount'] ?? 0;
                            $transactionType = is_object($transaction)
                                ? $transaction->transaction_type ?? ($transaction->type ?? 'debit')
                                : $transaction['transaction_type'] ?? ($transaction['type'] ?? 'debit');

                            // حساب الرصيد قبل هذه المعاملة
                            if ($transactionType == 'credit') {
                                $balanceAfter = $runningBalance;
                                $runningBalance -= $transactionAmount;
                            } else {
                                $balanceAfter = $runningBalance;
                                $runningBalance += $transactionAmount;
                            }

                            $transactionsWithBalance[] = [
                                'transaction' => $transaction,
                                'balance_after' => $balanceAfter,
                            ];
                        }

                        // إعادة ترتيب المعاملات للعرض (الأحدث أولاً)
                        $transactionsWithBalance = array_reverse($transactionsWithBalance);
                    @endphp

                    @foreach ($transactionsWithBalance as $item)
                        @php
                            $transaction = $item['transaction'];
                            $balanceAfter = $item['balance_after'];

                            // التعامل مع المعاملة سواء كانت object أو array
                            if (is_object($transaction)) {
                                $transactionType = $transaction->transaction_type ?? ($transaction->type ?? 'debit');
                                $transactionId = $transaction->id ?? 0;
                                $transactionAmount = $transaction->amount ?? 0;
                                $transactionDate = $transaction->created_at ?? now();
                                $transactionDescription = $transaction->description ?? 'غير محدد';
                                $transactionTaskId = $transaction->task_id ?? null;
                            } else {
                                // إذا كانت array
                                $transactionType =
                                    $transaction['transaction_type'] ?? ($transaction['type'] ?? 'debit');
                                $transactionId = $transaction['id'] ?? 0;
                                $transactionAmount = $transaction['amount'] ?? 0;
                                $transactionDate = $transaction['created_at'] ?? now();
                                $transactionDescription = $transaction['description'] ?? 'غير محدد';
                                $transactionTaskId = $transaction['task_id'] ?? null;
                            }

                            // التأكد من أن ID صحيح
                            if (empty($transactionId) || $transactionId == 0) {
                                $transactionId = 'N/A';
                            }
                        @endphp
                        <tr>
                            <td><strong>#{{ $transactionId }}</strong></td>
                            <td class="amount-balance">
                                <strong>{{ number_format($balanceAfter, 2) }} ريال</strong>
                            </td>
                            <td class="{{ $transactionType == 'credit' ? 'amount-positive' : 'amount-negative' }}">
                                {{ $transactionType == 'credit' ? '+' : '-' }}{{ number_format($transactionAmount, 2) }}
                            </td>
                            <td>
                                <span
                                    class="status-badge {{ $transactionType == 'credit' ? 'type-credit' : 'type-debit' }}">
                                    {{ $transactionType == 'credit' ? 'إيداع' : 'سحب' }}
                                </span>
                            </td>

                            <td class="text-truncate">{{ $transactionDescription }}</td>

                            <td>{{ \Carbon\Carbon::parse($transactionDate)->format('d/m/Y H:i') }}</td>

                            <td>
                                @if ($transactionTaskId)
                                    #{{ $transactionTaskId }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>لا توجد معاملات في الفترة المحددة</p>
            </div>
        @endif

        <!-- Summary -->
        @if (isset($summary) && is_array($summary))
            <div class="summary">
                <div class="summary-title">ملخص المحفظة</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">إجمالي الإيداعات</div>
                        <div class="summary-value amount-positive">
                            {{ number_format($summary['total_credit'] ?? 0, 2) }} ريال</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">إجمالي السحوبات</div>
                        <div class="summary-value amount-negative">{{ number_format($summary['total_debit'] ?? 0, 2) }}
                            ريال</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">الرصيد الحالي</div>
                        <div class="summary-value">{{ number_format($summary['current_balance'] ?? 0, 2) }} ريال</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">عدد المعاملات</div>
                        <div class="summary-value">{{ $summary['total_transactions'] ?? 0 }}</div>
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
