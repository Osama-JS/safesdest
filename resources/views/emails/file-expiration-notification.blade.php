<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Expiration Alert</title>
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .action-button a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4);
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

            .header,
            .content,
            .footer {
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
            <h1>File Expiration Alert</h1>
            <p>SafeDests Transport and Logistics Platform</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p style="font-size: 16px; margin-bottom: 20px;">
                Dear <strong>{{ $user_name }}</strong> ({{ $user_type }}),
            </p>

            <div class="alert-box">
                <h2>
                    @if ($is_expired)
                        🚨 Required File Has Expired
                    @else
                        ⏰ File Will Expire Soon
                    @endif
                </h2>

                <p style="font-size: 16px; line-height: 1.6;">
                    @if ($is_expired)
                        We would like to inform you that the required file <strong>has expired</strong> and must be
                        updated immediately to avoid account suspension.
                    @else
                        We would like to inform you that the required file <strong>will expire in {{ $days_remaining }}
                            days</strong>.
                    @endif
                </p>

                <div class="days-counter">
                    @if ($is_expired)
                        Expired
                    @else
                        {{ $days_remaining }} days remaining
                    @endif
                </div>
            </div>

            <!-- File Details -->
            <div class="file-details">
                <h3>📄 File Details</h3>

                <div class="detail-row">
                    <span class="detail-label">File Type:</span>
                    <span class="detail-value">{{ $field_label }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Expiration Date:</span>
                    <span class="detail-value">{{ $expiration_date }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge">
                        @if ($is_expired)
                            Expired
                        @else
                            Expiring Soon
                        @endif
                    </span>
                </div>
            </div>

            @if ($is_expired)
                <div class="warning-box">
                    <div class="warning-icon">⚠️</div>
                    <p>
                        Warning: Your account will be suspended within 3 days from this notification if the file is not
                        updated.
                    </p>
                </div>
            @endif

            <p style="font-size: 16px; line-height: 1.6; margin: 20px 0;">
                Please update the file as soon as possible to ensure service continuity and avoid any interruption to
                your account.
            </p>

            {{-- @if ($action_url)
                <div class="action-button">
                    <a href="{{ $action_url }}">
                        {{ $action_text ?? 'Update File Now' }}
                    </a>
                </div>
            @endif --}}

            <div style="background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <p style="font-size: 14px; color: #6c757d; margin: 0;">
                    <strong>Note:</strong> This is an automatic notification from SafeDests system.
                    If you have recently updated the file, please ignore this notification.
                </p>
            </div>
        </div>


    </div>
</body>

</html>
