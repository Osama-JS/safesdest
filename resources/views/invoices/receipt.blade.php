<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: 'Tajawal';
            src: url('{{ storage_path('fonts/Tajawal-Regular.ttf') }}') format("truetype");
        }

        body {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 14px;
            padding: 30px;
        }

        .invoice-box {
            border: 1px solid #eee;
            padding: 30px;
            max-width: 800px;
            margin: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            width: 120px;
        }

        .platform-info {
            text-align: center;
            margin-bottom: 30px;
        }

        .title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .info-row {
            margin-bottom: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #999;
        }

        .table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .table th {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <div class="logo">
            <img src="{{ public_path('images/logo.png') }}" alt="شعار المنصة">
        </div>

        <div class="platform-info">
            <strong>اسم المنصة:</strong> منصتي<br>
            <strong>العنوان:</strong> الرياض، المملكة العربية السعودية<br>
            <strong>البريد الإلكتروني:</strong> support@mansati.com
        </div>

        <div class="title">فاتورة الدفع</div>

        <div class="info-row"><strong>العميل:</strong> {{ $transaction->payable->name }}</div>
        <div class="info-row"><strong>البريد:</strong> {{ $transaction->payable->email }}</div>

        <table class="table">
            <tr>
                <th>البيان</th>
                <th>القيمة</th>
            </tr>
            <tr>
                <td>المبلغ</td>
                <td>{{ $transaction->amount }} ريال</td>
            </tr>
            <tr>
                <td>نوع العملية</td>
                <td>{{ $transaction->type }}</td>
            </tr>
            <tr>
                <td>رقم المرجع</td>
                <td>{{ $transaction->reference_id }}</td>
            </tr>
            <tr>
                <td>ملاحظة</td>
                <td>{{ $transaction->note }}</td>
            </tr>
            <tr>
                <td>تاريخ العملية</td>
                <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        </table>

        <div class="footer">
            تم إصدار هذه الفاتورة بشكل آلي بواسطة النظام
        </div>
    </div>
</body>

</html>
