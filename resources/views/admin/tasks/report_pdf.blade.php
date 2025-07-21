<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title>#{{ $task->id }}</title>
    <style>
        @font-face {
            font-family: 'Tajawal';
            /* قم بوضع الخط داخل مجلد عام يمكن الوصول إليه أو مسار ثابت */
            src: url("{{ storage_path('fonts/Tajawal-Regular.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'Tajawal', sans-serif !important;
            margin: 0;
            background-color: #fafafa;
            color: #2c3e50;
            line-height: 1.5;
            font-size: 14px;
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        header {
            background-color: #1a2733;
            color: white;
            padding: 10px 25px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .platform-info div {
            margin: 2px 0;
        }

        /* الصور في DomPDF قد لا تعمل مع url asset، لذلك سنحول الصورة إلى base64 */
        .logo img {
            max-height: 40px;
            object-fit: contain;
        }

        main {
            background: white;
            margin: 20px auto;
            padding: 25px 30px;
            max-width: 800px;
            border-radius: 6px;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.05);
            font-size: 13px;
        }

        h1 {
            text-align: center;
            color: #1a2733;
            font-size: 18px;
            margin-bottom: 25px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 15px;
            color: #34495e;
            font-weight: 600;
            margin-bottom: 12px;
            border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 4px solid #1abc9c;
            padding-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px 20px;
        }

        .info-item {
            background-color: #f5f5f5;
            padding: 3px 5px;
            border-radius: 4px;
            word-break: break-word;
        }

        .label {
            font-weight: 600;
            color: #34495e;
            display: block;
            min-width: 100px;
        }

        .value {
            color: #555;
        }

        .note {
            background-color: #fff8e1;
            padding: 12px 16px;
            border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 4px solid #fbc02d;
            border-radius: 4px;
            font-style: italic;
            font-size: 13px;
            color: #665c00;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #e1e4e8;
            padding-top: 15px;
        }

        /* بعض خصائص الطباعة يمكن تجاهلها أو تبسيطها */
    </style>
</head>

<body>

    <header>
        <div class="platform-info">
            <div><strong>{{ __('Safe Dest') }}</strong></div>
            <div>{{ __('info@safedest.com') }}</div>
            <div>{{ __('+966556978782') }}</div>
        </div>
        <div class="logo">
            {{-- استبدل الصورة بـ base64 --}}
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/img/logo.png'))) }}"
                alt="Logo" />
        </div>
        <div class="lang-switch" style="display:none;">
            {{-- اخفاء لان الpdf ثابت --}}
        </div>
    </header>

    <main>
        <h1>{{ __('Task Status Report') }} #{{ $task->id }}</h1>

        <div class="section">
            <div class="section-title">{{ __('task information') }}</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">{{ __('Task ID') }}</span>
                    <span class="value">{{ $task->id }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('customer name') }}</span>
                    <span class="value">{{ $task->customer?->company_name }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('status') }}</span>
                    <span class="value">{{ $task->status }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('Vehicle') }}</span>
                    <span class="value">
                        {{ $task->vehicle_size?->type?->vehicle->name }} ({{ $task->vehicle_size?->type->name }})
                        ({{ $task->vehicle_size?->name }})
                    </span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('Start before') }}</span>
                    <span class="value">{{ $task->pickup->scheduled_time }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('complete before') }}</span>
                    <span class="value">{{ $task->delivery->scheduled_time }}</span>
                </div>
            </div>
        </div>

        {{-- بقية الأقسام تنطبق عليها نفس الملاحظات:
             - استبدال الصور إلى base64
             - تبسيط التنسيق --}}

        <div class="section">
            <div class="section-title">{{ __('Pickup Point') }}</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">{{ __('Name') }}</span>
                    <span class="value">{{ $task->pickup->contact_name }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('phone number') }}</span>
                    <span class="value">{{ $task->pickup?->contact_phone }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('email') }}</span>
                    <span class="value">{{ $task->pickup->contact_emil }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('Address') }}</span>
                    <span class="value">{{ $task->pickup?->address }}</span>
                </div>
                @if ($task->pickup?->note)
                    <div class="info-item note">{{ $task->pickup?->note }}</div>
                @endif
                @if ($task->pickup->image)
                    <div class="info-item">
                        <span class="label">{{ __('Reference Image') }}</span>
                        <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path($task->pickup->image))) }}"
                            style="width: 50px;" />
                    </div>
                @endif
            </div>
        </div>

        {{-- نفس الشيء لبقية الصور والنصوص --}}

        <footer>
            {{ __('task report generated at') }}: {{ now()->format('Y-m-d H:i') }}
        </footer>
    </main>

</body>

</html>
