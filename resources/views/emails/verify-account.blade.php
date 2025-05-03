<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
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

        .verify-button {
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
            <h1>Welcome to {{ config('app.name') }}</h1>
        </div>
        <div class="email-body">
            <h2>Hello {{ $user->name }},</h2>
            <p>
                Thank you for registering with {{ config('app.name') }}. <br>
                Please verify your email address by clicking the button below:
            </p>
            <p style="text-align: center;">
                <a href="{{ $verifyLink }}" class="verify-button">Verify Email</a>
            </p>
            <p>If you did not sign up for this account, you can ignore this email.</p>
        </div>
        <div class="email-footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>

</html>
