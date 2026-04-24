<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>بوليصة شحن #{{ $task->id }}</title>
    <style>
        body {
            font-family: 'tajawal', sans-serif;
            margin: 0;
            padding: 0;
            color: #2c3e50;
            font-size: 11px; /* Reduced from 12px */
            direction: rtl;
            text-align: right;
            line-height: 1.2; /* Added line-height */
        }

        /* mPDF Specific Page Settings */
        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 15px;
            margin-bottom: 15px;
            margin-left: 15px;
            margin-right: 15px;
            background-image-resize: 6; /* Equivalent to object-fit: contain */
            background-image-opacity: 0.1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        td {
            vertical-align: top;
        }

        /* Helper Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .ltr-content {
            direction: ltr;
            display: inline-block;
            unicode-bidi: embed;
        }

        /* ==================== HEADER SECTION ==================== */
        .header-table {
            width: 100%;
            margin-bottom: 10px;
            border: none;
        }

        .header-table td {
            vertical-align: middle;
        }

        .customer-name {
            font-size: 18px; /* Reduced from 22px */
            font-weight: bold;
            color: #1a2733;
        }

        .department-name {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
        }

        .customer-logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        /* ==================== DATE & TITLE SECTION ==================== */
        .date-title-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .date-label {
            font-weight: bold;
            color: #2c3e50;
        }

        .date-value {
            color: #555;
        }

        .title-box {
            text-align: center;
            padding: 5px; /* Reduced from 10px */
            border: 2px solid #1a2733;
            border-radius: 6px;
            display: block;
            width: 60%;
            margin: 0 auto;
        }

        .document-title-ar {
            font-size: 20px;
            font-weight: bold;
            color: #1a2733;
        }

        .document-title-en {
            font-size: 14px;
            color: #555;
            margin-top: 3px;
        }

        .task-number {
            font-size: 18px;
            font-weight: bold;
            color: #8B0000;
            padding-top: 15px; /* Alignment adjustment */
        }

        /* ==================== MESSRS SECTION ==================== */
        .messrs-table {
             width: 100%;
             margin-bottom: 10px;
        }

        .messrs-dots {
            color: #ccc;
            letter-spacing: 1px;
            text-align: center;
        }

        /* ==================== INFO SECTION (Two Columns) ==================== */
        .info-main-table {
            width: 100%;
            border: 2px solid #1a2733;
            margin-bottom: 5px; /* Reduced from 10px */
        }

        /* The vertical separator line */
        .info-col-right {
            width: 50%;
            border-left: 1px solid #e1e1e1;
            padding: 10px;
        }

        .info-col-left {
            width: 50%;
            padding: 10px;
        }

        /* Inner tables for rows */
        .inner-row-table {
            width: 100%;
            border-bottom: 1px dotted #e1e1e1;
            margin-bottom: 5px;
        }

        /* Last row needs no border, handled by logic or CSS pseudo-class if supported,
           but CSS last-child support in mPDF is limited. We'll use a class if needed or just accept it. */

        .info-label-ar {
            width: 25%;
            font-size: 11px;
            font-weight: bold;
            color: #2c3e50;
            text-align: right;
            padding-bottom: 5px;
        }

        .info-value {
            width: 50%;
            font-size: 11px;
            font-weight: bold;
            color: #333;
            text-align: center;
            padding-bottom: 5px;
        }

        .info-label-en {
            width: 25%;
            font-size: 10px;
            font-style: italic;
            color: #888;
            text-align: left;
            direction: ltr; /* Ensure english label direction */
            padding-bottom: 5px;
        }

        /* ==================== LOCATIONS & TABLE ==================== */
        .section-box {
            width: 100%;
            margin-bottom: 5px; /* Reduced from 10px */
            padding: 2px 5px; /* Reduced padding */
        }

        .section-label {
            font-weight: bold;
            color: #1a2733;
            display: block;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }

        .maps-link {
            color: #004085;
            text-decoration: none;
            font-size: 10px;
            margin-right: 10px; /* actually margin-left due to rtl */
        }

        .destination-table-inner {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .destination-table-inner th, .destination-table-inner td {
            border: 1px solid #1a2733;
            padding: 2px 4px;
            text-align: center;
            font-size: 10px;
        }

        .destination-table-inner th {
            background-color: #f8f9fa;
        }

        /* ==================== SIGNATURES ==================== */
        .sig-table {
            width: 100%;
            margin-bottom: 15px;
        }

        .sig-box {
            border: 1px solid #1a2733;
            padding: 10px;
            width: 48%; /* Slightly less than 50 to allow spacing if not using border-spacing */
            /* In a single row table, we just use cells with padding */
        }

        .sig-spacer {
            width: 4%;
        }

        .sig-line {
            margin-top: 20px;
            border-bottom: 1px dotted #000;
            width: 80%;
            display: inline-block;
        }

        /* ==================== NOTES & DECLARATION ==================== */
        .important-note {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 4px; /* Reduced from 8px */
            border-radius: 6px;
            margin-bottom: 5px; /* Reduced from 10px */
            font-weight: bold;
            text-align: center;
            font-size: 10px; /* Added explicit small size */
        }

        .declaration-table {
            width: 100%;
            border: 1px solid #1a2733;
            background-color: #fcfcfc;
            margin-bottom: 5px; /* Reduced from 15px */
        }

        .decl-cell {
            width: 50%;
            padding: 5px; /* Reduced from 8px */
            line-height: 1.2; /* Reduced from 1.4 */
            vertical-align: top;
            font-size: 10px; /* Standardized to 10px */
        }

        .decl-ar {
            text-align: justify;
            border-left: 1px solid #eee;
        }

        .decl-en {
            text-align: justify;
            font-size: 9px; /* Reduced from 10px */
            color: #555;
            font-style: italic;
            direction: ltr;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #e1e1e1;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    @php
        // Same PHP Logic
        $customerLogo = null;
        if ($task->customer && $task->customer->image) {
            $cleanPath = preg_replace('/^storage\//', '', $task->customer->image);
            $possiblePaths = [
                public_path('storage/' . $cleanPath),
                storage_path('app/public/' . $cleanPath),
                storage_path('app/' . $cleanPath),
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($path) && is_file($path)) {
                    $customerLogo = $path;
                    break;
                }
            }
        }

        $gregorianDate = $task->created_at ? $task->created_at->format('Y-m-d') : now()->format('Y-m-d');
        $gregorianFormatted = $task->created_at ? $task->created_at->format('Y/m/d') : now()->format('Y/m/d');

        $hijriDate = '';
        try {
            if (class_exists('IntlDateFormatter')) {
                $formatter = new IntlDateFormatter(
                    'ar_SA@calendar=islamic',
                    IntlDateFormatter::LONG,
                    IntlDateFormatter::NONE,
                    'Asia/Riyadh',
                    IntlDateFormatter::TRADITIONAL
                );
                $hijriDate = $formatter->format(strtotime($gregorianDate));
            }
        } catch (\Exception $e) {
            $hijriDate = '-';
        }

          $driverName = $task->driver?->name ?? '';
        $driverPhone = $task->driver?->phone_code . $task->driver?->phone ?? '';
        $driverIdNumber = $task->driver?->identity_number ?? '';
        $vehicleNumber = $task->driver?->vehicle_number ?? '';
        $vehicleType = $task->vehicle_size?->type?->vehicle?->name . $task->vehicle_size?->type?->name . ' ' . $task->vehicle_size?->name ?? '';
        $platnumber =( $task->driver?->additional_data['right_letter']['value'] ?? '') .' '. ($task->driver?->additional_data['middle_letter']['value'] ?? '') .' '. ($task->driver?->additional_data['left_letter']['value'] ?? '') . ' '. ($task->driver?->additional_data['vehicle_number']['value'] ?? '') ;
        $carowner = $task->driver?->additional_data['car_car_owner']['value'] ?? ' ';
        $license_number = $task->driver?->additional_data['license_number']['value'] ?? ' ';
        $from_issued = $task->driver?->additional_data['from_issued']['value'] ?? ' ';
        $ld_number = $task->driver?->additional_data['ld_number']['value'] ?? ' ';
        $ld_date = $task->driver?->additional_data['ld_date']['value'] ?? ' ';
        $ld_from = $task->driver?->additional_data['ld_from']['value'] ?? ' ';

        // Internal Signatures Logic
        $internalSignaturesEnabled = \App\Models\Settings::getValue('internal_signatures_enabled', '0') == '1';

        $driverSignaturePath = null;
        if ($internalSignaturesEnabled && $task->driver && $task->driver->signature_image) {
            $cleanPath = preg_replace('/^storage\//', '', $task->driver->signature_image);
            $possiblePaths = [
                public_path('storage/' . $cleanPath),
                storage_path('app/public/' . $cleanPath),
                storage_path('app/' . $cleanPath),
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($path) && is_file($path)) {
                    $driverSignaturePath = $path;
                    break;
                }
            }
        }

        $customerSignaturePath = null;
        if ($internalSignaturesEnabled && $task->customer && $task->customer->signature_image) {
            $cleanPath = preg_replace('/^storage\//', '', $task->customer->signature_image);
            $possiblePaths = [
                public_path('storage/' . $cleanPath),
                storage_path('app/public/' . $cleanPath),
                storage_path('app/' . $cleanPath),
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($path) && is_file($path)) {
                    $customerSignaturePath = $path;
                    break;
                }
            }
        }
    @endphp



    <!-- HEADER TABLE -->
    <table class="header-table">
        <tr>
            <td width="60%" class="text-right">
                <div class="customer-name">
                    <p>شركة آفاق العمل</p>
                    <p>Job Horizons Co.</p>
                </div>
                <div class="department-name">س.ت: 1010224368 - 1010224368 </div>
                <div class="department-name">قسم النقل</div>
            </td>
            <td width="40%" class="text-left">
                <div class="customer-logo">
                    @if ($customerLogo)
                        <img src="{{ public_path('assets/img/afak.png') }}" alt="شعار العميل" style="max-height: 80px;  width: auto; height: auto;">
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- DATE & TITLE TABLE -->
    <table class="date-title-table">
        <tr>
            <!-- Date Section (Right) -->
            <td width="30%" class="text-right">
                <div style="margin-bottom: 5px;">
                    <span class="date-label">التأريخ :</span>
                    <span class="date-value">{{ $hijriDate ?: '-' }}</span>
                </div>
                <div>
                    <span class="date-label">الموافق :</span>
                    <span class="date-value ltr-content">{{ $gregorianFormatted }}</span>
                </div>
            </td>

            <!-- Title Section (Center) -->
            <td width="40%" class="text-center">
                <table class="title-box-table" style="margin: 0 auto; width: 60%; border: 2px solid #1a2733; border-radius: 6px; border-collapse: separate;">
                    <tr>
                        <td style="text-align: center; padding: 10px; border: none;">
                            <div class="document-title-ar">بوليصة شحن</div>
                            <div class="document-title-en">Loading Order</div>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Task Number (Left) -->
            <td width="30%" class="text-left"> <!-- Changed to left as per orig floats or center? orig was center, html css float left. Let's keep it left alignment within the cell but the cell is on left -->
                 <div class="text-center"> <!-- Actually orig float left means it's on the left SIDE of the page. wait.
                 RTL: float:right is start, float:left is end.
                 CSS said: .task-number-section { float: left; width: 25%; text-align: center; }
                 So it should be on the left side of page.
                 Table order in RTL: Cell 1 is Right. Cell 2 Center. Cell 3 Left.
                 -->
                 <div class="task-number ltr-content">#{{ $task->id }}</div>
                 </div>
            </td>
        </tr>
    </table>

    <!-- MESSRS SECTION -->
    <table class="messrs-table" style="margin-top:20px; margin-bottom:20px">
        <tr>
            <td width="15%" class="text-right bold">المكرم السادة :</td>
            <td width="70%" class="messrs-dots">....................................................................................................................</td>
            <td width="15%" class="text-left bold" dir="ltr">Messrs: </td>
        </tr>
    </table>

    <!-- TWO COLUMN INFO SECTION -->
    <table class="info-main-table">
        <tr>
            <!-- RIGHT COLUMN (Driver Info) -->
            <td class="info-col-right">
                <!-- Inner Tables for each row to ensure perfect alignment -->
                @foreach([
                    ['label_ar' => 'اسم السائق:', 'val' => $driverName, 'label_en' => 'Driver Name:'],
                    ['label_ar' => 'رقم السيارة:', 'val' => $platnumber, 'label_en' => 'Car No:'],
                    ['label_ar' => 'رقم رخصة التشغيل:', 'val' => $license_number, 'label_en' => 'Work Lic. No: '],
                    ['label_ar' => 'مالك السيارة:', 'val' => $carowner, 'label_en' => 'T.Owner Name:'],
                    ['label_ar' => 'جهة صدوره:', 'val' => $from_issued, 'label_en' => 'from Issued:'],
                ] as $row)
                <table class="inner-row-table">
                    <tr>
                        <td class="info-label-ar">{{ $row['label_ar'] }}</td>
                        <td class="info-value"><span class="ltr-content">{{ $row['val'] ?: '...............................' }}</span></td>
                        <td class="info-label-en">{{ $row['label_en'] }}</td>
                    </tr>
                </table>
                @endforeach
            </td>

            <!-- LEFT COLUMN (Delivery Info) -->
            <td class="info-col-left">
                @foreach([
                    ['label_ar' => 'رقم الحفيظة الاقامة:', 'val' => $ld_number, 'label_en' => 'L.D. No:'],
                    ['label_ar' => 'تاريخها:', 'val' => $ld_date, 'label_en' => 'Date:'],
                    ['label_ar' => 'مصدرها:', 'val' => $ld_from, 'label_en' => 'Issued At:'],
                    ['label_ar' => 'نوع السيارة:', 'val' => $vehicleType, 'label_en' => 'Kind Of Car:'],
                    ['label_ar' => 'تلفون السائق:', 'val' => $driverPhone, 'label_en' => 'Driver Tel:'],
                ] as $row)
                <table class="inner-row-table">
                    <tr>
                        <td class="info-label-ar">{{ $row['label_ar'] }}</td>
                        <td class="info-value"><span class="ltr-content">{{ $row['val'] ?: '...............................' }}</span></td>
                        <td class="info-label-en">{{ $row['label_en'] }}</td>
                    </tr>
                </table>
                @endforeach
            </td>
        </tr>
    </table>

    <!-- LOADING LOCATION -->
    <div class="loading-section" style=" padding: 10px; border-radius: 6px; margin-bottom: 15px;">
        <div class="section-label">موقع التحميل / :
        <span style="font-size: 13px;">
            {{ $task->pickup?->address ?? '............................................................' }}
            @if ($task->pickup?->latitude && $task->pickup?->longitude)
                <span dir="ltr">
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $task->pickup->latitude }},{{ $task->pickup->longitude }}" class="maps-link" style="margin-left: 10px;">
                        [ رابط الخريطة / Maps Link ]
                    </a>
                </span>
            @endif
                </span>
                </div>
    </div>

    <!-- DESTINATION & UNLOADING -->
<div class="section-box">
        <div class="section-label">منطقة التنزيل :</div>


        <table class="destination-table-inner">
            <thead>
                <tr>
                    <th width="45%">المنطقة / Area</th>
                    <th width="15%">وقت الوصول / Arrival</th>
                    <th width="15%">وقت الخروج / Departure</th>
                    <th width="20%">الختم / Stamp</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($task->delivery))
                     <tr>
                        <td>
                            {{ $task->delivery->address }}

                            <a href="https://www.google.com/maps/search/?api=1&query={{ $task->delivery->latitude }},{{ $task->delivery->longitude }}"
                            target="_blank"
                            class="maps-link"
                            style="margin-left: 10px; color: #1a73e8; text-decoration: none; font-weight: bold;">
                                [ رابط الخريطة / Maps Link ]
                            </a>
                        </td>

                        <td>{{ $task->delivery->arrival_time ?? '--:--' }}</td>
                        <td>{{ $task->delivery->departure_time ?? '--:--' }}</td>

                        <td>
                            <div style="height: 0; overflow: visible;">
                                <img src="{{ public_path('khatm/afaq.png') }}" style="max-height: 100px; margin-top: -30px; vertical-align: middle;" alt="">
                            </div>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td>{{ $task->delivery?->address }}</td>
                        <td></td>
                        <td></td>
                        <td>
                             <div style="height: 0; overflow: visible;">
                                <img src="{{ public_path('khatm/afaq.png') }}" style="max-height: 90px; margin-top: -50px; vertical-align: middle;" alt="">
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>


            <table>
              <tr>
                <td>
                <div style="margin-top: 10px;">المستلم: {{$task->delivery->contact_name}}</div>

                </td>
                <td>
                <div>
                    @if ($customerSignaturePath)
                        <div style="height: 0; overflow: visible;">
                            <img src="{{ $customerSignaturePath }}" style="max-height: 60px; margin-top: -5px; margin-left: -30px; vertical-align: middle;">
                        </div>
                    @endif
                    <span style="font-weight: bold; position: relative;">التوقيع:</span>
                    @if (!$customerSignaturePath)
                        <span class="sig-line"></span>
                    @endif
                </div>

                </td>
              </tr>
            </table>
            <table style="margin-top:20px; margin-bottom:20px">
              <tr>
                <td>
                <div style="margin-top: 10px;">إعداد: .......................................</div>

                </td>
                <td>
                <div>التوقيع: <span class="sig-line"></span></div>

                </td>
              </tr>
            </table>


    <!-- IMPORTANT NOTE -->
    <div class="important-note">
        ملاحظة هامة : صاحب البضاعة ملزم بدفع غرامة زيادة الوزن او غرامة مخالفة الإرتفاع المسموح به
    </div>

    <!-- DECLARATION -->
    <table class="declaration-table">
        <tr>
            <td class="decl-cell decl-ar">
                <strong>تنبيه:</strong> اقر انا السائق بأنني تسلمت البضاعة الموضحة بهذا الكشف أعلاه او الكشف المرفق من قبل الشركة. اتحمل مسؤولية اي نقص او تلف او سرقة في البضاعة حتى وصولها. كما اتعهد بوضع الشراع قبل الخروج من مستودع المغادرة. وسوف اخبر المكتب وصاحب البضاعة في حال تعطل السيارة واحصر سند استلام مختوم من عند العميل عند استلام البضاعة. وليس على المكتب اي مسؤولية فيما يتعلق بالبضاعة الحملة بعد مرور 15 يوم من تأريخ التحميل.
            </td>
            <td class="decl-cell decl-en">
                <strong>Attention:</strong> I, the driver, acknowledge that I have received the goods described in this list above or in the attached list from the company. I bear responsibility for any shortage, damage, or theft of the goods until their arrival. I also pledge to place the cover (sail) before leaving the departure warehouse. I will notify the office and the owner of the goods in case of vehicle breakdown and obtain a stamped receipt from the customer upon delivery. The office bears no responsibility regarding the loaded goods after 15 days from the date of loading.
            </td>
        </tr>
    </table>

    <!-- FINAL SIGNATURE -->
    <table style="width: 100%; border: none; margin: 0; border-collapse: collapse;">
        <tr>
            <td width="70%">&nbsp;</td>
            <td width="30%" class="text-right" style="padding: 0;">
                <div style="position: relative;">
                    @if ($driverSignaturePath)
                        <div style="height: 0; overflow: visible; text-align: right;">
                            <img src="{{ $driverSignaturePath }}" style="max-height: 60px; margin-top: -5px;">
                        </div>
                    @endif
                    <span style="font-weight: bold; position: relative;">توقيع السائق :</span>
                    @if (!$driverSignaturePath)
                        <div style="border-bottom: 2px solid #000; width: 100%; margin-top: 8px;"></div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- DRIVER DOCUMENTS -->
    @if ($task->driver && !empty((array) $task->driver->driver_visible_additional_data))
        @php
            $driverDocs = array_filter((array) $task->driver->driver_visible_additional_data, function($item) {
                $val = $item['value'] ?? '';
                $type = $item['type'] ?? '';
                return !empty($val) && (
                    in_array($type, ['file', 'image', 'file_expiration_date']) ||
                    preg_match('/\.(pdf|doc|docx|xls|xlsx|zip|rar|txt|jpg|jpeg|png|gif|webp)$/i', $val)
                );
            });
        @endphp
        @if (!empty($driverDocs))
            <div style="margin-top: 15px; padding: 10px; ">
                <div style="font-weight: bold; color: #1a2733;  padding-bottom: 5px; margin-bottom: 10px; font-size: 13px;">
                    وثائق السائق
                </div>
                <table style="width: 100%;">
                    @foreach(array_chunk($driverDocs, 3, true) as $chunk)
                        <tr>
                            @foreach($chunk as $item)
                                <td style="padding: 3px; font-size: 10px;">
                                    @php
                                        $fileUrl = ltrim($item['value'], '/');
                                        if (!str_starts_with($fileUrl, 'storage/') && !str_starts_with($fileUrl, 'http')) {
                                            $fileUrl = 'storage/' . $fileUrl;
                                        }
                                    @endphp
                                    <strong>{{ $item['label'] }}:</strong>
                                    <a href="{{ url($fileUrl) }}" target="_blank" style="color: #004085; text-decoration: underline;">
                                        تحميل / Download
                                    </a>
                                </td>
                            @endforeach
                            @for ($i = count($chunk); $i < 3; $i++)
                                <td></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    @endif

    <!-- CONTACT INFO -->
     <div style="width: 100%; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 11px; line-height: 1.8;">
        <div style="margin-bottom: 5px;">
            جوال: <span class="ltr-content">0551498217 - 0502187991</span> - الرمز البريدي: 14264 - الرمز الاضافي : 3943 - عضوية الرقم : 171899 - وحدة رقم : 312
        </div>
        <div dir="ltr" style="margin-bottom: 5px; font-family: sans-serif;">
            mob: <span class="ltr-content">0551498217 - 0502187991</span> - add code : 14264 - add code : 3943 - c.c.no.: 171899 - unit no.: 312
        </div>
        <div dir="ltr" style="font-family: sans-serif; font-weight: bold; color: #1a2733;">
            afaqal3ml@afaqal3ml.com.se
        </div>
    </div>


    <!-- DRIVER DOCUMENTS IMAGES PAGE -->
    @if ($task->driver && !empty((array) $task->driver->driver_visible_additional_data))
        @php
            $imageDocs = array_filter((array) $task->driver->driver_visible_additional_data, function($item) {
                $val = $item['value'] ?? '';
                // Check if it's an image extension
                return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $val);
            });
        @endphp

        @if (!empty($imageDocs))
            <div style="page-break-before: always;"></div>
            <div style="padding: 20px;">
                <h2 style="text-align: center; color: #1a2733; border-bottom: 2px solid #1a2733; padding-bottom: 10px; font-size: 18px;">
                    وثائق السائق (صور) / Driver Documents (Images)
                </h2>
                <div style="margin-top: 20px;">
                    @foreach($imageDocs as $item)
                        @php
                            $fileUrl = ltrim($item['value'], '/');
                            if (!str_starts_with($fileUrl, 'storage/') && !str_starts_with($fileUrl, 'http')) {
                                $fileUrl = 'storage/' . $fileUrl;
                            }
                        @endphp
                        <div style="margin-bottom: 30px; text-align: center; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                            <h3 style="font-size: 14px; margin-bottom: 10px; color: #333;">{{ $item['label'] }}</h3>
                            <div style="text-align: center;">
                                @if (str_starts_with($fileUrl, 'http'))
                                    <img src="{{ $fileUrl }}" style="max-width: 95%; max-height: 800px; display: block; margin: 0 auto;">
                                @else
                                    <img src="{{ public_path($fileUrl) }}" style="max-width: 95%; max-height: 800px; display: block; margin: 0 auto;">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

</body>
</html>
