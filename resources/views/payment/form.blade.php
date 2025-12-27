<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>إتمام الدفع</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(to right, #f3f4f6, #e5e7eb);
        }

        .widgetFrame {
            border-radius: 1rem !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen px-4">

    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-lg text-center">
        <h1 class="text-2xl font-bold mb-4 text-gray-800">{{ __('إتمام عملية الدفع') }}</h1>
        <p class="mb-6 text-gray-600">{{ __('يرجى تعبئة بيانات البطاقة لإتمام عملية الدفع بأمان') }}</p>

        <!-- HyperPay Widget -->
        <script src="{{ $script_url }}?checkoutId={{ $checkout_id }}"></script>
        <form action="{{ route('payment.callback') }}" class="paymentWidgets" data-brands="VISA MASTER MADA"></form>

        <div class="mt-6 text-sm text-gray-500">
            جميع المعاملات مؤمنة ومشفرة بواسطة HyperPay 🔒
        </div>
    </div>

</body>

</html>
