<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title>#{{ $task->id }}</title>
    <style>
        @font-face {
            font-family: 'Tajawal';
            src: url("{{ public_path('fonts/Tajawal-Regular.ttf') }}") format('truetype');
        }

        body {
            font-family: 'Tajawal', sans-serif;
            margin: 0;
            /* تقليل الهوامش قدر الإمكان */
            padding: 0;
            color: #2c3e50;
            font-size: 12px;
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .platform-info {
            font-size: 12px;
            line-height: 1.4;
            color: #555;
        }

        .logo img {
            height: 40px;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            color: #1a2733;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 20px;
            padding: 0 10px;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 6px;
            padding-bottom: 4px;
            padding-left: 7px;
            border-left: 4px solid #070000;
        }

        table.header-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 10px;
        }

        table.info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 10px;
        }

        table.info-table td {
            background-color: #f5f7fa;
            padding: 3px;
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            width: 33%;
            vertical-align: top;
        }

        .label {
            font-size: 12px;
            color: #000;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .value {
            font-size: 13px;
            color: #2c3e50;
        }

        footer {
            text-align: center;
            font-size: 11px;
            color: #888;
            border-top: 1px solid #e1e1e1;
            margin-top: 30px;
            padding-top: 10px;
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <header>
        <table class="header-table">
            <td>
                <div class="platform-info">
                    <div><strong>{{ __('Safe Dest') }}</strong></div>
                    <div>{{ __('info@safedest.com') }}</div>
                    <div>{{ __('+966556978782') }}</div>
                </div>
            </td>
            <td style="text-align: right">
                <div class="logo">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/img/logo.png'))) }}"
                        alt="Logo">
                </div>
            </td>
        </table>
    </header>

    {{-- Title --}}
    <h1>{{ __('Task Status Report') }} #{{ $task->id }}</h1>

    {{-- Task Info --}}
    <div class="section">
        <div class="section-title">{{ __('task information') }}</div>
        <table class="info-table">
            <tr>
                <td>
                    <div class="label">{{ __('Task ID') }}</div>
                    <div class="value">{{ $task->id }}</div>
                </td>
                <td>
                    <div class="label">{{ __('customer name') }}</div>
                    <div class="value">{{ $task->customer?->company_name }}</div>
                </td>
                <td>
                    <div class="label">{{ __('status') }}</div>
                    <div class="value">{{ $task->status }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">{{ __('Vehicle') }}</div>
                    <div class="value">
                        {{ $task->vehicle_size?->type?->vehicle->name }}
                        ({{ $task->vehicle_size?->type->name }})
                        ({{ $task->vehicle_size?->name }})
                    </div>
                </td>
                <td>
                    <div class="label">{{ __('Start before') }}</div>
                    <div class="value">{{ $task->pickup->scheduled_time }}</div>
                </td>
                <td>
                    <div class="label">{{ __('complete before') }}</div>
                    <div class="value">{{ $task->delivery->scheduled_time }}</div>
                </td>
            </tr>

        </table>
    </div>

    {{-- Pickup Info --}}
    <div class="section">
        <div class="section-title">{{ __('Pickup Point') }}</div>
        <table class="info-table">
            <tr>
                <td>
                    <div class="label">{{ __('Name') }}</div>
                    <div class="value">{{ $task->pickup->contact_name }}</div>
                </td>
                <td>
                    <div class="label">{{ __('phone number') }}</div>
                    <div class="value">{{ $task->pickup->contact_phone }}</div>
                </td>
                <td>
                    <div class="label">{{ __('email') }}</div>
                    <div class="value">{{ $task->pickup->contact_emil }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">{{ __('Address') }}</div>
                    <div class="value">{{ $task->pickup->address }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Delivery Info --}}
    <div class="section">
        <div class="section-title">{{ __('Delivery Point') }}</div>
        <table class="info-table">
            <tr>
                <td>
                    <div class="label">{{ __('Name') }}</div>
                    <div class="value">{{ $task->delivery->contact_name }}</div>
                </td>
                <td>
                    <div class="label">{{ __('phone number') }}</div>
                    <div class="value">{{ $task->delivery->contact_phone }}</div>
                </td>
                <td>
                    <div class="label">{{ __('email') }}</div>
                    <div class="value">{{ $task->delivery->contact_emil }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">{{ __('Address') }}</div>
                    <div class="value">{{ $task->delivery->address }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Driver Info --}}
    <div class="section">
        <div class="section-title">{{ __('Driver Information') }}</div>
        <table class="info-table">
            <tr>
                <td>
                    <div class="label">{{ __('Driver name') }}</div>
                    <div class="value">{{ $task->driver?->name }}</div>
                </td>
                <td>
                    <div class="label">{{ __('Phone number') }}</div>
                    <div class="value">{{ $task->driver?->phone }}</div>
                </td>
                <td>
                    <div class="label">{{ __('Team') }}</div>
                    <div class="value">{{ $task->driver?->team?->name }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">{{ __('Left Letter') }}</div>
                    <div class="value">{{ $task->driver?->vehicle_letters?->left }}</div>
                </td>
                <td>
                    <div class="label">{{ __('Right Letter') }}</div>
                    <div class="value">{{ $task->driver?->vehicle_letters?->right }}</div>
                </td>
                <td>
                    <div class="label">{{ __('Middle Letter') }}</div>
                    <div class="value">{{ $task->driver?->vehicle_letters?->middle }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="label">{{ __('Vehicle Number') }}</div>
                    <div class="value">{{ $task->driver?->vehicle_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <footer>
        {{ __('task report generated at') }}: {{ now()->format('Y-m-d H:i') }}
    </footer>

</body>

</html>
