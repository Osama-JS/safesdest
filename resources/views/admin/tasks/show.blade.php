@extends('layouts.layoutMaster')

@section('title', 'تفاصيل المهمة')

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

        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stats-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }

        .delivery-section {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            border: none;
            color: white;
        }
    </style>
@endsection
@section('page-script')
    <script>
        function openReport() {
            const reportWindow = window.open('{{ route('tasks.report', $task->id) }}', '_blank');
        }
    </script>
@endsection
@section('task-isactive', 'active')
@section('content')
    <div class="container my-4">
        <div class="row">
            <!-- تفاصيل المهمة -->
            <div class="col-lg-8 col-md-12">
                <div class="card mb-4 info-card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-tasks me-2 text-primary"></i>
                            {{ __('Task Details') }} #{{ $task->id }}
                        </h5>

                        <div class="d-flex gap-2">
                            <a href="#" class="btn btn-sm btn-primary" onclick="openReport()">
                                <i class="fas fa-download me-1"></i>
                                {{ __('Download Report') }}
                            </a>

                            <a href="{{ route('tasks.list') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                {{ __('Back to Tasks') }}
                            </a>
                        </div>
                    </div>

                    <div class="card-body mt-3">
                        <p><strong>{{ __('owner') }}:</strong>
                            {{ $task->owner }}</p>
                        <p></strong>
                            {{ $task->owner == 'admin' ? $task->user->name : $task->customer->name }}</p>
                        <p><strong>{{ __('phone') }}:</strong>
                            {{ $task->owner == 'admin' ? $task->user->phone : $task->customer->phone }}</p>

                        @if ($task->driver_id && $task->driver)
                            <div class="card mb-4 shadow-sm">
                                <div
                                    class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-dark">
                                        <i class="fas fa-user me-2 text-primary"></i> {{ __('Assigned Driver') }}
                                    </h6>
                                    <a href="{{ route('drivers.show', [$task->driver->id, $task->driver->name]) }}"
                                        class="btn btn-primary btn-sm">
                                        {{ __('View Profile') }}
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center">
                                            @if ($task->driver->image)
                                                <img src="{{ asset('storage/' . $task->driver->image) }}"
                                                    alt="{{ $task->driver->name }}"
                                                    class="img-fluid rounded-circle shadow-sm mb-2"
                                                    style="width: 80px; height: 80px; object-fit: cover;">
                                            @else
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width: 80px; height: 80px;">
                                                    <i class="fas fa-user fa-2x"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-10">
                                            <h5 class="mb-1">{{ $task->driver->name }}</h5>
                                            <p class="mb-1">
                                                <i class="fas fa-phone-alt me-1 text-success"></i>
                                                <a href="tel:{{ $task->driver->phone }}">{{ $task->driver->phone }}</a>
                                            </p>
                                            @if ($task->driver->email)
                                                <p class="mb-1">
                                                    <i class="fas fa-envelope me-1 text-warning"></i>
                                                    <a
                                                        href="mailto:{{ $task->driver->email }}">{{ $task->driver->email }}</a>
                                                </p>
                                            @endif
                                            @if ($task->driver->vehicle_size)
                                                <p class="mb-1">
                                                    <i class="fas fa-truck me-1 text-primary"></i>
                                                    {{ $task->driver->vehicle_size->name }}
                                                </p>
                                            @endif
                                            @if ($task->driver->team)
                                                <p class="mb-0">
                                                    <i class="fas fa-users me-1 text-info"></i>
                                                    {{ __('Team') }}: {{ $task->driver->team->name }}
                                                </p>
                                            @endif

                                            @if ($task->driver->longitude && $task->driver->altitude)
                                                <p class="mt-2 mb-0">
                                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                                    <a href="https://www.google.com/maps?q={{ $task->driver->altitude }},{{ $task->driver->longitude }}"
                                                        target="_blank">
                                                        {{ __('View on Map') }}
                                                    </a>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                {{ __('Driver not assigned yet') }}
                            </div>
                        @endif


                        <!-- إحصائيات المهمة -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3 col-sm-6">
                                <div class="card stats-card text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-dollar-sign fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Total Price') }}</h6>
                                        <h4 class="mb-0">
                                            {{ $task->total_price ? number_format($task->total_price, 2) : '0.00' }} SAR
                                        </h4>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="card bg-warning text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-percentage fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Commission') }}</h6>
                                        <h4 class="mb-0">
                                            {{ $task->commission ? number_format($task->commission, 2) : '0.00' }} SAR
                                        </h4>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="card bg-info text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-info-circle fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Status') }}</h6>
                                        <span class="status-badge bg-white text-info fw-bold">
                                            {{ ucfirst($task->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="card bg-{{ $task->closed ? 'danger' : 'success' }} text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-lock{{ $task->closed ? '' : '-open' }} fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Task Status') }}</h6>
                                        <span
                                            class="status-badge bg-white text-{{ $task->closed ? 'danger' : 'success' }} fw-bold">
                                            {{ $task->closed ? __('Closed') : __('Open') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- نقطة الاستلام والتسليم -->
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
                                            <i class="fas fa-map-marker-alt me-1"></i> {{ __('Pickup') }}
                                        </span>
                                        @if (optional($task->pickup)->latitude && optional($task->pickup)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->pickup->latitude }},{{ $task->pickup->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-map-location-dot me-1"></i>
                                            </a>
                                        @endif
                                    </h6>


                                    <ul class="list-unstyled mb-3">
                                        <li><strong>Address:</strong> {{ optional($task->pickup)->address }}</li>
                                        <li><strong>Contact Name:</strong> {{ optional($task->pickup)->contact_name }}</li>
                                        <li><strong>Phone:</strong> {{ optional($task->pickup)->contact_phone }}</li>
                                        <li><strong>Email:</strong> {{ optional($task->pickup)->contact_emil }}</li>
                                        <li><strong>Note:</strong> {{ optional($task->pickup)->note }}</li>
                                        @if (optional($task->pickup)->scheduled_time)
                                            <li><strong>Pickup Before:</strong>
                                                {{ \Carbon\Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>

                                    @if (optional($task->pickup)->image)
                                        <div class="mt-3">
                                            <strong>Image:</strong>
                                            <div class="mt-2 border rounded overflow-hidden" style=" width: 150px;">
                                                <img src="{{ asset($task->pickup->image) }}" alt="Pickup Image"
                                                    class="img-fluid rounded">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Delivery -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 shadow-sm bg-white">
                                    <h6 class="mb-3 text-success d-flex align-items-center justify-content-between">
                                        <span>
                                            <i class="fas fa-truck me-1"></i> {{ __('Delivery') }}
                                        </span>
                                        @if (optional($task->delivery)->latitude && optional($task->delivery)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->delivery->latitude }},{{ $task->delivery->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-map-location-dot me-1"></i>
                                            </a>
                                        @endif
                                    </h6>


                                    <ul class="list-unstyled mb-3">
                                        <li><strong>Address:</strong> {{ optional($task->delivery)->address }}</li>
                                        <li><strong>Contact Name:</strong> {{ optional($task->delivery)->contact_name }}
                                        </li>
                                        <li><strong>Phone:</strong> {{ optional($task->delivery)->contact_phone }}</li>
                                        <li><strong>Email:</strong> {{ optional($task->delivery)->contact_emil }}</li>
                                        <li><strong>Note:</strong> {{ optional($task->delivery)->note }}</li>
                                        @if (optional($task->delivery)->scheduled_time)
                                            <li><strong>Delivery Before:</strong>
                                                {{ \Carbon\Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>

                                    @if (optional($task->delivery)->image_path)
                                        <div class="mt-3">
                                            <strong>Image:</strong>
                                            <div class="mt-2 border rounded overflow-hidden" style="max-height: 250px;">
                                                <img src="{{ asset('storage/' . $task->delivery->image_path) }}"
                                                    alt="Delivery Image" class="img-fluid rounded">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- مذكرة التوصيل -->
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
                                                $isImage = in_array($fileExtension, [
                                                    'jpg',
                                                    'jpeg',
                                                    'png',
                                                    'gif',
                                                    'webp',
                                                ]);

                                                $fileIcons = [
                                                    'pdf' => ['icon' => 'fas fa-file-pdf', 'color' => 'text-danger'],
                                                    'doc' => ['icon' => 'fas fa-file-word', 'color' => 'text-primary'],
                                                    'docx' => ['icon' => 'fas fa-file-word', 'color' => 'text-primary'],
                                                    'txt' => ['icon' => 'fas fa-file-alt', 'color' => 'text-secondary'],
                                                    'csv' => ['icon' => 'fas fa-file-csv', 'color' => 'text-success'],
                                                ];

                                                $fileIcon = $fileIcons[$fileExtension] ?? [
                                                    'icon' => 'fas fa-file',
                                                    'color' => 'text-muted',
                                                ];
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
                                                        <i
                                                            class="{{ $fileIcon['icon'] }} file-icon {{ $fileIcon['color'] }}"></i>
                                                    </div>
                                                    <h6 class="mb-2">{{ $fileName }}</h6>
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

                            @if ($task->delivery_note && $task->delivery_number)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-success d-flex align-items-center">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <div>
                                                <strong>{{ __('Task Completed Successfully') }}</strong><br>
                                                <small>{{ __('Delivery completed with reference number') }}:
                                                    <strong>{{ $task->delivery_number }}</strong></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Modal لعرض الصورة -->
                    @if (
                        $task->delivery_note &&
                            in_array(strtolower(pathinfo($task->delivery_note, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                        <div class="modal fade" id="deliveryNoteModal" tabindex="-1"
                            aria-labelledby="deliveryNoteModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deliveryNoteModalLabel">
                                            <i class="fas fa-file-invoice me-2"></i>
                                            {{ __('Delivery Note') }} - {{ __('Task') }} #{{ $task->id }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img src="{{ asset('storage/' . $task->delivery_note) }}"
                                            alt="{{ __('Delivery Note') }}" class="img-fluid rounded shadow">
                                        @if ($task->delivery_number)
                                            <div class="mt-3">
                                                <span class="badge bg-primary fs-6">
                                                    {{ __('Reference') }}: {{ $task->delivery_number }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="modal-footer">
                                        <a href="{{ asset('storage/' . $task->delivery_note) }}" target="_blank"
                                            class="btn btn-primary">
                                            <i class="fas fa-external-link-alt me-1"></i>
                                            {{ __('Open in New Tab') }}
                                        </a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            {{ __('Close') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <!-- بيانات إضافية -->
                @if ($task->additional_data)
                    <div class="card mb-4 info-card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-layer-group me-2 text-primary"></i>
                                {{ __('Additional Data') }}
                            </h5>
                        </div>

                        <div class="card-body mt-3">
                            @if (is_array($task->additional_data) && count($task->additional_data) > 0)
                                <div class="row">
                                    @foreach ($task->additional_data as $key => $field)
                                        <div class="col-md-6 mb-4">
                                            <div class="border rounded p-3 h-100 ">
                                                <h6 class="text-muted mb-2">

                                                    {{ $field['label'] }}
                                                </h6>

                                                @switch($field['type'])
                                                    @case('text')
                                                    @case('string')

                                                    @case('number')
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                    @break

                                                    @case('image')
                                                        <img src="{{ asset('storage/' . $field['value']) }}"
                                                            alt="{{ $field['label'] }}" class="img-fluid rounded border"
                                                            style="max-height: 200px; object-fit: cover;">
                                                    @break

                                                    @case('file')
                                                        @php
                                                            $ext = strtolower(
                                                                pathinfo($field['value'], PATHINFO_EXTENSION),
                                                            );
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

                                                    @case('file_with_text')
                                                        @if ($field['value'])
                                                            @php
                                                                $ext = strtolower(
                                                                    pathinfo($field['value'], PATHINFO_EXTENSION),
                                                                );
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

                                                            <div class="d-flex align-items-center mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}"
                                                                    target="_blank"
                                                                    class="d-flex align-items-center text-decoration-none">
                                                                    <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                    <span
                                                                        class="text-truncate">{{ basename($field['value']) }}</span>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if (isset($field['text']) && $field['text'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">Additional Info:</small>
                                                                <p class="mb-0 fw-medium">{{ $field['text'] }}</p>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @case('file_expiration_date')
                                                        @if ($field['value'])
                                                            @php
                                                                $ext = strtolower(
                                                                    pathinfo($field['value'], PATHINFO_EXTENSION),
                                                                );
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

                                                            <div class="d-flex align-items-center mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}"
                                                                    target="_blank"
                                                                    class="d-flex align-items-center text-decoration-none">
                                                                    <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                    <span
                                                                        class="text-truncate">{{ basename($field['value']) }}</span>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if (isset($field['expiration']) && $field['expiration'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">Expires:</small>
                                                                <p class="mb-0 fw-medium">
                                                                    {{ \Carbon\Carbon::parse($field['expiration'])->format('Y-m-d') }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @default
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                @endswitch
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info" role="alert">
                                    {{ __('No additional data found for this customer.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif




            </div>

            <!-- سجل الأحداث -->
            <div class="col-lg-4 col-md-12">
                <div class="card info-card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-history me-2 text-primary"></i>
                            {{ __('Task History') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($task->history->count())
                            <ul class="timeline">
                                @foreach ($task->history->sortByDesc('created_at') as $entry)
                                    <li class="timeline-item">
                                        <span class="timeline-point"></span>
                                        <div class="timeline-event">
                                            <div class="d-flex justify-content-between mb-1">
                                                <strong>{{ ucfirst($entry->action_type) }}</strong>
                                                <small
                                                    class="text-muted">{{ $entry->created_at->format('Y-m-d H:i') }}</small>
                                            </div>
                                            <p class="mb-1">{{ $entry->description }}</p>
                                            @if ($entry->file_path)
                                                <a href="{{ asset('storage/' . $entry->file_path) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">عرض المرفق</a>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted">لا توجد أحداث مسجلة.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
