<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="UTF-8">
    <title>طلب سداد - {{ $referenceNumber }}</title>
    <style>
        body {
            font-family: 'Tajawal', Arial, sans-serif;
            margin: 0;
            padding: 20mm;
            font-size: 14px;
            color: #000;
            background: #fff;
        }

        .container {
            max-width: 210mm;
            margin: auto;
        }

        h1,
        h2,
        h3 {
            margin: 0 0 10px 0;
            font-weight: bold;
        }

        .title {
            text-align: center;
            margin-bottom: 20px;
        }

        .emp-name {
            font-size: 16px;
        }

        table {
            width: 100%;
            margin-bottom: 15px;
        }

        td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }

        .label {
            width: 30%;
            font-weight: bold;
            background: #f7f7f7;
        }

        .amount-box {
            padding: 15px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 16px;
        }

        .signatures td {
            height: 80px;
            text-align: center;
        }

        .amount-details {
            font-size: 16px;
        }

        .amount-details span {
            border: 1px solid #000;
            padding: 5px 10px;
            margin: 20px 5px;
            border-radius: 5px;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 12px;
            color: #555;
        }

        @media print {
            body {
                margin: 0;
                padding: 15mm;
                font-size: 12px;
            }

            .container {
                width: auto;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- Header -->
        <div class="title">
            <h1>Safedests</h1>
            <h2>طلب سداد مالي - محفظة الفريق</h2>
            <p>رقم الطلب: {{ $referenceNumber }}</p>
            <p>التاريخ: {{ $date }}</p>
        </div>

        <!-- Employee -->
        <p class="emp-name">
            اسم الموظف طالب السداد : <strong> {{ $user->name }}</strong>
        </p>

        <h3>بيانات السداد</h3>
        <!-- Amount -->
        <div class="amount-box">
            مبلغ السداد:
            ({{ app('App\Http\Controllers\admin\TeamWalletController')->numberToArabicWords($amount) }} ريال سعودي)
        </div>
        <div>
            <p class="amount-details">
                السداد:
                دفعة <span>{{ number_format($amount, 2) }} ريال </span>
                باقي حساب <span> {{ number_format($teamWallet->balance - $amount, 2) }} ريال </span>
                إجمالي الحساب <span>{{ number_format($teamWallet->balance, 2) }} ريال </span>
            </p>
        </div>

        <!-- Bank Info -->
        <h3>بيانات البنك</h3>
        <table>
            <tr>
                <td class="label">اسم البنك</td>
                <td>{{ $bankName }}</td>
            </tr>
            <tr>
                <td class="label">رقم الحساب</td>
                <td>{{ $accountNumber }}</td>
            </tr>
            <tr>
                <td class="label">رقم الآيبان</td>
                <td>{{ $ibanNumber }}</td>
            </tr>
            <tr>
                <td class="label">طريقة السداد</td>
                <td>
                    @if($paymentMethod === 'hyperpay')
                        HyperPay Payout (دفع آلي)
                    @elseif($paymentMethod === 'bank_transfer')
                        تحويل بنكي
                    @else
                        أخرى ({{ $paymentMethod }})
                    @endif
                </td>
            </tr>
        </table>

        <!-- Team Info -->
        <h3>بيانات الفريق</h3>
        <table>
            <tr>
                <td class="label">اسم الفريق</td>
                <td>{{ $team->name }}</td>
            </tr>
            <tr>
                <td class="label">رئيس الفريق</td>
                <td>{{ $teamLeader->name }}</td>
            </tr>
            <tr>
                <td class="label">رقم المحفظة</td>
                <td>#{{ $teamWallet->id }}</td>
            </tr>
            <tr>
                <td class="label">الرصيد المتبقي</td>
                <td> {{ number_format($teamWallet->balance - $amount, 2) }} ريال</td>
            </tr>
        </table>
        <h3>ملاحظات</h3>
        <p> <strong>{{ $notes ?: 'لا توجد ملاحظات' }}</strong> </p>

        <!-- Signatures -->
        <h3>التوقيع</h3>
        <br>
        <br>
        <br>
        <!-- Footer -->
        <div class="footer">
            <p>تم إنشاء المستند إلكترونياً بتاريخ {{ $date }}</p>
            <p>أنشأ من قبل: {{ $user->name }}</p>
        </div>

    </div>
</body>

</html>
</div>
</body>

</html>
