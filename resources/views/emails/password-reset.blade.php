<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f7;
            padding: 0;
            margin: 0;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .email-header {
            background: #0d6efd;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }

        .email-body {
            padding: 30px;
        }

        .email-body h2 {
            margin-top: 0;
            color: #333333;
        }

        .email-body p {
            color: #555555;
            line-height: 1.6;
        }

        .reset-button {
            display: inline-block;
            margin-top: 20px;
            background-color: #0d6efd;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .email-footer {
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #999999;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="email-body">
            <h2>Hello {{ $name }},</h2>
            <p>
                We received a request to reset your password for your {{ config('app.name') }} account.
                Click the button below to reset it:
            </p>
            <p style="text-align: center;">
                <a href="{{ $url }}" class="reset-button">Reset Password</a>
            </p>
            <p>
                This link will expire in 30 minutes. If you didn’t request a password reset,
                no further action is required.
            </p>
        </div>
        <div class="email-footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>

</html>
