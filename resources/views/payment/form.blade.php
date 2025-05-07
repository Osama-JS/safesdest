<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>إتمام الدفع</title>
</head>

<body>
    <h1>إتمام عملية الدفع</h1>
    <script src="https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId={{ $checkout_id }}"></script>

    <form action="{{ route('payment.callback') }}" class="paymentWidgets" data-brands="VISA"></form>
</body>

</html>
