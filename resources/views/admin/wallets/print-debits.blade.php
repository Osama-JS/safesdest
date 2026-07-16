<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة عمليات الدفع للسائق</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}">
    <style>
        body { background-color: #fff; padding: 20px; font-family: 'Cairo', sans-serif; }
        .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .print-header h3 { margin: 0; padding: 0; }
        .info-box { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f8f9fa; }
        .total-row { font-weight: bold; background-color: #e9ecef; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print text-center mb-4">
        <button onclick="window.print()" class="btn btn-primary">طباعة الصفحة</button>
    </div>

    <div class="print-header">
        <h3>كشف حساب السائق (عمليات الدفع)</h3>
        <p>تاريخ الطباعة: {{ date('Y-m-d H:i') }}</p>
    </div>

    <div class="info-box">
        <div class="row">
            <div class="col-6">
                <strong>اسم السائق:</strong> {{ $wallet->driver->name ?? 'غير محدد' }}<br>
                <strong>رقم الجوال:</strong> <span dir="ltr">{{ $wallet->driver->phone ?? 'غير محدد' }}</span>
            </div>
            <div class="col-6">
                <strong>رقم المحفظة:</strong> #{{ $wallet->id }}<br>
                <strong>الرصيد الحالي للمحفظة:</strong> {{ number_format($wallet->balance, 2) }} ريال
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>رقم العملية</th>
                <th>التاريخ والوقت</th>
                <th>المبلغ (ريال)</th>
                <th>البيان (الوصف)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; @endphp
            @forelse($transactions as $index => $transaction)
                @php $totalAmount += $transaction->amount; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $transaction->sequence }}</td>
                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ number_format($transaction->amount, 2) }}</td>
                    <td>{{ $transaction->description }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">لا توجد عمليات دفع مسجلة لهذا السائق.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" class="text-center">إجمالي المبالغ المدفوعة</td>
                <td colspan="2">{{ number_format($totalAmount, 2) }} ريال</td>
            </tr>
        </tbody>
    </table>

    <script>
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
