<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنبيه انتهاء صلاحية الملف</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert-box {
            background: {{ $is_expired ? '#f8d7da' : '#fff3cd' }};
            border: 1px solid {{ $is_expired ? '#f5c6cb' : '#ffeaa7' }};
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-right: 5px solid {{ $is_expired ? '#dc3545' : '#ffc107' }};
        }
        
        .alert-box h2 {
            color: {{ $is_expired ? '#721c24' : '#856404' }};
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .file-details {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .file-details h3 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        
        .detail-value {
            color: #6c757d;
        }
        
        .warning-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .warning-box .warning-icon {
            font-size: 36px;
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .warning-box p {
            color: #721c24;
            font-weight: bold;
            font-size: 16px;
        }
        
        .action-button {
            text-align: center;
            margin: 30px 0;
        }
        
        .action-button a {
            display: inline-block;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .action-button a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.4);
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 25px;
            text-align: center;
            font-size: 14px;
        }
        
        .footer p {
            margin-bottom: 10px;
        }
        
        .footer .logo {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            background: {{ $is_expired ? '#dc3545' : '#ffc107' }};
        }
        
        .days-counter {
            font-size: 24px;
            font-weight: bold;
            color: {{ $is_expired ? '#dc3545' : '#ffc107' }};
            text-align: center;
            margin: 15px 0;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 5px;
            }
            
            .header, .content, .footer {
                padding: 20px;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="icon">⚠️</div>
            <h1>تنبيه انتهاء صلاحية الملف</h1>
            <p>منصة SafeDests للنقل والخدمات اللوجستية</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <p style="font-size: 16px; margin-bottom: 20px;">
                عزيزي/عزيزتي <strong>{{ $user_name }}</strong> ({{ $user_type }}),
            </p>
            
            <div class="alert-box">
                <h2>
                    @if($is_expired)
                        🚨 انتهت صلاحية الملف المطلوب
                    @else
                        ⏰ ستنتهي صلاحية الملف قريباً
                    @endif
                </h2>
                
                <p style="font-size: 16px; line-height: 1.6;">
                    @if($is_expired)
                        نود إعلامكم بأن الملف المطلوب <strong>قد انتهت صلاحيته</strong> ويجب تحديثه فوراً لتجنب تعليق الحساب.
                    @else
                        نود إعلامكم بأن الملف المطلوب <strong>ستنتهي صلاحيته خلال {{ $days_remaining }} يوم</strong>.
                    @endif
                </p>
                
                <div class="days-counter">
                    @if($is_expired)
                        انتهت الصلاحية
                    @else
                        {{ $days_remaining }} يوم متبقي
                    @endif
                </div>
            </div>
            
            <!-- File Details -->
            <div class="file-details">
                <h3>📄 تفاصيل الملف</h3>
                
                <div class="detail-row">
                    <span class="detail-label">نوع الملف:</span>
                    <span class="detail-value">{{ $field_label }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">تاريخ انتهاء الصلاحية:</span>
                    <span class="detail-value">{{ $expiration_date }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">الحالة:</span>
                    <span class="status-badge">
                        @if($is_expired)
                            منتهي الصلاحية
                        @else
                            سينتهي قريباً
                        @endif
                    </span>
                </div>
            </div>
            
            @if($is_expired)
                <div class="warning-box">
                    <div class="warning-icon">⚠️</div>
                    <p>
                        تحذير: سيتم تعليق حسابكم خلال 3 أيام من تاريخ هذا الإشعار إذا لم يتم تحديث الملف.
                    </p>
                </div>
            @endif
            
            <p style="font-size: 16px; line-height: 1.6; margin: 20px 0;">
                يرجى تحديث الملف في أقرب وقت ممكن لضمان استمرارية الخدمة وتجنب أي انقطاع في حسابكم.
            </p>
            
            @if($action_url)
                <div class="action-button">
                    <a href="{{ $action_url }}">
                        {{ $action_text ?? 'تحديث الملف الآن' }}
                    </a>
                </div>
            @endif
            
            <div style="background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <p style="font-size: 14px; color: #6c757d; margin: 0;">
                    <strong>ملاحظة:</strong> هذا إشعار تلقائي من نظام SafeDests. 
                    إذا كنت قد حدثت الملف مؤخراً، يرجى تجاهل هذا الإشعار.
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="logo">🚛 SafeDests</div>
            <p>منصة النقل والخدمات اللوجستية الرائدة</p>
            <p>هذا إشعار تلقائي، يرجى عدم الرد على هذا الإيميل</p>
            <p style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
                للدعم الفني: support@safedests.com | الموقع: www.safedests.com
            </p>
        </div>
    </div>
</body>
</html>
