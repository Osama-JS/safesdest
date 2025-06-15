<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title># {{ $task->id }}</title>
    <style>
        @font-face {
            font-family: 'Tajawal';
            src: url('{{ resource_path('fonts/Tajawal-Regular.ttf') }}') format('truetype');
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
        }

        header .platform-info div {
            margin: 2px 0;
        }

        header img {
            max-height: 40px;
            object-fit: contain;
        }

        .lang-switch a {
            color: white;
            font-size: 12px;
            text-decoration: underline;
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
            border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 4px solid #1abc9c;
            padding-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 10px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px 20px;
        }

        .info-item {
            background-color: #f5f5f5;
            padding: 3px 5px;
            border-radius: 4px;

        }

        .label {
            font-weight: 600;
            color: #34495e;
            min-width: 100px;
            display: block;
        }

        .value {
            color: #555;
            word-break: break-word;
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

        @media print {
            body {
                background: none;
                color: black;
                font-size: 12pt;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            header {
                background: none !important;
                color: black !important;
                box-shadow: none;
                padding: 10px 0;
            }

            main {
                box-shadow: none;
                border-radius: 0;
                margin: 0;
                padding: 0;
            }

            .section-title {
                border-color: black !important;
            }

            .lang-switch {
                display: none;
            }
        }
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
            <img src="{{ url(asset('assets/img/logo.png')) }}" alt="Logo" />
        </div>
        <div class="lang-switch">
            <a href="{{ route('lang.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}">
                {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
            </a>
        </div>
    </header>

    <main>
        <h1>{{ __('Task Status Report') }} #{{ $task->id }}</h1>

        <div class="section">
            <div class="section-title">{{ __('task information') }}</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">{{ __('Task ID') }}</span>
                    <span class="value ">{{ $task->id }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('customer name') }}</span>
                    <span class="value ">{{ $task->customer?->company_name }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('status') }}</span>
                    <span class="value ">{{ $task->status }}</span>
                </div>
                <div class="info-item"><span class="label">{{ __('Vehicle') }}</span><span class="value">
                        {{ $task->vehicle_size?->type?->vehicle->name }} ({{ $task->vehicle_size?->type->name }})
                        ({{ $task->vehicle_size?->name }})</span></div>
                <div class="info-item">
                    <span class="label">{{ __('Start before') }}</span>
                    <span class="value ">{{ $task->pickup->scheduled_time }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('complete before') }}</span>
                    <span class="value ">{{ $task->delivery->scheduled_time }}</span>
                </div>

            </div>
        </div>

        <div class="section">
            <div class="section-title">{{ __('Pickup Point') }}</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">{{ __('Name') }}</span>
                    <span class="value ">{{ $task->pickup->contact_name }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('phone number') }}</span>
                    <span class="value ">{{ $task->pickup?->contact_phone }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('email') }}</span>
                    <span class="value ">{{ $task->pickup->contact_emil }}</span>
                </div>

                <div class="info-item"><span class="label">{{ __('Address') }}</span><span
                        class="value">{{ $task->pickup?->address }}</span></div>
                @if ($task->pickup?->note)
                    <div class="info-item">
                        <span class="label">{{ __('Notes') }}</span>
                        <span class="value">{{ $task->pickup?->note }}</span>
                    </div>
                @endif
                @if ($task->pickup->image)
                    <div class="info-item">
                        <span class="label">{{ __('Reference Image') }}</span>
                        <span class="value ">
                            <img src="{{ asset($task->pickup?->image) }}" alt="" style="width: 50px">
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">{{ __('Delivery Point') }}</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">{{ __('Name') }}</span>
                    <span class="value ">{{ $task->delivery?->contact_name }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('phone number') }}</span>
                    <span class="value ">{{ $task->delivery?->contact_phone }}</span>
                </div>
                <div class="info-item">
                    <span class="label">{{ __('email') }}</span>
                    <span class="value ">{{ $task->delivery->contact_emil }}</span>
                </div>

                <div class="info-item"><span class="label">{{ __('Address') }}</span><span
                        class="value">{{ $task->delivery?->address }}</span></div>
                @if ($task->delivery?->note)
                    <div class="info-item">
                        <span class="label">{{ __('Notes') }}</span>
                        <span class="value">{{ $task->delivery?->note }}</span>
                    </div>
                @endif
                @if ($task->delivery?->image)
                    <div class="info-item">
                        <span class="label">{{ __('Reference Image') }}</span>
                        <span class="value ">
                            <img src="{{ asset($task->delivery?->image) }}" alt="" style="width: 50px">
                        </span>
                    </div>
                @endif
            </div>
        </div>



        <div class="section">
            <div class="section-title">{{ __('Driver Information') }}</div>
            <div class="info-grid">
                <div class="info-item"><span class="label">{{ __('Driver name') }}</span><span
                        class="value">{{ $task->driver?->name }}</span></div>
                <div class="info-item"><span class="label">{{ __('Phone number') }}</span><span
                        class="value">{{ $task->driver?->phone }}</span></div>

                @if ($task->driver?->additional_data && is_array($task->driver->driver_visible_additional_data))
                    @foreach ($task->driver->driver_visible_additional_data as $key => $field)
                        @if (isset($field['label'], $field['value'], $field['type']))
                            <div class="info-item">
                                <span class="label">{{ $field['label'] }}</span>
                                @switch($field['type'])
                                    @case('text')
                                    @case('string')

                                    @case('number')
                                        <span class="value">{{ $field['value'] }}</span>
                                    @break

                                    @case('image')
                                        <img src="{{ asset('storage/' . $field['value']) }}" alt="{{ $field['label'] }}"
                                            class="img-fluid rounded border"
                                            style="max-height: 200px; max-width: 100%; object-fit: cover;">
                                    @break

                                    @case('file')
                                        @php
                                            $ext = strtolower(pathinfo($field['value'], PATHINFO_EXTENSION));
                                            $icons = [
                                                'pdf' => 'ti ti-file-text',
                                                'doc' => 'ti ti-file-description',
                                                'docx' => 'ti ti-file-description',
                                                'xls' => 'ti ti-file-spreadsheet',
                                                'xlsx' => 'ti ti-file-spreadsheet',
                                                'ppt' => 'ti ti-presentation',
                                                'pptx' => 'ti ti-presentation',
                                            ];
                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                        @endphp
                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                            class="d-flex align-items-center text-decoration-none mt-1">
                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                        </a>
                                    @break

                                    @case('file_expiration_date')
                                        @php
                                            $ext = strtolower(pathinfo($field['value'], PATHINFO_EXTENSION));
                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                        @endphp
                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                            class="d-flex align-items-center text-decoration-none mt-1">
                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                        </a>
                                        <p class="mt-3">expiration date: {{ $field['expiration'] ?? '-' }}</p>
                                    @break

                                    @default
                                        <p class="mb-0">{{ $field['value'] }}</p>
                                @endswitch
                            </div>
                        @endif
                    @endforeach
                @endif


            </div>
        </div>

        <div class="section">
            <div class="section-title">{{ __('Custom Fields') }}</div>
            <div class="info-grid">
                @if ($task->additional_data)

                    @if (is_array($task->additional_data) && count($task->additional_data) > 0)

                        @foreach ($task->driver_visible_additional_data as $key => $field)
                            <div class="info-item">
                                <span class="label">{{ $field['label'] }}</span>
                                @switch($field['type'])
                                    @case('text')
                                    @case('string')

                                    @case('number')
                                        <span class="value">{{ $field['value'] }}</span>
                                    @break

                                    @case('image')
                                        <img src="{{ asset('storage/' . $field['value']) }}" alt="{{ $field['label'] }}"
                                            class="img-fluid rounded border" style="max-height: 200px; object-fit: cover;">
                                    @break

                                    @case('file')
                                        @php
                                            $ext = strtolower(pathinfo($field['value'], PATHINFO_EXTENSION));
                                            $icons = [
                                                'pdf' => 'ti ti-file-text',
                                                'doc' => 'ti ti-file-description',
                                                'docx' => 'ti ti-file-description',
                                                'xls' => 'ti ti-file-spreadsheet',
                                                'xlsx' => 'ti ti-file-spreadsheet',
                                                'ppt' => 'ti ti-presentation',
                                                'pptx' => 'ti ti-presentation',
                                            ];
                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                        @endphp
                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                            class="d-flex align-items-center text-decoration-none mt-1">
                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                        </a>
                                    @break

                                    @case('file_expiration_date')
                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                            class="d-flex align-items-center text-decoration-none mt-1">
                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                        </a>
                                        <p class="mt-3">expiration date: {{ $field['expiration'] }}</p>
                                    @break

                                    @default
                                        <p class="mb-0">{{ $field['value'] }}</p>
                                @endswitch
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info" role="alert">
                            {{ __('No additional data found for this customer.') }}
                        </div>
                    @endif

                @endif

            </div>
        </div>


        <footer>
            {{ __('task report generated at') }}: {{ now()->format('Y-m-d H:i') }}
        </footer>
    </main>

    <script>
        window.onload = () => window.print();
        window.onload = () => {
            window.print();
            window.onafterprint = () => {
                window.close();
            };
        };
    </script>

</body>

</html>
