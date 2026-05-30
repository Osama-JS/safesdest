<table>
    <tr>
        <td colspan="7" style="font-size:22px;font-weight:bold;text-align:center;background:#e3f0fa;">تقرير محفظة العمولات للمستثمر: {{ $investor->name }}</td>
    </tr>
    <tr>
        <td colspan="7" style="font-size:14px;text-align:center;">رقم المحفظة: {{ $wallet->id }} | الرصيد الحالي: {{ number_format($wallet->balance,2) }} ر.س</td>
    </tr>
    <tr style="background:#d1e7f7;font-weight:bold;">
        <td>م</td>
        <td>رقم العملية</td>
        <td>النوع</td>
        <td>المبلغ</td>
        <td>الوصف</td>
        <td>تاريخ العملية</td>
        <td>الرصيد بعد العملية</td>
    </tr>
    @foreach($transactions as $i=>$trx)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $trx->id }}</td>
        <td>{{ $trx->transaction_type === 'credit' ? 'إيداع' : 'خصم' }}</td>
        <td>{{ number_format($trx->amount,2) }}</td>
        <td>{{ $trx->description }}</td>
        <td>{{ $trx->created_at->format('Y-m-d H:i') }}</td>
        <td>{{ number_format($trx->balance_after,2) }}</td>
    </tr>
    @endforeach
</table>
