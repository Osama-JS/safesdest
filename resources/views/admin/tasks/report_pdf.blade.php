<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title>#{{ $task->id }}</title>
    <style>
        body {
            font-family: 'tajawal';
            margin: 0;
            padding: 0;
            color: #2c3e50;
            font-size: 12px;
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .header-table td {
            vertical-align: middle;
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
            {{ app()->getLocale() === 'ar' ? 'border-right' : 'border-left' }}: 4px solid #070000;
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

        .signature-section {
            page-break-before: always;
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
        }

        .signature-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #1a2733;
        }

        .signature-box {
            border: 1px dashed #ccc;
            height: 120px;
            margin-top: 10px;
            background-color: #fafafa;
        }

        .conditions {
            font-size: 8.5px;
            line-height: 1.2;
            color: #34495e;
        }

        .conditions ul {
            margin: 2px 0;
            padding-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 15px;
        }

        .conditions li {
            margin-bottom: 2px;
        }

        .signer-info {
            margin-bottom: 5px;
            font-size: 12px;
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

    <table class="header-table">
        <tr>
            <td style="text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}">
                <div class="platform-info">
                    <div><strong>{{ __('Safe Dest') }}</strong></div>
                    <div>{{ __('info@safedest.com') }}</div>
                    <div>{{ __('+966556978782') }}</div>
                </div>
            </td>
            <td style="text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}">
                <div class="logo">
                    <img src="{{ public_path('assets/img/logo.png') }}"
                        alt="Logo" height="40">
                </div>
            </td>
        </tr>
    </table>

    <h1>
        {{ __('Task Status Report') }}
        #{{ $task->id }}
    </h1>

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
                    <div class="value">{{ $task->customer?->company_name ?? $task->customer?->name ?? $task->user->name }}</div>
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
                    <div class="value">
                        {{ $task->pickup?->address ?? '-' }}
                        @if ($task->pickup?->latitude && $task->pickup?->longitude)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $task->pickup->latitude }},{{ $task->pickup->longitude }}"
                                style="color: #3498db; text-decoration: none; font-size: 10px; margin-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 10px;">
                                [{{ __('Open in Maps') }}]
                            </a>
                        @endif
                    </div>
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
                    <div class="value">
                        {{ $task->delivery?->address ?? '-' }}
                        @if ($task->delivery?->latitude && $task->delivery?->longitude)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $task->delivery->latitude }},{{ $task->delivery->longitude }}"
                                style="color: #3498db; text-decoration: none; font-size: 10px; margin-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 10px;">
                                [{{ __('Open in Maps') }}]
                            </a>
                        @endif
                    </div>
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
            @if ($task->driver && !empty((array) $task->driver->driver_visible_additional_data))
                @php
                    $driverData = (array) $task->driver->driver_visible_additional_data;
                    $chunks = array_chunk($driverData, 3, true);
                @endphp
                @foreach ($chunks as $chunk)
                    <tr>
                        @foreach ($chunk as $item)
                            <td>
                                <div class="label">{{ $item['label'] ?? '-' }}</div>
                                    @php
                                        $val = $item['value'];
                                        $isImage = false;
                                        $imageSrc = null;

                                        // Check if value looks like an image
                                        if (
                                            !empty($val) &&
                                            is_string($val) &&
                                            preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $val)
                                        ) {
                                            // 1. Clean path (remove storage/ prefix if present to normalize)
                                            $cleanVal = preg_replace('/^storage\//', '', $val);

                                            // Define potential absolute paths
                                            $possiblePaths = [
                                                public_path($val), // Direct public path
                                                public_path('storage/' . $cleanVal), // Storage link in public
                                                storage_path('app/public/' . $cleanVal), // Direct storage path
                                                storage_path('app/' . $cleanVal), // Fallback storage path
                                            ];

                                            foreach ($possiblePaths as $path) {
                                                if (file_exists($path) && is_file($path)) {
                                                    // Use direct path for mPDF
                                                    $imageSrc = $path;
                                                    $isImage = true;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp

                                    @if ($isImage && $imageSrc)
                                        <div style="margin-top: 5px;">
                                            <img src="{{ $imageSrc }}"
                                                style="max-width: 250px; max-height: 250px; border: 1px solid #eee; border-radius: 4px;">
                                        </div>
                                    @else
                                        {{ $item['value'] ?? '-' }}
                                    @endif
                                </div>
                            </td>
                        @endforeach
                        @for ($i = count($chunk); $i < 3; $i++)
                            <td></td>
                        @endfor
                    </tr>
                @endforeach
            @endif
        </table>
    </div>

    {{-- Task Additional Data --}}
    @if (!empty((array) $task->pdf_visible_additional_data))
        <div class="section">
            <div class="section-title">{{ __('Task Additional Data') }}</div>
            <table class="info-table">
                @php
                    $taskData = (array) $task->pdf_visible_additional_data;
                    $chunks = array_chunk($taskData, 3, true);
                @endphp
                @foreach ($chunks as $chunk)
                    <tr>
                        @foreach ($chunk as $item)
                            <td>
                                <div class="label">{{ $item['label'] ?? '-' }}</div>
                                <div class="value">
                                    @php
                                        $val = $item['value'];
                                        $isImage = false;
                                        $imageSrc = null;

                                        // Check if value looks like an image
                                        if (
                                            !empty($val) &&
                                            is_string($val) &&
                                            preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $val)
                                        ) {
                                            // 1. Clean path (remove storage/ prefix if present to normalize)
                                            $cleanVal = preg_replace('/^storage\//', '', $val);

                                            // Define potential absolute paths
                                            $possiblePaths = [
                                                public_path($val), // Direct public path
                                                public_path('storage/' . $cleanVal), // Storage link in public
                                                storage_path('app/public/' . $cleanVal), // Direct storage path
                                                storage_path('app/' . $cleanVal), // Fallback storage path
                                            ];

                                            foreach ($possiblePaths as $path) {
                                                if (file_exists($path) && is_file($path)) {
                                                    // Use direct path for mPDF
                                                    $imageSrc = $path;
                                                    $isImage = true;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp

                                    @if ($isImage && $imageSrc)
                                        <div style="margin-top: 5px;">
                                            <img src="{{ $imageSrc }}"
                                                style="max-width: 250px; max-height: 250px; border: 1px solid #eee; border-radius: 4px;">
                                        </div>
                                    @else
                                        {{ $item['value'] ?? '-' }}
                                    @endif
                                </div>
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

    {{-- Conditions --}}
    <div class="section conditions">
        <div class="section-title">{{ __('Conditions & Roles') }}</div>
        <ul>
            <li>{{ __('The transport contract specifies the type and nature of the goods contracted for transport, the size, weight, number or quantity according to the type of goods, the data of the contracting parties, transport fees, payment method, place, date and time of transfer of responsibility for the goods to the carrier, and the place and period of delivery to the consignee. It also defines the mechanism of receipt and delivery including loading, unloading, handling, stacking and storage operations, and the requirements and conditions of the transport process specific to the type and nature of the goods. The contracting parties may agree to use electronic transactions in all matters related to transport contract transactions in accordance with the regulations, bylaws and instructions in force in the Kingdom.') }}
            </li>
            <li>{{ __('The contracting parties may agree on additional conditions in the transport contract that do not violate the provisions of this regulation and related regulations, bylaws and instructions.') }}
            </li>
            <li>{{ __('The carrier may - under his responsibility and supervision - assign part or all of the tasks assigned to him to implement the terms of the transport contract, unless otherwise agreed in the contract. The carrier shall be directly responsible for all actions and acts of his subordinates in implementing the obligations arising from the transport contract, and any condition that exempts the carrier from responsibility for the actions and acts of his subordinates shall be null and void.') }}
            </li>
            <li>{{ __('The carrier is responsible for the goods from the time he receives them or when the party assigned by him performs any of the tasks assigned to him, and his responsibility ends when he delivers the goods to the consignee or the authorized person to receive them at their destination.') }}
            </li>
            <li>{{ __('The carrier does not bear responsibility for damages resulting from loading or unloading goods from or onto the truck, except in the case that loading and unloading was done by the carrier at the request of the sender or consignee.') }}
            </li>
            <li>{{ __('If necessary to preserve the goods, the carrier may, upon receiving them, repack, repair, increase or decrease the packaging, or take other necessary measures that need to be taken with or without compensation according to the agreement with the sender or his representative.') }}
            </li>
            <li>{{ __('The carrier shall be liable for loss resulting from damage or loss of goods as well as delay in delivery if the incident that caused the damage, loss or delay in delivery occurred at the time when the goods were in his custody, unless he proves that no error or negligence was issued by him or any of his employees or agents that caused or contributed to the delay in delivery, loss or damage of the goods. He may also be exempted from liability if he proves that the delay in delivery, loss or damage of the goods is due to one of the following reasons:') }}
            </li>
            <ul>
                <li>{{ __('Error by the sender or consignee or any of their agents or representatives.') }}</li>
                <li>{{ __('Force majeure.') }}</li>
                <li>{{ __('Inherent or hidden defect in the goods.') }}</li>
                <li>{{ __('Decrease in volume or weight during transport for reasons related to the nature of the transported goods such as evaporation, drying or ripening.') }}
                </li>
                <li>{{ __('Another reason that is beyond the control of the carrier and prevents him from implementing the terms of the transport contract.') }}
                </li>
            </ul>
            <li>{{ __('The carrier shall be liable for damage or loss resulting from delay in delivery of goods at the specified time if the sender has declared in writing his desire to deliver the goods at this specified time and the carrier has agreed to it.') }}
            </li>
            <li>{{ __('In the absence of a prior agreement regarding the delivery date of the goods, the carrier shall be liable for delay in delivery if it is not delivered within a reasonable period of time after taking into account the circumstances that may lead to this delay.') }}
            </li>
            <li>{{ __('Goods are treated as lost and the carrier bears responsibility for their loss in the following cases:') }}
            </li>
            <ul>
                <li>{{ __('If the goods do not arrive within (30) thirty days after the agreed delivery date.') }}
                </li>
                <li>{{ __('After the expiry of (60) sixty days from the carriers receipt of the goods; if no delivery date is specified.') }}
                </li>
            </ul>
            <li>{{ __('The carrier shall not be liable for loss resulting from delay in delivery, damage or loss of goods if this resulted from the sender providing incorrect data or information about the nature of the goods in the transport contract or transport document.') }}
            </li>
            <li>{{ __('The carrier shall not be liable for any decrease in weight or volume that affects the goods by virtue of their nature during transport, provided that this decrease does not exceed the percentage determined according to the general rules adopted in transporting such goods. If the transport document includes different goods divided into groups or packages and the weight of each is indicated in the document, the permissible shortage shall be determined on the basis of the weight of each group or package separately.') }}
            </li>
            <li>{{ __('The carrier does not bear the shortage that appears in goods transported in a container or similar equipment prepared by the sender and sealed with his seal if the carrier delivers it to the consignee with its intact seal and at the specified time for delivery.') }}
            </li>
            <li>{{ __('The carrier is obligated to collect the amounts that were stipulated under the terms of the transport contract to be collected from the consignee for the account of the sender upon delivery. If the goods are delivered without collecting those amounts, the carrier is obligated to pay those amounts to the sender without prejudice to his right to recourse against the consignee.') }}
            </li>
            <li>{{ __('The carrier shall be liable for the loss of documents attached to the transport document or contained therein or deposited with him, or for their incorrect use, provided that the compensation to be paid does not exceed considering that the goods are lost.') }}
            </li>
        </ul>

        @if ($task->conditions)
            <h4>{{ __('Customers Conditions') }}</h4>
            <p>{{ $task->conditions }}</p>
        @endif
    </div>

    {{-- Dedicated Signature Page --}}
    <div class="signature-section">
        <div class="signature-title">{{ __('Electronic Signature Page') }}</div>

        <table style="width: 100%; border-spacing: 20px;">
            <tr>
                {{-- Driver Side --}}
                <td style="border: 1px solid #e1e1e1; padding: 20px; background: #ffffff; vertical-align: top; border-radius: 10px;">
                    <div style="font-weight: bold; margin-bottom: 12px; font-size: 15px; color: #1a2733; border-bottom: 2px solid #34495e; padding-bottom: 8px; display: inline-block;">
                        {{ __('Driver Signature') }}
                    </div>
                    <div class="signer-info" style="line-height: 1.6;">
                        <strong>{{ __('Name') }}:</strong> <span style="color: #2c3e50;">{{ $task->driver?->name ?? '-' }}</span><br>
                        <strong>{{ __('Phone') }}:</strong> <span style="color: #2c3e50;">{{ $task->driver?->phone ?? '-' }}</span>
                    </div>

                    <div style="margin-top: 15px; text-align: center;">
                        <div class="signature-box" style="border: 2px dashed #d1d5db; border-radius: 8px; background: #f9fafb; height: 110px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #9ca3af; font-size: 11px; font-style: italic;">
                                {{ __('Sign Here') }}
                            </div>
                        </div>
                    </div>

                </td>
                {{-- Customer Side --}}
                <td style="border: 1px solid #e1e1e1; padding: 20px; background: #ffffff; vertical-align: top; border-radius: 10px; padding-bottom: 150px;">
                    <div style="font-weight: bold; margin-bottom: 12px; font-size: 15px; color: #1a2733; border-bottom: 2px solid #34495e; padding-bottom: 8px; display: inline-block;">
                        {{ __('Customer Signature') }}
                    </div>
                    <div class="signer-info" style="line-height: 1.6;">
                        <strong>{{ __('Name') }}:</strong> <span style="color: #2c3e50;">{{ $task->customer?->name ?? $task->user->name }}</span><br>
                        <strong>{{ __('Phone') }}:</strong> <span style="color: #2c3e50;">{{ $task->customer?->phone ?? $task->user->phone ?? '-' }}</span>
                    </div>

                    <div style="margin-top: 20px; text-align: center;">
                        <div class="signature-box" style="border: 2px dashed #d1d5db; border-radius: 8px; background: #f9fafb; height: 110px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #9ca3af; font-size: 30px; font-style: italic;">
                                {{ __('Sign Here') }}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div style="margin-top: 30px; text-align: center; font-size: 11px; color: #7f8c8d; font-style: italic;">
            {{ __('This document is electronically signed and legally binding according to the regulations of the Kingdom of Saudi Arabia.') }}
        </div>
    </div>


    <footer>
        {{ __('task report generated at') }}: {{ now()->format('Y-m-d H:i') }}
    </footer>


</body>

</html>
