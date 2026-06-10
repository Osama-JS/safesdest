@extends('layouts/layoutMaster')

@section('title', __('Task Details'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('page-style')
    <style>
        .timeline {
            list-style: none;
            padding: 0;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ccc;
        }

        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 20px;
        }

        .timeline-point {
            position: absolute;
            left: 7px;
            top: 5px;
            width: 16px;
            height: 16px;
            background: #0d6efd;
            border-radius: 50%;
        }

        .info-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .delivery-note-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .delivery-note-preview:hover {
            transform: scale(1.05);
        }

        .file-icon {
            font-size: 3rem;
            color: #6c757d;
        }

        .stats-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
    <div class="container my-4">
        <div class="row">
            <!-- Task Details -->
            <div class="col-lg-8 col-md-12">
                <div class="card mb-4 info-card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">
                            <i class="ti ti-clipboard-list me-2 text-primary"></i>
                            {{ __('Task Details') }} #{{ $task->id }}
                        </h5>

                        <div class="d-flex gap-2 flex-wrap">
                            @if (!$task->closed && in_array($task->status, ['assign', 'started', 'in pickup point', 'loading', 'in the way', 'in delivery point', 'unloading']))
                                <a href="{{ route('customer.tasks.track', $task->id) }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-map-pin me-1"></i>{{ __('Track Task') }}
                                </a>
                            @endif

                            {{-- Policy buttons - always visible --}}
                            <a href="{{ route('customer.tasks.download-policy', $task->id) }}"
                                target="_blank" class="btn btn-sm btn-info">
                                <i class="ti ti-file-certificate me-1"></i>
                                {{ __('Download Policy') }}
                            </a>

                            @if($task->customer && $task->customer->policy_file_name)
                                <a href="{{ route('customer.tasks.policy_custom', $task->id) }}"
                                    target="_blank" class="btn btn-sm btn-warning">
                                    <i class="fas fa-print me-1"></i>
                                    {{ __('Custom Policy') }}
                                </a>
                            @endif

                            {{-- Invoice - only when paid --}}
                            @if ($task->payment_status === 'paid' || $task->payment_status === 'completed' || $task->status === 'completed')
                                <a href="javascript:void(0);" onclick="downloadInvoice({{ $task->id }})"
                                    class="btn btn-sm btn-success">
                                    <i class="ti ti-file-invoice me-1"></i>
                                    {{ __('Download Invoice') }}
                                </a>
                            @endif

                            <a href="{{ route('customer.tasks.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                {{ __('Back to Tasks') }}
                            </a>
                        </div>
                    </div>

                    <div class="card-body mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted border-bottom pb-2">{{ __('Basic Information') }}</h6>
                                <p><strong>{{ __('Order ID') }}:</strong> {{ $task->order_id ?? 'N/A' }}</p>
                                <p><strong>{{ __('Vehicle') }}:</strong>
                                    @if ($task->vehicle_size)
                                        {{ $task->vehicle_size->type->vehicle->name }} -
                                        {{ $task->vehicle_size->type->name }} -
                                        {{ $task->vehicle_size->name }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                                <p><strong>{{ __('Created At') }}:</strong> {{ $task->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted border-bottom pb-2">{{ __('Driver Information') }}</h6>
                                @if ($task->driver)
                                    <p><strong>{{ __('Driver Name') }}:</strong> {{ $task->driver->name }}</p>
                                    <p><strong>{{ __('Phone') }}:</strong> {{ $task->driver->phone_code }} {{ $task->driver->phone }}</p>
                                    @if ($task->driver->full_whatsapp_number)
                                        <p>
                                            <i class="ti ti-brand-whatsapp text-success me-1"></i><strong>{{ __('WhatsApp') }}:</strong>
                                            <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $task->driver->full_whatsapp_number) }}"
                                                target="_blank" class="text-success text-decoration-none">
                                                {{ $task->driver->whatsapp_display }}
                                            </a>
                                        </p>
                                    @endif
                                @else
                                    <p class="text-muted">{{ __('No driver assigned yet') }}</p>
                                @endif
                            </div>
                        </div>


                        @if ($task->conditions)
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                {{ $task->conditions }}
                            </div>
                        @endif

                        <!-- Task Stats -->
                        <div class="row g-3 mt-4 mb-2">
                            <div class="col-md-4">
                                <div class="card stats-card text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-dollar-sign fs-2 mb-2"></i>
                                        <h6 class="card-title text-white">{{ __('Total Price') }}</h6>
                                        <h4 class="mb-0 text-white">
                                            {{ $task->total_price ? number_format($task->total_price, 2) : '0.00' }} SAR
                                        </h4>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card bg-info text-white h-100 border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <i class="fas fa-info-circle fs-2 mb-2"></i>
                                        <h6 class="card-title text-white">{{ __('Payment Status') }}</h6>
                                        <span class="status-badge bg-white text-info fw-bold">
                                            {{ ucfirst($task->payment_status ?? 'pending') }}
                                        </span>
                                        @if(in_array(strtolower($task->payment_status ?? 'unpaid'), ['pending', 'unpaid', '']) && $task->total_price > 0)
                                            <button onclick="openPaymentModal({{ $task->id }}, {{ $task->total_price }})" class="btn btn-light btn-sm mt-2 text-info fw-bold rounded-pill px-3 shadow-sm border">
                                                <i class="ti ti-credit-card me-1"></i> {{ __('Pay Now') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card bg-{{ $task->closed ? 'danger' : 'success' }} text-white h-100 border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <i class="fas fa-lock{{ $task->closed ? '' : '-open' }} fs-2 mb-2"></i>
                                        <h6 class="card-title text-white">{{ __('Status') }}</h6>
                                        <span class="status-badge bg-white text-{{ $task->closed ? 'danger' : 'success' }} fw-bold">
                                            {{ ucfirst($task->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pickup & Delivery -->
                <div class="card mb-4 info-card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-route me-2 text-primary"></i>
                            {{ __('Pickup & Delivery Points') }}
                        </h5>
                    </div>
                    <div class="card-body mt-4">
                        <div class="row g-4">
                            <!-- Pickup -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 shadow-sm bg-white">
                                    <h6 class="mb-3 text-primary d-flex align-items-center justify-content-between">
                                        <span>
                                            <i class="fas fa-map-marker-alt me-1 text-success"></i> {{ __('Pickup') }}
                                        </span>
                                        @if (optional($task->pickup)->latitude && optional($task->pickup)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->pickup->latitude }},{{ $task->pickup->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-map-location-dot me-1"></i>
                                            </a>
                                        @endif
                                    </h6>
                                    @if ($task->pickup)
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2"><strong>{{ __('Address') }}:</strong> {{ $task->pickup->address }}</li>
                                            <li class="mb-2"><strong>{{ __('Contact') }}:</strong> {{ $task->pickup->contact_name ?? 'N/A' }} ({{ $task->pickup->contact_phone ?? 'N/A' }})</li>
                                            @if ($task->pickup->scheduled_time)
                                                <li class="mb-2"><strong>{{ __('Time') }}:</strong> {{ \Carbon\Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i') }}</li>
                                            @endif
                                            @if ($task->pickup->note)
                                                <li><strong>{{ __('Note') }}:</strong> {{ $task->pickup->note }}</li>
                                            @endif
                                        </ul>
                                    @endif
                                </div>
                            </div>

                            <!-- Delivery -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 shadow-sm bg-white">
                                    <h6 class="mb-3 text-danger d-flex align-items-center justify-content-between">
                                        <span>
                                            <i class="fas fa-truck me-1"></i> {{ __('Delivery') }}
                                        </span>
                                        @if (optional($task->delivery)->latitude && optional($task->delivery)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->delivery->latitude }},{{ $task->delivery->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-map-location-dot me-1"></i>
                                            </a>
                                        @endif
                                    </h6>
                                    @if ($task->delivery)
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2"><strong>{{ __('Address') }}:</strong> {{ $task->delivery->address }}</li>
                                            <li class="mb-2"><strong>{{ __('Contact') }}:</strong> {{ $task->delivery->contact_name ?? 'N/A' }} ({{ $task->delivery->contact_phone ?? 'N/A' }})</li>
                                            @if ($task->delivery->scheduled_time)
                                                <li class="mb-2"><strong>{{ __('Time') }}:</strong> {{ \Carbon\Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i') }}</li>
                                            @endif
                                            @if ($task->delivery->note)
                                                <li><strong>{{ __('Note') }}:</strong> {{ $task->delivery->note }}</li>
                                            @endif
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Note (User implemented section) -->
                @if ($task->closed && ($task->delivery_note || $task->delivery_number))
                    <div class="card mb-4 info-card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-file-invoice me-2 text-primary"></i>
                                {{ __('Delivery Note') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                @if ($task->delivery_number)
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100 bg-light">
                                            <h6 class="text-primary mb-3">
                                                <i class="fas fa-hashtag me-1"></i>
                                                {{ __('Delivery Number') }}
                                            </h6>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle p-2 me-3">
                                                    <i class="fas fa-barcode"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-0 text-primary fw-bold">{{ $task->delivery_number }}
                                                    </h4>
                                                    <small class="text-muted">{{ __('Reference Number') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($task->delivery_note)
                                    <div class="col-md-{{ $task->delivery_number ? '6' : '12' }}">
                                        <div class="border rounded p-3 h-100 bg-light">
                                            <h6 class="text-success mb-3">
                                                <i class="fas fa-file-alt me-1"></i>
                                                {{ __('Delivery Note File') }}
                                            </h6>

                                            @php
                                                $filePath = $task->delivery_note;
                                                $fileName = basename($filePath);
                                                $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                                $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                $fileIcons = [
                                                    'pdf' => ['icon' => 'fas fa-file-pdf', 'color' => 'text-danger'],
                                                    'doc' => ['icon' => 'fas fa-file-word', 'color' => 'text-primary'],
                                                    'docx' => ['icon' => 'fas fa-file-word', 'color' => 'text-primary'],
                                                    'txt' => ['icon' => 'fas fa-file-alt', 'color' => 'text-secondary'],
                                                    'csv' => ['icon' => 'fas fa-file-csv', 'color' => 'text-success'],
                                                ];
                                                $fileIcon = $fileIcons[$fileExtension] ?? ['icon' => 'fas fa-file', 'color' => 'text-muted'];
                                            @endphp

                                            @if ($isImage)
                                                <div class="text-center">
                                                    <img src="{{ asset('storage/' . $filePath) }}"
                                                        alt="{{ __('Delivery Note') }}"
                                                        class="delivery-note-preview img-fluid border shadow-sm"
                                                        data-bs-toggle="modal" data-bs-target="#deliveryNoteModal"
                                                        style="cursor: pointer;">
                                                    <div class="mt-2">
                                                        <small class="text-muted">{{ $fileName }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center">
                                                    <div class="mb-3">
                                                        <i class="{{ $fileIcon['icon'] }} file-icon {{ $fileIcon['color'] }}"></i>
                                                    </div>
                                                    <h6 class="mb-2 text-truncate">{{ $fileName }}</h6>
                                                    <a href="{{ asset('storage/' . $filePath) }}" target="_blank"
                                                        class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-download me-1"></i>
                                                        {{ __('Download File') }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Modal -->
                    @if ($task->delivery_note && in_array(strtolower(pathinfo($task->delivery_note, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                        <div class="modal fade" id="deliveryNoteModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ __('Delivery Note') }} - #{{ $task->id }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img src="{{ asset('storage/' . $task->delivery_note) }}" class="img-fluid rounded shadow">
                                    </div>
                                    <div class="modal-footer">
                                        <a href="{{ asset('storage/' . $task->delivery_note) }}" target="_blank" class="btn btn-primary">
                                            <i class="fas fa-external-link-alt me-1"></i> {{ __('Open in New Tab') }}
                                        </a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Additional Data (Adopted Admin Style) -->
                @if ($task->customer_visible_additional_data && count($task->customer_visible_additional_data) > 0)
                    <div class="card mb-4 info-card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-layer-group me-2 text-primary"></i>
                                {{ __('Additional Information') }}
                            </h5>
                        </div>

                        <div class="card-body mt-3">
                            <div class="row">
                                @foreach ($task->customer_visible_additional_data as $key => $field)
                                    <div class="col-md-6 mb-4">
                                        <div class="border rounded p-3 h-100 shadow-sm">
                                            <h6 class="text-muted mb-2">{{ $field['label'] }}</h6>

                                            @switch($field['type'])
                                                @case('text')
                                                @case('string')
                                                @case('number')
                                                    <p class="mb-0 fw-bold">{{ $field['value'] ?? 'N/A' }}</p>
                                                @break

                                                @case('image')
                                                    @if($field['value'])
                                                        <div class="text-center">
                                                            <img src="{{ asset('storage/' . $field['value']) }}"
                                                                alt="{{ $field['label'] }}" class="img-fluid rounded border mt-2"
                                                                style="max-height: 200px; object-fit: cover;">
                                                            <div class="mt-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}" target="_blank" class="btn btn-sm btn-label-primary">
                                                                    <i class="ti ti-eye me-1"></i>{{ __('View Full Image') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">{{ __('No image uploaded') }}</span>
                                                    @endif
                                                @break

                                                @case('file')
                                                    @if($field['value'])
                                                        @php
                                                            $ext = strtolower(pathinfo($field['value'], PATHINFO_EXTENSION));
                                                            $icons = [
                                                                'pdf' => 'ti ti-file-text',
                                                                'doc' => 'ti ti-file-description',
                                                                'docx' => 'ti ti-file-description',
                                                                'xls' => 'ti ti-file-spreadsheet',
                                                                'xlsx' => 'ti ti-file-spreadsheet',
                                                            ];
                                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                                        @endphp
                                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                                            class="d-flex align-items-center text-decoration-none border p-2 rounded bg-light">
                                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                            <span class="text-truncate text-dark">{{ basename($field['value']) }}</span>
                                                        </a>
                                                    @else
                                                        <span class="text-muted">{{ __('No file uploaded') }}</span>
                                                    @endif
                                                @break

                                                @case('file_expiration_date')
                                                    @if ($field['value'])
                                                        @php
                                                            $ext = strtolower(pathinfo($field['value'], PATHINFO_EXTENSION));
                                                            $icons = [
                                                                'pdf' => 'ti ti-file-text',
                                                                'doc' => 'ti ti-file-description',
                                                                'docx' => 'ti ti-file-description',
                                                                'xls' => 'ti ti-file-spreadsheet',
                                                                'xlsx' => 'ti ti-file-spreadsheet',
                                                            ];
                                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                                        @endphp
                                                        <div class="d-flex flex-column gap-2">
                                                            <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                                                class="d-flex align-items-center text-decoration-none border p-2 rounded bg-light">
                                                                <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                <span class="text-truncate text-dark">{{ basename($field['value']) }}</span>
                                                            </a>
                                                            @if (isset($field['expiration']))
                                                                <div class="badge bg-label-warning align-self-start">
                                                                    <i class="ti ti-calendar-event me-1"></i>
                                                                    {{ __('Expires') }}: {{ $field['expiration'] }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">{{ __('No file uploaded') }}</span>
                                                    @endif
                                                @break

                                                @default
                                                    <p class="mb-0">{{ $field['value'] ?? 'N/A' }}</p>
                                            @endswitch
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Driver Additional Data (Moved here) -->
                @if ($task->driver && !empty((array) $task->driver->driver_visible_additional_data))
                    <div class="card mb-4 info-card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="ti ti-id-badge me-2 text-primary"></i>
                                {{ __('Driver Additional Information') }}
                            </h5>
                        </div>
                        <div class="card-body mt-3">
                            <div class="row">
                                @php $driverAdditional = (array) $task->driver->driver_visible_additional_data; @endphp
                                @foreach ($driverAdditional as $field)
                                    @if(isset($field['label']) && isset($field['value']) && $field['value'])
                                        <div class="col-md-6 mb-4">
                                            <div class="border rounded p-3 h-100 shadow-sm">
                                                <h6 class="text-muted mb-2">{{ $field['label'] }}</h6>

                                                @switch($field['type'] ?? 'text')
                                                    @case('image')
                                                        <div class="text-center">
                                                            <img src="{{ asset('storage/' . $field['value']) }}"
                                                                alt="{{ $field['label'] }}" class="img-fluid rounded border mt-2"
                                                                style="max-height: 200px; object-fit: cover;">
                                                            <div class="mt-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}" target="_blank" class="btn btn-sm btn-label-primary">
                                                                    <i class="ti ti-eye me-1"></i>{{ __('View Full Image') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                        @if(isset($field['expiration']) && $field['expiration'])
                                                            <div class="mt-2 text-center">
                                                                <span class="badge bg-label-warning">{{ __('Exp') }}: {{ $field['expiration'] }}</span>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @case('file')
                                                    @case('file_expiration_date')
                                                        @php
                                                            $ext = strtolower(pathinfo($field['value'], PATHINFO_EXTENSION));
                                                            $icons = [
                                                                'pdf' => 'ti ti-file-text',
                                                                'doc' => 'ti ti-file-description',
                                                                'docx' => 'ti ti-file-description',
                                                                'xls' => 'ti ti-file-spreadsheet',
                                                                'xlsx' => 'ti ti-file-spreadsheet',
                                                            ];
                                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                                        @endphp
                                                        <div class="d-flex flex-column gap-2">
                                                            <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                                                class="d-flex align-items-center text-decoration-none border p-2 rounded bg-light">
                                                                <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                <span class="text-truncate text-dark">{{ basename($field['value']) }}</span>
                                                            </a>
                                                            @if(isset($field['expiration']) && $field['expiration'])
                                                                <div class="badge bg-label-warning align-self-start">
                                                                    <i class="ti ti-calendar-event me-1"></i>
                                                                    {{ __('Expires') }}: {{ $field['expiration'] }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @break

                                                    @default
                                                        <p class="mb-0 fw-bold">{{ $field['value'] }}</p>
                                                        @if(isset($field['expiration']) && $field['expiration'])
                                                            <span class="badge bg-label-warning mt-2 d-inline-block">{{ __('Exp') }}: {{ $field['expiration'] }}</span>
                                                        @endif
                                                @endswitch
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Task History Sidebar -->
            <div class="col-lg-4 col-md-12">
                <div class="card info-card h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark">
                            <i class="ti ti-history me-2 text-primary"></i>
                            {{ __('Task History') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($task->history && $task->history->count() > 0)
                            <ul class="timeline">
                                @foreach ($task->history->sortByDesc('created_at') as $history)
                                    <li class="timeline-item">
                                        <span class="timeline-point"></span>
                                        <div class="timeline-event">
                                            <div class="d-flex justify-content-between mb-1">
                                                <strong class="text-dark">{{ ucfirst(str_replace('_', ' ', $history->action_type)) }}</strong>
                                                <small class="text-muted">{{ $history->created_at->format('Y-m-d H:i') }}</small>
                                            </div>
                                            <p class="mb-1 small text-muted">{{ $history->description }}</p>
                                            <div class="mt-1">
                                                @if ($history->user)
                                                    <span class="badge bg-label-secondary x-small">{{ __('By') }}: {{ $history->user->name }}</span>
                                                @elseif($history->driver)
                                                    <span class="badge bg-label-primary x-small">{{ __('By Driver') }}: {{ $history->driver->name }}</span>
                                                @endif
                                                @if ($history->file_path)
                                                    <a href="{{ asset('storage/' . $history->file_path) }}" target="_blank"
                                                        class="btn btn-xs btn-outline-info ms-2">
                                                        <i class="ti ti-paperclip me-1"></i>{{ __('Attachment') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted text-center py-4">{{ __('No history available') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        function downloadInvoice(taskId) {
            Swal.fire({
                title: '{{ __('Generating Invoice...') }}',
                text: '{{ __('Please wait while we generate your invoice.') }}',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const url = "{{ route('customer.tasks.invoice', ':id') }}".replace(':id', taskId);

            fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.error || '{{ __('Failed to generate invoice.') }}');
                        });
                    }
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `invoice_${taskId}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);

                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('Success') }}',
                        text: '{{ __('Invoice downloaded successfully.') }}',
                        timer: 2000,
                        showConfirmButton: false
                    });
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Error') }}',
                        text: error.message
                    });
                });
        }
    </script>
    @include('customers.tasks.payment-modal')
@endsection
