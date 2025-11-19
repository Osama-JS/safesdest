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
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(url('assets/img/logo.png'))) }}"
                            alt="Logo" height="40">
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <h1>
        {{ __('Task Status Report') }}
        <!-- Shipping Consignee -->
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
                                                <p style="color:red;">❌ {{ __('Image not found') }}</p>
                                            @endif
                                        @break

                                        @case('file')
                                            <a href="{{ url('storage/' . $field['value']) }}"
                                                target="_blank">{{ basename($field['value']) }}</a>
                                        @break

                                        @case('file_expiration_date')
                                            <a href="{{ url('storage/' . $field['value']) }}"
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
                                                <p style="color:red;">❌ {{ __('Image not found') }}</p>
                                            @endif
                                        @break

                                        @case('file')
                                            <a href="{{ url('storage/' . $field['value']) }}"
                                                target="_blank">{{ basename($field['value']) }}</a>
                                        @break

                                        @case('file_expiration_date')
                                            <a href="{{ url('storage/' . $field['value']) }}"
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
