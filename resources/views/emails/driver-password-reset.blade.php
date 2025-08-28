<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور - SafeDests Driver</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .message {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
            color: #555;
        }
        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .security-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            color: #856404;
        }
        .security-note h3 {
            margin-top: 0;
            color: #856404;
            font-size: 16px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 14px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .expiry-info {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #1565c0;
            text-align: center;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 5px;
            }
            .header, .content, .footer {
                padding: 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .reset-button {
                display: block;
                margin: 20px 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SafeDests Driver</div>
            <h1>إعادة تعيين كلمة المرور</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                مرحباً {{ $driver->name }},
            </div>
            
            <div class="message">
                تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في تطبيق SafeDests Driver.
                إذا كنت قد طلبت هذا التغيير، يرجى النقر على الزر أدناه لإعادة تعيين كلمة المرور الخاصة بك.
            </div>
            
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="reset-button">
                    إعادة تعيين كلمة المرور
                </a>
            </div>
            
            <div class="expiry-info">
                <strong>ملاحظة:</strong> هذا الرابط صالح لمدة ساعة واحدة فقط من وقت إرسال هذا البريد.
            </div>
            
            <div class="security-note">
                <h3>🔒 ملاحظة أمنية مهمة</h3>
                <ul style="margin: 10px 0; padding-right: 20px;">
                    <li>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد</li>
                    <li>لا تشارك هذا الرابط مع أي شخص آخر</li>
                    <li>تأكد من استخدام كلمة مرور قوية وفريدة</li>
                    <li>إذا كنت تشك في أن حسابك قد تم اختراقه، يرجى التواصل معنا فوراً</li>
                </ul>
            </div>
            
            <div class="message">
                إذا كنت تواجه مشكلة في النقر على الزر أعلاه، يمكنك نسخ الرابط التالي ولصقه في متصفحك:
                <br><br>
                <a href="{{ $resetUrl }}" style="color: #667eea; word-break: break-all;">{{ $resetUrl }}</a>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>فريق SafeDests Driver</strong></p>
            <p>هذا بريد إلكتروني تلقائي، يرجى عدم الرد عليه</p>
            <p>إذا كنت بحاجة إلى مساعدة، يرجى التواصل مع فريق الدعم</p>
            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                © {{ date('Y') }} SafeDests. جميع الحقوق محفوظة.
            </p>
        </div>
    </div>
</body>
</html>
