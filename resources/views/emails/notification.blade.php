<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
            direction: rtl;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        .email-header {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .email-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .email-header .subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .email-body {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .content {
            font-size: 16px;
            line-height: 1.8;
            color: #555555;
            margin-bottom: 30px;
        }

        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(40, 199, 111, 0.3);
            transition: all 0.3s ease;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 199, 111, 0.4);
        }

        .action-section {
            text-align: center;
            margin: 30px 0;
        }

        .info-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .info-box h3 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .email-footer {
            background: #f8f9fa;
            padding: 30px 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer-links {
            margin-bottom: 20px;
        }

        .footer-links a {
            color: #696cff;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .copyright {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .company-info {
            font-size: 12px;
            color: #adb5bd;
        }

        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e9ecef, transparent);
            margin: 30px 0;
        }

        @media (max-width: 600px) {
            .email-wrapper {
                margin: 10px;
                border-radius: 8px;
            }

            .email-body {
                padding: 30px 20px;
            }

            .email-header {
                padding: 25px 15px;
            }

            .email-header h1 {
                font-size: 24px;
            }

            .action-button {
                display: block;
                width: 100%;
                padding: 15px;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <h1>{{ config('app.name') }}</h1>
            <div class="subtitle">{{ $subject }}</div>
        </div>

        <!-- Body -->
        <div class="email-body">
            <!-- Greeting -->
            <div class="greeting">
                مرحباً {{ $user_name ?? 'عزيزي المستخدم' }}،
            </div>

            <!-- Content -->
            <div class="content">
                {!! $content ?? 'محتوى الإشعار' !!}
            </div>

            <!-- Action Button -->
            @if(isset($action_url) && isset($action_text))
                <div class="action-section">
                    <a href="{{ $action_url }}" class="action-button">
                        {{ $action_text }}
                    </a>
                </div>
            @endif

            <!-- Additional Data -->
            @if(isset($additional_data) && !empty($additional_data))
                <div class="info-box">
                    <h3>تفاصيل إضافية</h3>
                    @foreach($additional_data as $key => $value)
                        <div class="info-item">
                            <span class="info-label">{{ $key }}:</span>
                            <span class="info-value">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="divider"></div>

            <!-- Footer Message -->
            <div class="content" style="font-size: 14px; color: #6c757d;">
                إذا كان لديك أي استفسارات، لا تتردد في التواصل معنا.
                <br>
                شكراً لاستخدامك {{ config('app.name') }}.
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-links">
                <a href="{{ config('app.url') }}">الموقع الرئيسي</a>
                <a href="{{ config('app.url') }}/contact">تواصل معنا</a>
                <a href="{{ config('app.url') }}/privacy">سياسة الخصوصية</a>
            </div>
            
            <div class="copyright">
                &copy; {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.
            </div>
            
            <div class="company-info">
                هذا البريد الإلكتروني تم إرساله تلقائياً، يرجى عدم الرد عليه.
            </div>
        </div>
    </div>
</body>
</html>
