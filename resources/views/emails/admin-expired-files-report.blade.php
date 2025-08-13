<!DOCTYPE html>
<html lang="en" dir="ltr">

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
            color: #333;
            background-color: #f8f9fa;
            direction: ltr;
        }

        .email-wrapper {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        .email-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #d63031 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .email-header h1 {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 8px;
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
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .content {
            font-size: 16px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 30px;
        }

        /* Report Table */
        .table-container {
            width: 100%;
            overflow-x: scroll;
            /* instead of auto */
            scrollbar-width: thin;
            /* Firefox specific */
            scrollbar-color: #ccc #f1f1f1;
            /* scrollbar color */
        }

        /* WebKit specific (Chrome, Edge, Safari) */
        .table-container::-webkit-scrollbar {
            height: 8px;
            /* scrollbar height */
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .table-container::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 4px;
        }


        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .report-table th,
        .report-table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        .report-table th {
            background-color: #f1f3f5;
            font-weight: 600;
            color: #2c3e50;
        }

        .report-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .highlight {
            background-color: #ffe3e3;
            color: #c92a2a;
            font-weight: bold;
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
            color: #ff6b6b;
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
        }

        .company-info {
            font-size: 12px;
            color: #adb5bd;
            margin-top: 5px;
        }

        @media (max-width: 600px) {
            .email-wrapper {
                margin: 10px;
                border-radius: 8px;
            }

            .email-body {
                padding: 30px 20px;
            }

            .report-table th,
            .report-table td {
                font-size: 12px;
                padding: 8px;
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

            <div class="content">
                We are sending you this report to show details of expired files in the system.
                Please review the data below and take necessary actions.
            </div>

            <!-- Report Table -->
            @if (!empty($report_html) && $report_html)
                <div class="table-container">
                    {!! $report_html !!}
                </div>
            @else
                <div class="content" style="color: #28a745; font-weight: bold;">
                    No expired files at the moment ✅
                </div>
            @endif


            <div class="content" style="margin-top: 30px; font-size: 14px; color: #6c757d;">
                You can access the control panel to view more details or renew expired files.
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-links">
                <a href="{{ config('app.url') }}">Main Website</a>
                <a href="{{ config('app.url') }}/admin">Control Panel</a>
            </div>
            <div class="copyright">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
            <div class="company-info">
                This email was sent automatically, please do not reply to it.
            </div>
        </div>
    </div>
</body>

</html>
