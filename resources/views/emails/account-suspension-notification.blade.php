<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspension Notification</title>
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
            background: linear-gradient(135deg, #dc3545, #c82333);
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

        .suspension-alert {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
            border-right: 5px solid #dc3545;
            text-align: center;
        }

        .suspension-alert h2 {
            color: #721c24;
            margin-bottom: 15px;
            font-size: 22px;
        }

        .suspension-alert .suspension-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 15px;
        }

        .reason-box {
            background: #fff;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .reason-box h3 {
            color: #dc3545;
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

        .steps-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .steps-box h3 {
            color: #0c5460;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .steps-list {
            list-style: none;
            padding: 0;
        }

        .steps-list li {
            background: white;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            border-right: 4px solid #17a2b8;
            position: relative;
        }

        .steps-list li::before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            right: -15px;
            top: 50%;
            transform: translateY(-50%);
            background: #17a2b8;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }

        .steps-list {
            counter-reset: step-counter;
        }

        .action-button {
            text-align: center;
            margin: 30px 0;
        }

        .action-button a {
            display: inline-block;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .action-button a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }

        .contact-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .contact-box h3 {
            color: #495057;
            margin-bottom: 15px;
        }

        .contact-info {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 15px;
        }

        .contact-item {
            flex: 1;
            min-width: 150px;
        }

        .contact-item .icon {
            font-size: 24px;
            margin-bottom: 10px;
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
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: white;
            background: #dc3545;
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

            .contact-info {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="icon">🚫</div>
            <h1>Account Suspended</h1>
            <p>SafeDests Transport and Logistics Platform</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p style="font-size: 16px; margin-bottom: 20px;">
                Dear <strong>{{ $user_name }}</strong> ({{ $user_type }}),
            </p>

            <div class="suspension-alert">
                <div class="suspension-icon">🚫</div>
                <h2>Your Account Has Been Temporarily Suspended</h2>
                <p style="font-size: 16px; line-height: 1.6;">
                    We regret to inform you that your account on SafeDests platform has been suspended due to failure to
                    update required files within the specified time.
                </p>
                <div class="status-badge">Account Suspended</div>
            </div>

            <!-- Suspension Reason -->
            <div class="reason-box">
                <h3>📋 Suspension Reason</h3>

                <div class="detail-row">
                    <span class="detail-label">Reason:</span>
                    <span class="detail-value">{{ $suspension_reason }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Required File:</span>
                    <span class="detail-value">{{ $field_label }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Expiration Date:</span>
                    <span class="detail-value">{{ $expiration_date }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Suspension Date:</span>
                    <span class="detail-value">{{ now()->format('Y-m-d H:i:s') }}</span>
                </div>
            </div>

            <!-- Steps to Reactivate -->
            <div class="steps-box">
                <h3>🔄 Steps to Reactivate Account</h3>
                <p style="margin-bottom: 15px; color: #0c5460;">
                    To reactivate your account, please follow these steps:
                </p>

                <ol class="steps-list">
                    <li>
                        <strong>Contact Support Team</strong><br>
                        Contact our technical support team to review the file and reactivate the account
                    </li>
                    <li>
                        <strong>Wait for Activation</strong><br>
                        Your request will be reviewed and the account will be reactivated within 24 business hours
                    </li>
                </ol>
            </div>

            @if ($action_url)
                <div class="action-button">
                    <a href="{{ $action_url }}">
                        {{ $action_text ?? 'Update File and Reactivate' }}
                    </a>
                </div>
            @endif



            <div
                style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-top: 20px;">
                <p style="font-size: 14px; color: #856404; margin: 0;">
                    <strong>Important Note:</strong> Account suspension is temporary and can be reactivated once the
                    required files are updated.
                    We appreciate your understanding and cooperation in maintaining safety and quality standards on the
                    platform.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div style="background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <p style="font-size: 14px; color: #6c757d; margin: 0;">
                <strong>Note:</strong> This is an automatic notification from SafeDests system.
                If you have recently updated the file, please ignore this notification.
            </p>
        </div>
    </div>
</body>

</html>
