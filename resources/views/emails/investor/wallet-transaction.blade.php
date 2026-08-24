<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'إشعار محفظة الاستثمار' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            line-height: 1.6;
            direction: rtl;
            text-align: right;
            padding: 20px 10px;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 36px 24px;
            text-align: center;
            color: #ffffff;
            position: relative;
        }
        .header-logo-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            color: #ffffff;
        }
        .header-subtitle {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .intro-text {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.7;
        }
        .card-highlight {
            background: #f8fafc;
            border-radius: 14px;
            padding: 24px 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
            margin-bottom: 28px;
        }
        .badge {
            display: inline-block;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 20px;
            margin-bottom: 12px;
        }
        .badge-credit {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-debit {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .badge-info {
            background-color: #e0e7ff;
            color: #4338ca;
        }
        .amount-display {
            font-size: 34px;
            font-weight: 800;
            margin: 6px 0;
            letter-spacing: -0.5px;
        }
        .amount-credit {
            color: #16a34a;
        }
        .amount-debit {
            color: #dc2626;
        }
        .currency {
            font-size: 16px;
            font-weight: 600;
            color: #64748b;
        }
        .balance-info {
            font-size: 13px;
            color: #475569;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #cbd5e1;
        }
        .balance-info strong {
            color: #0f172a;
            font-size: 14px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .details-table tr {
            border-bottom: 1px solid #f1f5f9;
        }
        .details-table tr:last-child {
            border-bottom: none;
        }
        .details-table td {
            padding: 12px 6px;
            font-size: 13px;
        }
        .details-label {
            color: #64748b;
            width: 40%;
            font-weight: 500;
        }
        .details-value {
            color: #0f172a;
            font-weight: 600;
            text-align: left;
            direction: ltr;
        }
        .tasks-badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-end;
            direction: ltr;
        }
        .task-tag {
            background: #e2e8f0;
            color: #334155;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin: 2px;
        }
        .btn-action {
            display: block;
            width: 100%;
            text-align: center;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            transition: all 0.2s;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #94a3b8;
        }
        .footer p {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Header -->
        <div class="header">
            <div class="header-logo-title">سيف دست | SAFEDEST</div>
            <div class="header-subtitle">بوابة إدارة الاستثمار والمضاربة</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">مرحباً {{ $investor_name ?? 'شريكنا المستثمر' }}،</div>
            <p class="intro-text">{{ $intro_message }}</p>

            <!-- Card Highlight -->
            <div class="card-highlight">
                <span class="badge {{ $transaction_type === 'credit' ? 'badge-credit' : 'badge-debit' }}">
                    {{ $badge_title }}
                </span>
                
                <div class="amount-display {{ $transaction_type === 'credit' ? 'amount-credit' : 'amount-debit' }}">
                    {{ $transaction_type === 'credit' ? '+' : '-' }}{{ number_format($amount, 2) }} <span class="currency">ر.س</span>
                </div>

                @if(isset($new_balance))
                <div class="balance-info">
                    رصيد محفظة الاستثمار بعد المعاملة: <strong>{{ number_format($new_balance, 2) }} ر.س</strong>
                </div>
                @endif
            </div>

            <!-- Details Table -->
            <table class="details-table">
                <tr>
                    <td class="details-label">نوع المعاملة:</td>
                    <td class="details-value" style="direction: rtl; text-align: left;">{{ $operation_title }}</td>
                </tr>

                @if(!empty($tasks_count) && $tasks_count > 0)
                <tr>
                    <td class="details-label">عدد المهام:</td>
                    <td class="details-value" style="direction: rtl; text-align: left;">{{ $tasks_count }} {{ $tasks_count > 1 ? 'مهام' : 'مهمة' }}</td>
                </tr>
                @endif

                @if(!empty($task_ids) && count($task_ids) > 0)
                <tr>
                    <td class="details-label">أرقام المهام:</td>
                    <td class="details-value">
                        <div class="tasks-badge-container">
                            @foreach($task_ids as $taskId)
                                <span class="task-tag">#{{ $taskId }}</span>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endif

                @if(!empty($note))
                <tr>
                    <td class="details-label">ملاحظات:</td>
                    <td class="details-value" style="direction: rtl; text-align: left;">{{ $note }}</td>
                </tr>
                @endif

                <tr>
                    <td class="details-label">التاريخ والوقت:</td>
                    <td class="details-value" style="direction: ltr; text-align: left;">{{ $date_time ?? now()->format('Y-m-d H:i') }}</td>
                </tr>
            </table>

            <!-- Button -->
            <div style="margin-top: 25px;">
                <a href="{{ $action_url ?? url('/investor/investment-wallet') }}" class="btn-action">
                    الدخول إلى محفظة الاستثمار
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>هذا الإشعار تم إرساله آلياً من نظام إدارة محافظ الاستثمار لمنصة سيف دست.</p>
            <p>© {{ date('Y') }} Safedest. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
