<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>شحن محفظة المضاربة — SafeDest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #7367f0;
            --bg-body: #f8f7fa;
            --bg-card: #ffffff;
            --text-main: #5d596c;
            --text-light: #a5a3ae;
            --success: #28c76f;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .payment-container {
            width: 100%;
            max-width: 450px;
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #eaeaea;
        }

        .payment-header {
            background: linear-gradient(135deg, #7367f0 0%, #a098f5 100%);
            padding: 30px;
            color: white;
            text-align: center;
        }

        .amount-display {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .amount-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
        }

        .payment-body {
            padding: 30px;
        }

        .payment-body h4 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.1rem;
            text-align: center;
        }

        /* HyperPay widget custom styling */
        .wpwl-form {
            max-width: 100% !important;
            margin: 0 auto !important;
        }
        .wpwl-button-pay {
            background-color: var(--primary) !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 12px !important;
            font-weight: 600 !important;
            width: 100% !important;
            box-shadow: 0 4px 10px rgba(115, 103, 240, 0.3) !important;
        }
        .wpwl-control {
            border-radius: 6px !important;
            border: 1px solid #dcdcdc !important;
            height: 40px !important;
        }

        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .trust-badges img {
            height: 25px;
            opacity: 0.7;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

    <div class="payment-container">
        <div class="payment-header">
            <div class="amount-label">مبلغ الشحن</div>
            <div class="amount-display">{{ number_format($amount, 2) }} <small style="font-size: 1rem">ر.س</small></div>
            <div style="font-size: 0.85rem; opacity: 0.8">بوابة دفع آمنة مشفرة 256-bit</div>
        </div>

        <div class="payment-body">
            <h4>أدخل بيانات البطاقة لإتمام العملية</h4>
            
            <form action="" class="paymentWidgets" data-brands="{{ $brand === 'MADA' ? 'MADA' : 'VISA MASTER' }}"></form>

            <div class="trust-badges">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Mada_Logo.svg/1280px-Mada_Logo.svg.png" alt="Mada">
                <img src="{{ asset('assets/img/icons/payments/visa.png') }}" alt="Visa">
                <img src="{{ asset('assets/img/icons/payments/mastercard.png') }}" alt="MasterCard">
            </div>

            <a href="{{ route('investor.investment-wallet') }}" class="back-link">
                &larr; العودة لمحفظة المضاربة
            </a>
        </div>
    </div>

    <script>
        var wpwlOptions = {
            locale: "ar",
            style: "plain",
            paymentTarget: "_top",
        }
    </script>
    <script src="{{ $scriptUrl }}"></script>
</body>
</html>
