<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title>#{{ $task->id }}</title>
    <style>
        body {
            margin: 0;
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

        table {
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

        img {
            max-width: 100%;
            border-radius: 4px;
            object-fit: cover;
        }
    </style>
</head>

<body>

    <header>
        <table class="header-table">
            <tr>
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
                            alt="Logo" height="40">
                    </div>
                </td>
            </tr>
        </table>
    </header>

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
                        {{ $task->vehicle_size?->type?->vehicle->name ?? '-' }}
                        ({{ $task->vehicle_size?->type->name ?? '-' }})
                        ({{ $task->vehicle_size?->name ?? '-' }})
                    </div>
                </td>
                <td>
                    <div class="label">{{ __('Start before') }}</div>
                    <div class="value">{{ $task->pickup?->scheduled_time ?? '-' }}</div>
                </td>
                <td>
                    <div class="label">{{ __('complete before') }}</div>
                    <div class="value">{{ $task->delivery?->scheduled_time ?? '-' }}</div>
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
                    <div class="value">{{ $task->pickup?->contact_name ?? '-' }}</div>
                </td>
                <td>
                    <div class="label">{{ __('phone number') }}</div>
                    <div class="value">{{ $task->pickup?->contact_phone ?? '-' }}</div>
                </td>
                <td>
                    <div class="label">{{ __('email') }}</div>
                    <div class="value">{{ $task->pickup?->contact_emil ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="label">{{ __('Address') }}</div>
                    <div class="value">{{ $task->pickup?->address ?? '-' }}</div>
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
                    <div class="value">{{ $task->delivery?->contact_name ?? '-' }}</div>
                </td>
                <td>
                    <div class="label">{{ __('phone number') }}</div>
                    <div class="value">{{ $task->delivery?->contact_phone ?? '-' }}</div>
                </td>
                <td>
                    <div class="label">{{ __('email') }}</div>
                    <div class="value">{{ $task->delivery?->contact_emil ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="label">{{ __('Address') }}</div>
                    <div class="value">{{ $task->delivery?->address ?? '-' }}</div>
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
                    <div class="value">{{ $task->driver?->name ?? '-' }}</div>
                </td>
                <td>
                    <div class="label">{{ __('Phone number') }}</div>
                    <div class="value">{{ $task->driver?->phone ?? '-' }}</div>
                </td>
                <td>
                    <div class="label">{{ __('Team') }}</div>
                    <div class="value">{{ $task->driver?->team?->name ?? '-' }}</div>
                </td>
            </tr>
        </table>

        {{-- Driver Additional Data --}}
        @if (!empty($task->driver->driver_visible_additional_data))
            <table class="info-table">
                @php $counter = 0; @endphp
                <tr>
                    @foreach ($task->driver->driver_visible_additional_data as $field)
                        @if (isset($field['label'], $field['value'], $field['type']))
                            <td>
                                <div class="label">{{ $field['label'] }}</div>
                                <div class="value">
                                    @switch($field['type'])
                                        @case('image')
                                            @php
                                                $path = storage_path('app/public/' . $field['value']);

                                                if (file_exists($path)) {
                                                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                                                    // تحويل WebP إلى PNG مؤقتًا
                                                    if ($ext === 'webp') {
                                                        $image = imagecreatefromwebp($path);
                                                        ob_start();
                                                        imagepng($image);
                                                        $data = ob_get_clean();
                                                        imagedestroy($image);
                                                        $type = 'png';
                                                        $base64 = base64_encode($data);
                                                    } else {
                                                        $type = $ext;
                                                        $base64 = base64_encode(file_get_contents($path));
                                                    }

                                                    $src = 'data:image/' . $type . ';base64,' . $base64;
                                                } else {
                                                    $src = null;
                                                }
                                            @endphp

                                            @if ($src)
                                                <img src="{{ $src }}" alt="{{ $field['label'] }}"
                                                    style="max-width: 100%; max-height: 150px; border-radius: 4px; object-fit: cover;">
                                            @else
                                                <p style="color:red;">❌ لم يتم العثور على الصورة</p>
                                            @endif
                                        @break

                                        @case('file')
                                            <a href="{{ public_path('storage/' . $field['value']) }}"
                                                target="_blank">{{ basename($field['value']) }}</a>
                                        @break

                                        @case('file_expiration_date')
                                            <a href="{{ public_path('storage/' . $field['value']) }}"
                                                target="_blank">{{ basename($field['value']) }}</a>
                                            <div>{{ __('expiration date') }}: {{ $field['expiration'] ?? '-' }}</div>
                                        @break

                                        @default
                                            {{ $field['value'] }}
                                    @endswitch
                                </div>
                            </td>
                            @php
                                $counter++;
                                if ($counter % 3 == 0) {
                                    echo '</tr><tr>';
                                }
                            @endphp
                        @endif
                    @endforeach

                    @if ($counter % 3 != 0)
                        @for ($i = 0; $i < 3 - ($counter % 3); $i++)
                            <td></td>
                        @endfor
                </tr>
        @endif
        </table>
        @endif
    </div>

    {{-- Custom Fields --}}
    <div class="section">
        <div class="section-title">{{ __('Custom Fields') }}</div>
        <table class="info-table">
            @if (!empty($task->additional_data))
                @php $counter = 0; @endphp
                <tr>
                    @foreach ($task->additional_data as $field)
                        @if (isset($field['label'], $field['value'], $field['type']))
                            <td>
                                <div class="label">{{ $field['label'] }}</div>
                                <div class="value">
                                    @switch($field['type'])
                                        @case('image')
                                            @php
                                                $path = storage_path('app/public/' . $field['value']);

                                                if (file_exists($path)) {
                                                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                                                    // تحويل WebP إلى PNG مؤقتًا
                                                    if ($ext === 'webp') {
                                                        $image = imagecreatefromwebp($path);
                                                        ob_start();
                                                        imagepng($image);
                                                        $data = ob_get_clean();
                                                        imagedestroy($image);
                                                        $type = 'png';
                                                        $base64 = base64_encode($data);
                                                    } else {
                                                        $type = $ext;
                                                        $base64 = base64_encode(file_get_contents($path));
                                                    }

                                                    $src = 'data:image/' . $type . ';base64,' . $base64;
                                                } else {
                                                    $src = null;
                                                }
                                            @endphp

                                            @if ($src)
                                                <img src="{{ $src }}" alt="{{ $field['label'] }}"
                                                    style="max-width: 100%; max-height: 150px; border-radius: 4px; object-fit: cover;">
                                            @else
                                                <p style="color:red;">❌ لم يتم العثور على الصورة</p>
                                            @endif
                                        @break

                                        @case('file')
                                            <a href="{{ public_path('storage/' . $field['value']) }}"
                                                target="_blank">{{ basename($field['value']) }}</a>
                                        @break

                                        @case('file_expiration_date')
                                            <a href="{{ public_path('storage/' . $field['value']) }}"
                                                target="_blank">{{ basename($field['value']) }}</a>
                                            <div>{{ __('expiration date') }}: {{ $field['expiration'] ?? '-' }}</div>
                                        @break

                                        @default
                                            {{ $field['value'] }}
                                    @endswitch
                                </div>
                            </td>
                            @php
                                $counter++;
                                if ($counter % 3 == 0) {
                                    echo '</tr><tr>';
                                }
                            @endphp
                        @endif
                    @endforeach

                    @if ($counter % 3 != 0)
                        @for ($i = 0; $i < 3 - ($counter % 3); $i++)
                            <td></td>
                        @endfor
                </tr>
            @endif
        @else
            <tr>
                <td colspan="3">{{ __('No additional data found.') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="conditions">
        <div class="section-title">{{ __('Conditions & Roles') }}</div>

        <div>
            <ul>
                <li>يحدد عقد النقل نوع وطبيعة البضاعة المتعاقد على نقلها، والحجم أو الوزن أو العدد أو الكمية حسب نوع
                    البضاعة، وبيانات أطراف العقد، وأجور النقل، وطريقة الدفع، ومكان وتاريخ ووقت انتقال مسؤولية
                    البضاعة للناقل، ومكان وفترة تسليمها للمرسل إليه، كما يحدد آلية الاستلام والتسليم بما فيها عمليات
                    التحميل والتفريغ والمناولة والتصفيف والتخزين، ومتطلبات واشتراطات عملية النقل الخاصة بنوع وطبيعة
                    البضاعة، ويجوز باتفاق طرفي عقد النقل استخدام التعاملات الإلكترونية في كل ما يخص معاملات عقد
                    النقل طبقاً للأنظمة واللوائح والتعليمات المعمول بها في المملكة.</li>
                <li>يجوز اتفاق أطراف العقد على شروط إضافية في عقد النقل بما لا يخالف أحكام هذه اللائحة والأنظمة
                    واللوائح والتعليمات ذات العلاقة.</li>
                <li>يجوز للناقل –تحت مسئوليته وإشرافه– إسناد جزء أو كل من المهام الموكلة له لتنفيذ بنود عقد النقل،
                    ما لم يتفق على خلاف ذلك في العقد، ويكون الناقل مسؤولاً مسؤولية مباشرة عن كل تصرفات وأفعال تابعيه
                    في تنفيذ الالتزامات المترتبة على عقد النقل، ويقع باطلاً كل شرط يقضي بإعفاء الناقل من المسؤولية
                    عن تصرفات وأفعال تابعيه.</li>
                <li>الناقل مسؤول عن البضاعة من وقت استلامه لها أو قيام الطرف المكلف من قبله بتنفيذ أي من المهام
                    الموكلة إليه، وتنتهي مسؤوليته عند تسليمه للبضاعة للمرسل إليه أو المفوض باستلامها في مقصدها.</li>
                <li>لا يتحمل الناقل المسؤولية عن الأضرار الناجمة عن تحميل البضائع أو تفريغها من الشاحنة أو عليها،
                    إلا في حالة أن يكون التحميل والتفريغ قد تم من قبل الناقل بطلب من المرسل أو المرسل إليه.</li>
                <li>للناقل إذا اقتضت الضرورة للمحافظة على البضاعة أن يقوم عند استلامها بإعادة التحزيم أو إصلاح
                    الأغلفة أو زيادتها أو تخفيضها أو غير ذلك من التدابير الضرورية التي يقتضي القيام بها بمقابل أو
                    بدون مقابل حسب الاتفاق مع المرسل أو من ينوب عنه.</li>
                <li>يكون الناقل مسؤولاً عن الخسارة الناتجة عن تلف أو فقد البضاعة وكذلك عن التأخير في التسليم إذا وقع
                    الحادث الذي سبب التلف أو الفقد أو التأخير في التسليم في الوقت الذي كانت فيه البضاعة في عهدته إلا
                    إذا أثبت عدم صدور أي خطأ أو إهمال عنه أو عن أي من موظفيه أو وكلائه تسبب أو ساهم في تأخير تسليم
                    البضاعة أو خسارتها أو تلفها، كما يمكن إعفاؤه من المسؤولية إذا أثبت أن تأخير تسليم البضاعة أو
                    خسارتها أو تلفها يعود إلى أحد الأسباب التالية:</li>
                <ul>
                    <li>خطأ صادر عن المرسل أو المرسل إليه أو أي من وكلائهما أو ممثليهما.</li>
                    <li>قوة قاهرة.</li>
                    <li>عيب كامن أو خفي في البضاعة.</li>
                    <li>حدوث نقص في الحجم أو الوزن أثناء النقل لأسباب تعود إلى طبيعة البضاعة المنقولة مثل التبخر أو
                        الجفاف أو النضوج.</li>
                    <li>سبب آخر يكون خارج سيطرة الناقل ويمنعه من تنفيذ بنود عقد النقل.</li>
                </ul>
                <li>يكون الناقل مسؤولاً عن التلف أو الخسارة الناجمة عن تأخير تسليم البضاعة في الموعد المحدد إذا كان
                    المرسل قد أعلن كتابةً عن رغبته في تسليم البضاعة في هذا الموعد المحدد ووافق عليه الناقل.</li>
                <li>في حال عدم وجود اتفاق مسبق بشأن موعد تسليم البضاعة يكون الناقل مسؤولاً عن التأخير في التسليم إذا
                    لم يجر تسليمها خلال فترة زمنية تعتبر مناسبة بعد أن تؤخذ في الاعتبار الظروف التي قد تؤدي إلى هذا
                    التأخير.</li>
                <li>تعامل البضاعة كأنها مفقودة ويتحمل الناقل مسؤولية فقدها في الحالات التالية:</li>
                <ul>
                    <li>إذا لم تصل البضاعة خلال (30) ثلاثون يوماً بعد تاريخ التسليم المتفق عليه.</li>
                    <li>بعد انقضاء (60) ستين يوماً من تسلم الناقل للبضاعة؛ إذا لم يحدد موعد للتسليم.</li>
                </ul>
                <li>لا يكون الناقل مسؤولاً عن الخسارة الناجمة عن التأخير في تسليم البضاعة أو تلفها أو فقدها إذا كان
                    ذلك قد نتج عن تقديم المرسل بيانات أو معلومات خاطئة عن طبيعة البضاعة في عقد النقل أو وثيقة النقل.
                </li>
                <li>لا يكون الناقل مسؤولاً عما يلحق بالبضاعة بحكم طبيعتها من نقص في الوزن أو الحجم أثناء النقل، على
                    ألا يزيد هذا النقص عن النسبة المقررة طبقاً للقواعد العامة المعتمدة في نقل مثل هذه البضاعة. وإذا
                    شملت وثيقة النقل بضاعة مختلفة مقسمة إلى مجموعات أو طرود وكان وزن كل منها مبيناً في الوثيقة فيحدد
                    النقص المسموح به على أساس وزن كل مجموعة أو طرد كلاً على حدة.</li>
                <li>لا يتحمل الناقل النقص الذي يظهر في البضاعة المنقولة في حاوية أو ما شابهها المجهزة من قبل المرسل
                    والمختومة بختمه إذا سلمها الناقل إلى المرسل إليه بختمها السليم وفي الوقت المحدد للتسليم.</li>
                <li>يلتزم الناقل باستيفاء المبالغ التي أُشترط بموجب شروط عقد النقل استيفاؤها من المرسل إليه لحساب
                    المرسل عند التسليم، وإذا تم تسليم البضاعة دون استيفاء تلك المبالغ فيلزم الناقل بدفع تلك المبالغ
                    إلى المرسل دون الإخلال بحقه في الرجوع على المرسل إليه.</li>
                <li>يكون الناقل مسؤولاً عن فقدان الوثائق المرفقة بوثيقة النقل أو الواردة فيها أو المودعة لديه، أو
                    على استعمالها بصورة غير صحيحة بشرط ألا يزيد التعويض الواجب الدفع على اعتبار أن البضاعة مفقودة.
                </li>
            </ul>

        </div>

        @if ($task->conditions)
            <h4>{{ __('Customers Conditions') }}</h4>
            <li>{{ $task->conditions }}</li>
        @endif

    </div>

    <footer>
        {{ __('task report generated at') }}: {{ now()->format('Y-m-d H:i') }}
    </footer>

</body>

</html>
